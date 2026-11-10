<?php

namespace App\Services;

use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\Result;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the public results board for a gala.
 *
 * The same payload is read two ways: by the controller when a page is first
 * asked for, and by the broadcast event that pushes an update down the socket
 * the moment the referee signs an event off. Both go through here so a swim
 * never looks one way on arrival and another way after a refresh.
 */
class ResultsBoard
{
    /**
     * How many earlier events the board keeps below the latest one.
     */
    private const EARLIER_EVENTS = 12;

    /**
     * How long one reading of the board is reused for.
     *
     * A published event tells every page watching the gala to come and read
     * the board, so they all arrive at once. This answers that burst from a
     * single read.
     *
     * The key is built from what has been published and recorded, so a
     * publication is never held back by it. Results are stamped to the
     * second, so two changes within the same second are the one case the key
     * cannot tell apart, and the second of them waits out the window below.
     * On a board whose protest clock runs for thirty minutes, ten seconds is
     * not a cost worth paying anything to avoid.
     */
    private const CACHE_SECONDS = 10;

    public function __construct(private readonly ResultService $results) {}

    /**
     * Every part of the live board that changes while a gala is being swum.
     *
     * The keys are the names of the live props on the page, so a broadcast can
     * hand these straight to Inertia without the two drifting apart.
     *
     * @return array{isLive: bool, progress: array{published: int, total: int, percent: int}, latest: array<string, mixed>|null, earlier: array<int, array<string, mixed>>, pointsTable: array<int, array<string, mixed>>, refreshedAt: string}
     */
    public function board(Gala $gala): array
    {
        /** @var array{isLive: bool, progress: array{published: int, total: int, percent: int}, latest: array<string, mixed>|null, earlier: array<int, array<string, mixed>>, pointsTable: array<int, array<string, mixed>>, refreshedAt: string} */
        return Cache::remember(
            $this->cacheKey($gala),
            now()->addSeconds(self::CACHE_SECONDS),
            fn (): array => $this->build($gala),
        );
    }

    /**
     * A key that changes the moment any result does.
     *
     * Three cheap aggregates stand in for the board itself: when an event was
     * last published, how many swims are recorded, and when one last changed.
     * A publish, a correction or a re-placing moves one of them.
     */
    private function cacheKey(Gala $gala): string
    {
        return sprintf(
            'results-board:%s:%s:%d:%s',
            $gala->getKey(),
            (string) $gala->events()->max('results_published_at'),
            Result::query()->where('gala_id', $gala->getKey())->count(),
            (string) Result::query()->where('gala_id', $gala->getKey())->max('updated_at'),
        );
    }

    /**
     * @return array{isLive: bool, progress: array{published: int, total: int, percent: int}, latest: array<string, mixed>|null, earlier: array<int, array<string, mixed>>, pointsTable: array<int, array<string, mixed>>, refreshedAt: string}
     */
    private function build(Gala $gala): array
    {
        $events = $this->publishedEvents($gala)
            ->sortByDesc(fn (GalaEvent $event): string => (string) $event->results_published_at)
            ->values();

        $total = $gala->events()->count();
        $published = $events->count();
        $latest = $events->first();

        return [
            'isLive' => $this->isLive($gala),
            'progress' => [
                'published' => $published,
                'total' => $total,
                'percent' => $total === 0 ? 0 : (int) round($published / $total * 100),
            ],
            'latest' => $latest === null ? null : $this->event($latest),
            'earlier' => $events->skip(1)->take(self::EARLIER_EVENTS)->values()
                ->map(fn (GalaEvent $event): array => $this->event($event))
                ->all(),
            'pointsTable' => $this->results->publishedPointsTable($gala->getKey()),
            'refreshedAt' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * The events whose results the referee has released to the public.
     *
     * @return Collection<int, GalaEvent>
     */
    public function publishedEvents(Gala $gala): Collection
    {
        return $gala->events()
            ->whereNotNull('results_published_at')
            ->with(['results' => fn ($query) => $query
                ->official()
                ->with(['member:id,first_name,last_name', 'club:id,name', 'relayTeam:id,name,letter'])
                ->orderBy('place')])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function gala(Gala $gala): array
    {
        return [
            'name' => $gala->name,
            'slug' => $gala->slug,
            'level' => $gala->level->label(),
            'course' => $gala->course->label(),
            'starts_on' => $gala->starts_on->toDateString(),
            'ends_on' => $gala->ends_on->toDateString(),
            'venue' => $gala->venueName(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function event(GalaEvent $event): array
    {
        return [
            'number' => $event->event_number,
            'title' => $event->title(),
            'age_group' => $event->age_group,
            'is_relay' => $event->is_relay,
            'published_at' => $event->results_published_at?->toIso8601String(),
            'results' => $event->results->map(fn (Result $result): array => [
                'place' => $result->place,
                'swimmer' => $result->competitorName(),
                'club' => $result->club->name,
                'time' => $result->formattedTime(),
                'is_personal_best' => $result->is_personal_best,
                'record_flagged' => $result->record_flagged,
            ])->all(),
        ];
    }

    /**
     * A gala counts as live while it is being swum: in progress, and today
     * falls inside its dates.
     */
    public function isLive(Gala $gala): bool
    {
        return $gala->status->value === 'in_progress'
            && ! Carbon::now()->startOfDay()->lt($gala->starts_on)
            && ! Carbon::now()->startOfDay()->gt($gala->ends_on);
    }
}

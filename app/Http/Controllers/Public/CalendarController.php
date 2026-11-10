<?php

namespace App\Http\Controllers\Public;

use App\Enums\GalaLevel;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\Season;
use App\Services\ResultsBoard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(private readonly ResultsBoard $board) {}

    public function index(Request $request): Response
    {
        $season = Season::current();

        $galas = Gala::query()
            ->published()
            ->when($season, fn ($query) => $query->where('season_id', $season->getKey()))
            ->when($request->string('level')->toString(), fn ($query, $level) => $query->where('level', $level))
            ->with(['venue:id,name,city,slug', 'host:id,name,slug'])
            ->withCount(['events as published_results_count' => fn ($query) => $query->whereNotNull('results_published_at')])
            ->orderBy('starts_on')
            ->get()
            ->map(fn (Gala $gala): array => [
                'name' => $gala->name,
                'slug' => $gala->slug,
                'level' => $gala->level->value,
                'level_label' => $gala->level->label(),
                'course' => $gala->course->value,
                'status' => $gala->status->label(),
                'starts_on' => $gala->starts_on->toDateString(),
                'ends_on' => $gala->ends_on->toDateString(),
                'eligible_ages' => $gala->eligible_ages,
                'venue' => $gala->venue?->name,
                'city' => $gala->venue?->city,
                'host' => $gala->host->name,
                'entries_close_at' => $gala->entries_close_at?->toDateString(),
                'is_live' => $this->board->isLive($gala),
                'has_results' => (int) $gala->getAttribute('published_results_count') > 0,
            ]);

        return Inertia::render('public/calendar', [
            'season' => $season === null ? null : [
                'name' => $season->name,
                'age_up_date' => $season->age_up_date->toDateString(),
                'starts_on' => $season->starts_on->toDateString(),
                'ends_on' => $season->ends_on->toDateString(),
            ],
            'galas' => $galas,
            'levels' => $this->levelCounts($season),
            'filters' => ['level' => $request->string('level')->toString() ?: null],
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * The levels the season actually holds meets at, with a count each.
     *
     * Offering every level the enum defines would hand the reader filters that
     * return nothing, so a level only appears once something is on the calendar
     * at it. The counts ignore the active filter: they describe the season, not
     * the current view.
     *
     * @return array<int, array{value: string, label: string, count: int}>
     */
    private function levelCounts(?Season $season): array
    {
        $counts = Gala::query()
            ->published()
            ->when($season, fn ($query) => $query->where('season_id', $season->getKey()))
            ->get(['level'])
            ->countBy(fn (Gala $gala): string => $gala->level->value);

        return collect(GalaLevel::cases())
            ->map(fn (GalaLevel $level): array => [
                'value' => $level->value,
                'label' => $level->label(),
                'count' => (int) $counts->get($level->value, 0),
            ])
            ->filter(fn (array $level): bool => $level['count'] > 0)
            ->values()
            ->all();
    }

    public function show(Gala $gala): Response
    {
        abort_unless($gala->is_published, 404);

        $gala->load(['venue', 'host', 'sanctioningBody', 'season', 'sessions.events']);

        return Inertia::render('public/gala', [
            'gala' => [
                'name' => $gala->name,
                'slug' => $gala->slug,
                'level_label' => $gala->level->label(),
                'course_label' => $gala->course->label(),
                'status_label' => $gala->status->label(),
                'is_sanctioned' => $gala->status->isSanctioned(),
                'starts_on' => $gala->starts_on->toDateString(),
                'ends_on' => $gala->ends_on->toDateString(),
                'age_up_date' => $gala->effectiveAgeUpDate()->toDateString(),
                'entries_close_at' => $gala->entries_close_at?->toDateString(),
                'entry_corrections_deadline' => $gala->entry_corrections_deadline?->toDateString(),
                'entry_fee' => $gala->entry_fee,
                'late_entry_fee' => $gala->late_entry_fee,
                'appeal_deposit' => $gala->appeal_deposit,
                'eligible_ages' => $gala->eligible_ages,
                'description' => $gala->description,
                'entries_email' => $gala->entries_email,
                'qualifying_times_apply' => $gala->qualifying_times_apply,
                'host' => $gala->host->name,
                'sanctioning_body' => $gala->sanctioningBody?->name,
                'venue' => $gala->venue === null ? null : [
                    'name' => $gala->venue->name,
                    'slug' => $gala->venue->slug,
                    'city' => $gala->venue->city,
                    'pool_length' => $gala->venue->pool_length,
                    'lanes' => $gala->venue->lanes,
                ],
            ],
            'sessions' => $gala->sessions->map(fn ($session): array => [
                'name' => $session->name,
                'held_on' => $session->held_on->toDateString(),
                'warmup_at' => $session->warmup_at,
                'starts_at' => $session->starts_at,
                'events' => $session->events->map(fn ($event): array => [
                    'number' => $event->event_number,
                    'gender' => ucfirst($event->gender),
                    'age_group' => $event->age_group,
                    'distance' => $event->distance,
                    'stroke' => $event->stroke->label(),
                    'round' => str_replace('_', ' ', $event->round),
                ]),
            ]),
        ]);
    }
}

<?php

namespace App\Services;

use App\Events\EventResultsPublished;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Result;
use App\Support\Broadcasts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Records swims and keeps everything that follows from them in step: personal
 * bests, places, points, and records proposed for ratification.
 */
class ResultService
{
    /**
     * Points awarded by finishing place in an individual event, from the
     * national handbook's trophy table.
     *
     * @var array<int, int>
     */
    private const POINTS = [1 => 10, 2 => 8, 3 => 6, 4 => 5, 5 => 4, 6 => 3, 7 => 2, 8 => 1];

    /**
     * Relays are scored on their own, shorter table and weighted towards the
     * win: 16, 8 and 4 for the first three places only.
     *
     * @var array<int, int>
     */
    private const RELAY_POINTS = [1 => 16, 2 => 8, 3 => 4];

    public function __construct(private readonly RecordChecker $records) {}

    /**
     * Record one swim. A disqualification carries a rule code and no time.
     *
     * @param  array{time_ms?: int|null, status?: string, dq_code?: string|null, dq_reason?: string|null}  $data
     */
    public function record(GalaEntry $entry, array $data, ?int $recordedBy = null): Result
    {
        $status = $data['status'] ?? 'official';
        $timeMs = $status === 'official' ? ($data['time_ms'] ?? null) : null;

        return DB::transaction(function () use ($entry, $status, $timeMs, $data, $recordedBy): Result {
            $result = Result::updateOrCreate(
                ['gala_entry_id' => $entry->getKey()],
                [
                    'gala_id' => $entry->gala_id,
                    'gala_event_id' => $entry->gala_event_id,
                    'member_id' => $entry->member_id,
                    'organization_id' => $entry->organization_id,
                    'time_ms' => $timeMs,
                    'heat' => $entry->heat,
                    'lane' => $entry->lane,
                    'status' => $status,
                    'dq_code' => $data['dq_code'] ?? null,
                    'dq_reason' => $data['dq_reason'] ?? null,
                    'recorded_by' => $recordedBy,
                ],
            );

            if ($timeMs !== null) {
                // Whether this is a record depends on the rest of the field, so
                // it is settled in placeEvent() once the event's times are in.
                $result->forceFill(['is_personal_best' => $this->isPersonalBest($result)])->save();
            }

            $entry->forceFill([
                'status' => $status === 'dns' ? 'no_show' : 'confirmed',
            ])->save();

            return $result;
        });
    }

    /**
     * Is this the swimmer's fastest ever official time at this distance,
     * stroke and course?
     */
    public function isPersonalBest(Result $result): bool
    {
        if ($result->time_ms === null) {
            return false;
        }

        $event = $result->event;

        $faster = Result::query()
            ->official()
            ->where('member_id', $result->member_id)
            ->whereKeyNot($result->getKey())
            ->whereNotNull('time_ms')
            ->where('time_ms', '<=', $result->time_ms)
            ->whereHas('event', fn ($query) => $query
                ->where('stroke', $event->stroke)
                ->where('distance', $event->distance))
            ->whereHas('gala', fn ($query) => $query->where('course', $result->gala->course))
            ->exists();

        return ! $faster;
    }

    /**
     * Place and score an event once its times are in. Disqualified and
     * absent swimmers are not placed.
     */
    public function placeEvent(GalaEvent $event): int
    {
        // reorder() rather than orderBy(): the relation is ordered by place for
        // display, and placing again after a correction must go strictly on
        // time, not on the places that are about to be overwritten.
        $results = $event->results()
            ->official()
            ->whereNotNull('time_ms')
            ->with('relayTeam.swimmers')
            ->reorder('time_ms')
            ->get();

        DB::transaction(function () use ($results, $event): void {
            $place = 0;
            $previousTime = null;
            $tied = 0;

            foreach ($results as $result) {
                // Dead heats share a place, and the next place is skipped.
                if ($previousTime !== null && $result->time_ms === $previousTime) {
                    $tied++;
                } else {
                    $place += 1 + $tied;
                    $tied = 0;
                }

                $previousTime = $result->time_ms;

                $result->forceFill([
                    'place' => $place,
                    'points' => $this->pointsFor($result, $place),
                ])->save();
            }

            $event->results()
                ->reorder()
                ->where('status', '!=', 'official')
                ->update(['place' => null, 'points' => null]);
        });

        $this->records->evaluate($event, $results);

        return $results->count();
    }

    /**
     * Swims in this event currently standing as proposed records.
     */
    public function proposedRecords(GalaEvent $event): int
    {
        return $event->results()->reorder()->where('record_flagged', true)->count();
    }

    /**
     * The points a placing earns.
     *
     * A relay that borrowed a swimmer may still take the medal but scores
     * nothing, so the club cannot improve its trophy standing by fielding
     * someone else's swimmer.
     */
    private function pointsFor(Result $result, int $place): int
    {
        if ($result->relay_team_id === null) {
            return self::POINTS[$place] ?? 0;
        }

        if ($result->relayTeam === null || ! $result->relayTeam->scoresPoints()) {
            return 0;
        }

        return self::RELAY_POINTS[$place] ?? 0;
    }

    /**
     * Publish an event's results to the public site.
     *
     * Publishing is the moment the results become public, so it is also the
     * moment the live board is pushed to everyone watching the gala.
     */
    public function publish(GalaEvent $event): void
    {
        $this->placeEvent($event);

        $event->forceFill(['results_published_at' => now()])->save();

        Broadcasts::quietly(new EventResultsPublished($event->gala, $event));
    }

    /**
     * The club points table as it stands, counting every swim recorded so far.
     *
     * This is the officials' view: it moves as times are entered, before the
     * referee has released anything, which is what the recorders and the
     * organiser want to see while the gala is being swum.
     *
     * @return array<int, array{club: string, points: int, golds: int, silvers: int, bronzes: int}>
     */
    public function pointsTable(int $galaId): array
    {
        return $this->buildPointsTable(Result::query()->where('gala_id', $galaId));
    }

    /**
     * The same table counting only events the referee has released.
     *
     * Anyone who is not entering times sees this one. Points are derived from
     * placings, so publishing the running total would give away the placings
     * in an event that has not been signed off, and would then shift under the
     * reader when a timekeeper corrects a time.
     *
     * @return array<int, array{club: string, points: int, golds: int, silvers: int, bronzes: int}>
     */
    public function publishedPointsTable(int $galaId): array
    {
        return $this->buildPointsTable(
            Result::query()
                ->where('gala_id', $galaId)
                ->whereHas('event', fn ($query) => $query->whereNotNull('results_published_at')),
        );
    }

    /**
     * @param  Builder<Result>  $results
     * @return array<int, array{club: string, points: int, golds: int, silvers: int, bronzes: int}>
     */
    private function buildPointsTable(Builder $results): array
    {
        return $results
            ->official()
            ->with('club:id,name')
            ->get()
            ->groupBy('organization_id')
            ->map(fn ($rows): array => [
                'club' => $rows->first()->club->name,
                'points' => (int) $rows->sum('points'),
                'golds' => $rows->where('place', 1)->count(),
                'silvers' => $rows->where('place', 2)->count(),
                'bronzes' => $rows->where('place', 3)->count(),
            ])
            ->sortByDesc('points')
            ->values()
            ->all();
    }
}

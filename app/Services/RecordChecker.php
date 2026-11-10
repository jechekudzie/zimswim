<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\GalaEvent;
use App\Models\Result;
use App\Models\SwimRecord;
use Illuminate\Support\Collection;

/**
 * Compares a swim against the standing record and, when it is faster, proposes
 * a new record for the authorised body to ratify. Nothing here changes a
 * ratified record; that is a board decision.
 */
class RecordChecker
{
    /**
     * Decide which swim in an event is a record, and mark it.
     *
     * Only the fastest legal swim in an event can be one, and flags are worked
     * out for the event as a whole rather than as each time is typed in. A slow
     * swim entered first must not be left wearing a record marker once the rest
     * of the heat is in, and re-timing a swim has to be able to take the marker
     * away again.
     *
     * @param  Collection<int, Result>  $results  fastest first
     * @return int the number of records proposed, which is zero or one
     */
    public function evaluate(GalaEvent $event, Collection $results): int
    {
        $event->results()->where('record_flagged', true)->update(['record_flagged' => false]);

        $fastest = $results->first(fn (Result $result): bool => $result->status === 'official'
            && $result->time_ms !== null);

        if ($fastest === null || ! $this->beatsStandingRecord($fastest)) {
            return 0;
        }

        $fastest->forceFill(['record_flagged' => true])->save();
        $this->propose($fastest);

        return 1;
    }

    /**
     * Is this swim faster than the record that stands?
     *
     * Where no record has been ratified for the category yet, someone still has
     * to set the inaugural mark — but only the fastest time anyone has swum,
     * not every swim in the heat.
     */
    public function beatsStandingRecord(Result $result): bool
    {
        if ($result->time_ms === null || $result->status !== 'official') {
            return false;
        }

        $standing = $this->standingRecord($result);

        if ($standing !== null) {
            return $result->time_ms < $standing->time_ms;
        }

        return ! $this->fasterSwimExists($result);
    }

    /**
     * Has anyone swum this category faster, at any gala?
     */
    private function fasterSwimExists(Result $result): bool
    {
        $event = $result->event;

        return Result::query()
            ->official()
            ->whereNotNull('time_ms')
            ->whereKeyNot($result->getKey())
            ->where('time_ms', '<', $result->time_ms)
            ->whereHas('event', fn ($query) => $query
                ->where('stroke', $event->stroke)
                ->where('distance', $event->distance)
                ->where('age_group', $event->age_group)
                ->where('gender', $event->gender))
            ->whereHas('gala', fn ($query) => $query->where('course', $result->gala->course))
            ->exists();
    }

    public function standingRecord(Result $result): ?SwimRecord
    {
        $event = $result->event;

        return SwimRecord::query()
            ->ratified()
            ->where('scope', 'national')
            ->where('course', $result->gala->course)
            ->where('gender', $this->genderFor($result))
            ->where('age_group', $event->age_group)
            ->where('stroke', $event->stroke)
            ->where('distance', $event->distance)
            ->orderBy('time_ms')
            ->first();
    }

    /**
     * Create a pending record so the board sees it on the dashboard. An
     * existing pending proposal for the same event is replaced only when the
     * new swim is faster.
     */
    public function propose(Result $result): ?SwimRecord
    {
        $event = $result->event;

        $existing = SwimRecord::query()
            ->awaitingRatification()
            ->where('scope', 'national')
            ->where('course', $result->gala->course)
            ->where('gender', $this->genderFor($result))
            ->where('age_group', $event->age_group)
            ->where('stroke', $event->stroke)
            ->where('distance', $event->distance)
            ->orderBy('time_ms')
            ->first();

        if ($existing !== null && $existing->time_ms <= $result->time_ms) {
            return $existing;
        }

        return SwimRecord::create([
            'scope' => 'national',
            'course' => $result->gala->course,
            'gender' => $this->genderFor($result),
            'age_group' => $event->age_group,
            'stroke' => $event->stroke,
            'distance' => $event->distance,
            'is_relay' => $event->is_relay,
            'time_ms' => $result->time_ms,
            // A relay record is held by the team, not by one swimmer.
            'holder_name' => $result->competitorName(),
            'member_id' => $result->member_id,
            'club_organization_id' => $result->organization_id,
            'venue_name' => $result->gala->venueName(),
            'set_on' => $result->gala->starts_on->toDateString(),
            'gala_id' => $result->gala_id,
            'result_id' => $result->getKey(),
            'status' => RecordStatus::Pending,
        ]);
    }

    private function genderFor(Result $result): string
    {
        return $result->event->genderRestriction();
    }
}

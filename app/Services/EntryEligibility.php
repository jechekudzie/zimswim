<?php

namespace App\Services;

use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Result;

/**
 * The checks that stop a swimmer being entered in the wrong place: affiliation,
 * age group, consent, qualifying times and entry deadlines.
 */
class EntryEligibility
{
    /**
     * Every reason this swimmer may not be entered in this event. An empty
     * list means the entry is good.
     *
     * @return array<int, string>
     */
    public function problems(Member $member, GalaEvent $event): array
    {
        $gala = $event->gala;
        $problems = [];

        if (! $gala->isAcceptingEntries()) {
            $problems[] = 'Entries for this gala are not open.';
        }

        if (! $member->status->isCompeting()) {
            $problems[] = $member->fullName().' is not an active member.';
        }

        if (! $member->hasRequiredConsent()) {
            $problems[] = 'A parent or guardian consent is required before entering.';
        }

        if (! $this->isAffiliated($member)) {
            $problems[] = $this->affiliationProblem($member);
        }

        if (! $this->isRightAgeGroup($member, $event)) {
            $problems[] = sprintf(
                '%s is %d at the age-up date and cannot swim the %s age group.',
                $member->fullName(),
                $member->ageAt($gala->effectiveAgeUpDate()),
                $event->age_group,
            );
        }

        if (! $this->isRightGender($member, $event)) {
            $problems[] = 'This event is not open to that swimmer.';
        }

        if ($gala->qualifying_times_apply && ! $this->meetsQualifyingTime($member, $event)) {
            $problems[] = 'No qualifying time on the national database for this event.';
        }

        return $problems;
    }

    public function isEligible(Member $member, GalaEvent $event): bool
    {
        return $this->problems($member, $event) === [];
    }

    public function isRightAgeGroup(Member $member, GalaEvent $event): bool
    {
        return $event->acceptsAge($member->date_of_birth, $event->gala->effectiveAgeUpDate());
    }

    public function isRightGender(Member $member, GalaEvent $event): bool
    {
        $restriction = $event->genderRestriction();

        return $restriction === 'mixed' || $member->gender === $restriction;
    }

    /**
     * Is this swimmer's club entitled to enter anyone?
     *
     * Clubs pay through their province, and the province is given a deadline
     * by which everything owed must have reached Zimbabwe Swimming. A province
     * that has not met it is not fully affiliated and its clubs may not enter
     * swimmers. The club's own standing is checked for the same reason one
     * level down: a suspended club is not entering anyone either.
     *
     * This replaced a per-swimmer capitation flag that nothing in the system
     * could set, which would have refused every entry in production.
     */
    public function isAffiliated(Member $member): bool
    {
        $club = $member->club;

        if ($club === null || ! $club->isAffiliated()) {
            return false;
        }

        $province = $club->province();

        return $province === null || $province->isAffiliated();
    }

    /**
     * Say which body is out of good standing, because the club secretary who
     * sees this cannot act on it without knowing where to go.
     */
    private function affiliationProblem(Member $member): string
    {
        $club = $member->club;

        if ($club === null) {
            return $member->fullName().' is not attached to a club.';
        }

        if (! $club->isAffiliated()) {
            return $club->name.' is not fully affiliated for this season, so it cannot enter swimmers.';
        }

        // The club is in good standing, so by elimination it is the province
        // above it that is not, and a club always has one to reach here.
        return $club->province()->name
            .' is not fully affiliated for this season, so its clubs cannot enter swimmers.';
    }

    /**
     * Qualifying times are measured against the swimmer's best official time
     * for the event. "NT" is never acceptable where times apply.
     */
    public function meetsQualifyingTime(Member $member, GalaEvent $event): bool
    {
        if ($event->qualifying_time_ms === null) {
            return $this->bestTimeFor($member, $event) !== null;
        }

        $best = $this->bestTimeFor($member, $event);

        return $best !== null && $best <= $event->qualifying_time_ms;
    }

    /**
     * The swimmer's fastest official time for this stroke and distance.
     */
    public function bestTimeFor(Member $member, GalaEvent $event): ?int
    {
        return Result::query()
            ->official()
            ->where('member_id', $member->getKey())
            ->whereNotNull('time_ms')
            ->whereHas('event', fn ($query) => $query
                ->where('stroke', $event->stroke)
                ->where('distance', $event->distance))
            ->min('time_ms');
    }

    /**
     * Entries lodged after the deadline are late and need organiser approval.
     */
    public function isLate(GalaEvent $event): bool
    {
        $closesAt = $event->gala->entries_close_at;

        return $closesAt !== null && $closesAt->isPast();
    }

    public function alreadyEntered(Member $member, GalaEvent $event): bool
    {
        return GalaEntry::query()
            ->where('gala_event_id', $event->getKey())
            ->where('member_id', $member->getKey())
            ->exists();
    }
}

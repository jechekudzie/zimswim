<?php

namespace Database\Seeders;

use App\Enums\GalaStatus;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use App\Services\EntryEligibility;
use App\Services\FinalsService;
use App\Services\RelayService;
use App\Services\ResultService;
use App\Services\SeedingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Walks the National Sprint Gala through entries, seeding and a first session
 * of results so the gala-day screens have something real to show.
 */
class DemoGalaDaySeeder extends Seeder
{
    public function __construct(
        private readonly SeedingService $seeding,
        private readonly ResultService $results,
        private readonly EntryEligibility $eligibility,
        private readonly RelayService $relays,
        private readonly FinalsService $finals,
    ) {}

    public function run(): void
    {
        $gala = Gala::where('slug', 'national-sprint-gala-2026')->first();

        if ($gala === null) {
            return;
        }

        $gala->forceFill([
            'status' => GalaStatus::EntriesOpen,
            'entries_close_at' => now()->addWeek(),
        ])->save();

        $recorder = User::where('email', 'technical@zimbabweswimming.co.zw')->first();
        $members = Member::query()->active()->with('registrations')->get();

        // Enter every eligible swimmer into the 50m events of their gender.
        $events = $gala->events()
            ->where('distance', 50)
            ->whereIn('event_number', [201, 202, 213, 214])
            ->get();

        foreach ($events as $event) {
            foreach ($members as $member) {
                if (! $this->eligibility->isEligible($member, $event)) {
                    continue;
                }

                GalaEntry::firstOrCreate(
                    ['gala_event_id' => $event->getKey(), 'member_id' => $member->getKey()],
                    [
                        'gala_id' => $gala->getKey(),
                        'organization_id' => $member->organization_id,
                        'entry_time_ms' => random_int(28_000, 44_000),
                        'status' => 'entered',
                        'entered_by' => $recorder?->getKey(),
                    ],
                );
            }

            $this->seeding->seed($event->fresh());
        }

        // Record and publish the first two events so results are live.
        foreach ($events->take(2) as $event) {
            foreach ($event->entries()->get() as $entry) {
                $this->results->record($entry, [
                    'time_ms' => max(26_000, ($entry->entry_time_ms ?? 35_000) - random_int(0, 1_200)),
                    'status' => 'official',
                ], $recorder?->getKey());
            }

            $this->results->publish($event->fresh());
        }

        $this->runHeatsAndFinal($gala, $recorder?->getKey());
        $this->declareRelays($gala, $members);

        $gala->forceFill(['status' => GalaStatus::InProgress])->save();
    }

    /**
     * Swim the 100m freestyle heats and promote the field into the final, so
     * the finals screen has a real qualifying list.
     */
    private function runHeatsAndFinal(Gala $gala, ?int $recordedBy): void
    {
        $heatsEvents = $gala->events()
            ->whereIn('event_number', [207, 208])
            ->get();

        foreach ($heatsEvents as $heats) {
            $members = Member::query()->active()->with('registrations')->get();

            foreach ($members as $member) {
                if (! $this->eligibility->isEligible($member, $heats)) {
                    continue;
                }

                GalaEntry::firstOrCreate(
                    ['gala_event_id' => $heats->getKey(), 'member_id' => $member->getKey()],
                    [
                        'gala_id' => $gala->getKey(),
                        'organization_id' => $member->organization_id,
                        'entry_time_ms' => random_int(62_000, 95_000),
                        'status' => 'entered',
                        'entered_by' => $recordedBy,
                    ],
                );
            }

            $this->seeding->seed($heats->fresh());

            foreach ($heats->entries()->get() as $entry) {
                $this->results->record($entry, [
                    'time_ms' => max(58_000, ($entry->entry_time_ms ?? 70_000) - random_int(0, 2_000)),
                    'status' => 'official',
                ], $recordedBy);
            }

            $this->results->publish($heats->fresh());

            $final = GalaEvent::where('parent_event_id', $heats->getKey())->first();

            if ($final !== null) {
                $this->finals->promote($final, $recordedBy);
            }
        }
    }

    /**
     * Declare two relay teams per club in each relay event, which is the most
     * the handbook allows, so the relay screen shows the limit in action.
     *
     * @param  Collection<int, Member>  $members
     */
    private function declareRelays(Gala $gala, Collection $members): void
    {
        $relayEvents = $gala->events()->where('is_relay', true)->get();
        $byClub = $members->groupBy('organization_id');

        foreach ($relayEvents as $event) {
            foreach ($byClub as $organizationId => $squad) {
                $club = Organization::find($organizationId);

                if ($club === null) {
                    continue;
                }

                $eligible = $squad
                    ->filter(fn (Member $member): bool => $event->acceptsAge(
                        $member->date_of_birth,
                        $gala->effectiveAgeUpDate(),
                    ))
                    ->values();

                foreach (['A', 'B'] as $letter) {
                    $legs = $this->pickLegs($event, $eligible, $organizationId);

                    if ($legs === []) {
                        break;
                    }

                    if ($this->relays->problems($event, $legs, (int) $organizationId, $letter) !== []) {
                        break;
                    }

                    $this->relays->declare(
                        $event,
                        (int) $organizationId,
                        $letter,
                        $legs,
                        random_int(118_000, 160_000),
                    );
                }
            }

            $this->relays->seed($event->fresh(), $this->seeding);
        }
    }

    /**
     * Four swimmers who are not already in a team for this event, respecting
     * the two-boys-two-girls rule for a mixed relay.
     *
     * @param  Collection<int, Member>  $eligible
     * @return array<int, int>
     */
    private function pickLegs(GalaEvent $event, Collection $eligible, int|string $organizationId): array
    {
        $taken = $event->relayTeams()
            ->where('organization_id', $organizationId)
            ->with('swimmers')
            ->get()
            ->flatMap(fn ($team): Collection => $team->swimmers->pluck('member_id'));

        $available = $eligible->reject(fn (Member $member): bool => $taken->contains($member->getKey()));

        if ($event->genderRestriction() === 'mixed') {
            $boys = $available->where('gender', 'male')->take(2);
            $girls = $available->where('gender', 'female')->take(2);

            return $boys->count() === 2 && $girls->count() === 2
                ? $boys->concat($girls)->pluck('id')->all()
                : [];
        }

        $squad = $available->where('gender', $event->genderRestriction())->take(4);

        return $squad->count() === 4 ? $squad->pluck('id')->all() : [];
    }
}

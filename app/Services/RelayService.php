<?php

namespace App\Services;

use App\Enums\Stroke;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Organization;
use App\Models\RelayTeam;
use App\Models\RelayTeamMember;
use App\Models\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Relay teams: declaring them, checking they are legal, and seeding them.
 *
 * A relay is entered as a team rather than as four swims, so the rules that
 * apply to it are about the make-up of the team: the right number of legs, no
 * swimmer twice, everyone inside the age band, the right split of boys and
 * girls in a mixed relay, and a declared order for a medley.
 */
class RelayService
{
    /**
     * The stroke each leg swims in a medley relay, in order.
     *
     * @var array<int, string>
     */
    public const MEDLEY_ORDER = ['backstroke', 'breaststroke', 'butterfly', 'freestyle'];

    /**
     * How many teams one club may enter in a single relay event.
     */
    public const MAX_TEAMS_PER_CLUB = 2;

    /**
     * Everything wrong with a proposed team, in plain language. An empty list
     * means it may be entered.
     *
     * @param  array<int, int>  $memberIds  swimmers in leg order
     * @param  array<int, bool>  $borrowed  keyed by member id
     * @return array<int, string>
     */
    public function problems(
        GalaEvent $event,
        array $memberIds,
        int $organizationId,
        string $letter = 'A',
        array $borrowed = [],
    ): array {
        $problems = [];

        if (! $event->is_relay) {
            return ['Event '.$event->event_number.' is not a relay.'];
        }

        $legs = $event->relay_legs ?? 4;

        if (count($memberIds) !== $legs) {
            $problems[] = 'A team for this event needs exactly '.$legs.' swimmers.';
        }

        if (count($memberIds) !== count(array_unique($memberIds))) {
            $problems[] = 'A swimmer can only swim one leg of a relay.';
        }

        $members = Member::query()->whereKey($memberIds)->get();
        $ageUpDate = $event->gala->effectiveAgeUpDate();

        foreach ($members as $member) {
            if (! $event->acceptsAge($member->date_of_birth, $ageUpDate)) {
                $problems[] = $member->fullName().' is outside the '.$event->age_group.' age band.';
            }
        }

        $restriction = $event->genderRestriction();

        if ($restriction === 'mixed') {
            $boys = $members->where('gender', 'male')->count();
            $girls = $members->where('gender', 'female')->count();

            if ($boys !== $girls) {
                $problems[] = 'A mixed relay must be an even split of boys and girls.';
            }
        } else {
            foreach ($members->where('gender', '!=', $restriction) as $member) {
                $problems[] = $member->fullName().' cannot swim in a '.$event->gender.' relay.';
            }
        }

        $alreadyEntered = RelayTeamMember::query()
            ->whereIn('member_id', $memberIds)
            ->whereHas('team', fn ($query) => $query->where('gala_event_id', $event->getKey()))
            ->with('member')
            ->get();

        foreach ($alreadyEntered as $existing) {
            $problems[] = $existing->member->fullName().' is already in a team for this event.';
        }

        $siblings = RelayTeam::query()
            ->where('gala_event_id', $event->getKey())
            ->where('organization_id', $organizationId)
            ->where('letter', '!=', $letter)
            ->get();

        if ($siblings->count() >= self::MAX_TEAMS_PER_CLUB) {
            $problems[] = 'A club may enter at most '.self::MAX_TEAMS_PER_CLUB.' teams in a relay event.';
        }

        // Borrowing exists so a club short of swimmers can still field a team.
        // A club that has already put up a full A relay may not then borrow to
        // pad out a second one.
        $hasBorrowed = array_any(
            array_values($memberIds),
            fn (int $memberId): bool => $borrowed[$memberId] ?? false,
        );

        if ($hasBorrowed && $letter !== 'A' && $siblings->contains('letter', 'A')) {
            $problems[] = 'Borrowing is not allowed once the club has entered an A relay in this event.';
        }

        return array_values(array_unique($problems));
    }

    /**
     * Declare a team. The caller is expected to have checked problems() first.
     *
     * @param  array<int, int>  $memberIds  swimmers in leg order
     * @param  array<int, bool>  $borrowed  keyed by member id
     */
    public function declare(
        GalaEvent $event,
        int $organizationId,
        string $letter,
        array $memberIds,
        ?int $entryTimeMs = null,
        array $borrowed = [],
    ): RelayTeam {
        return DB::transaction(function () use ($event, $organizationId, $letter, $memberIds, $entryTimeMs, $borrowed): RelayTeam {
            $club = Organization::query()->findOrFail($organizationId);

            $team = RelayTeam::updateOrCreate(
                [
                    'gala_event_id' => $event->getKey(),
                    'organization_id' => $organizationId,
                    'letter' => $letter,
                ],
                [
                    'gala_id' => $event->gala_id,
                    'name' => $club->short_name ?? $club->name,
                    'entry_time_ms' => $entryTimeMs,
                    'status' => 'entered',
                ],
            );

            $team->swimmers()->delete();

            foreach (array_values($memberIds) as $index => $memberId) {
                $leg = $index + 1;

                $team->swimmers()->create([
                    'member_id' => $memberId,
                    'leg' => $leg,
                    'stroke' => $this->strokeForLeg($event, $leg),
                    'is_borrowed' => $borrowed[$memberId] ?? false,
                ]);
            }

            return $team->fresh(['swimmers.member']);
        });
    }

    /**
     * The stroke a leg swims. Medley relays have a fixed order; a freestyle
     * relay leaves it open.
     */
    public function strokeForLeg(GalaEvent $event, int $leg): ?string
    {
        if ($event->stroke !== Stroke::MedleyRelay) {
            return null;
        }

        return self::MEDLEY_ORDER[$leg - 1] ?? null;
    }

    /**
     * Seed a relay event, using the same centre-out lanes and slowest-heat-
     * first order as an individual event.
     *
     * @return array{heats: int, teams: int, lanes: int}
     */
    public function seed(GalaEvent $event, SeedingService $seeding, ?int $lanes = null): array
    {
        $lanes ??= $event->gala->laneCount();

        $teams = $this->seedableTeams($event);

        if ($teams->isEmpty()) {
            $event->forceFill(['seeded_at' => now()])->save();

            return ['heats' => 0, 'teams' => 0, 'lanes' => $lanes];
        }

        $sizes = $seeding->heatSizes($teams->count(), $lanes);
        $heatCount = count($sizes);
        $laneOrder = $seeding->laneOrder($lanes);
        $position = 0;

        DB::transaction(function () use ($teams, $sizes, $heatCount, $laneOrder, &$position, $event): void {
            foreach ($sizes as $index => $size) {
                $heatNumber = $heatCount - $index;

                foreach ($teams->slice($position, $size)->values() as $seat => $team) {
                    $team->forceFill([
                        'heat' => $heatNumber,
                        'lane' => $laneOrder[$seat] ?? null,
                    ])->save();
                }

                $position += $size;
            }

            $event->forceFill(['seeded_at' => now()])->save();
        });

        return ['heats' => $heatCount, 'teams' => $teams->count(), 'lanes' => $lanes];
    }

    /**
     * Teams in seeding order, fastest first, no-time teams last.
     *
     * @return Collection<int, RelayTeam>
     */
    public function seedableTeams(GalaEvent $event): Collection
    {
        return RelayTeam::query()
            ->where('gala_event_id', $event->getKey())
            ->whereIn('status', ['entered', 'confirmed'])
            ->with(['swimmers.member', 'club:id,name,short_name'])
            ->get()
            ->sortBy([
                fn (RelayTeam $a, RelayTeam $b): int => ($a->entry_time_ms === null ? 1 : 0) <=> ($b->entry_time_ms === null ? 1 : 0),
                fn (RelayTeam $a, RelayTeam $b): int => ($a->entry_time_ms ?? PHP_INT_MAX) <=> ($b->entry_time_ms ?? PHP_INT_MAX),
            ])
            ->values();
    }

    /**
     * The heat sheet for a relay event, in the same shape as an individual
     * event's so one component can print either.
     *
     * @return array<int, array{heat: int, lanes: array<int, array<string, mixed>>}>
     */
    public function heatSheet(GalaEvent $event): array
    {
        return RelayTeam::query()
            ->where('gala_event_id', $event->getKey())
            ->whereNotNull('heat')
            ->whereIn('status', ['entered', 'confirmed'])
            ->with(['club:id,name', 'swimmers.member:id,first_name,last_name'])
            ->orderBy('heat')
            ->orderBy('lane')
            ->get()
            ->groupBy('heat')
            ->map(fn (Collection $heat, int|string $number): array => [
                'heat' => (int) $number,
                'lanes' => $heat->map(fn (RelayTeam $team): array => [
                    'lane' => $team->lane,
                    'swimmer' => $team->displayName(),
                    'club' => $team->club->name,
                    'entry_time' => $team->entryTime(),
                    'legs' => $team->swimmers
                        ->map(fn (RelayTeamMember $swimmer): string => $swimmer->member->fullName())
                        ->all(),
                ])->values()->all(),
            ])
            ->sortBy('heat')
            ->values()
            ->all();
    }

    /**
     * Record a relay swim. Points go to the club only when no leg was
     * borrowed, which is the handbook's rule.
     *
     * @param  array{time_ms?: int|null, status?: string, dq_code?: string|null, dq_reason?: string|null}  $data
     */
    public function record(RelayTeam $team, array $data, ?int $recordedBy = null): Result
    {
        $status = $data['status'] ?? 'official';
        $timeMs = $status === 'official' ? ($data['time_ms'] ?? null) : null;

        return DB::transaction(function () use ($team, $status, $timeMs, $data, $recordedBy): Result {
            $result = Result::updateOrCreate(
                ['relay_team_id' => $team->getKey()],
                [
                    'gala_id' => $team->gala_id,
                    'gala_event_id' => $team->gala_event_id,
                    'member_id' => null,
                    'organization_id' => $team->organization_id,
                    'time_ms' => $timeMs,
                    'heat' => $team->heat,
                    'lane' => $team->lane,
                    'status' => $status,
                    'dq_code' => $data['dq_code'] ?? null,
                    'dq_reason' => $data['dq_reason'] ?? null,
                    'recorded_by' => $recordedBy,
                ],
            );

            $team->forceFill([
                'status' => $status === 'dns' ? 'no_show' : 'confirmed',
            ])->save();

            return $result;
        });
    }
}

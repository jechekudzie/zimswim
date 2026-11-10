<?php

use App\Enums\Course;
use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\MemberRegistration;
use App\Models\Organization;
use App\Models\RelayTeam;
use App\Models\Season;
use App\Models\Venue;
use App\Services\RelayService;
use App\Services\ResultService;
use App\Services\SeedingService;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->otherClub = Organization::factory()->club($this->province)->create();
    $this->season = Season::factory()->create(['age_up_date' => '2026-12-31']);
    $this->venue = Venue::factory()->create(['lanes' => 8]);

    $this->gala = Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $this->venue->getKey(),
        'course' => Course::LongCourse,
        'status' => GalaStatus::EntriesOpen,
        'entries_close_at' => now()->addWeek(),
        'age_up_date' => '2026-12-31',
    ]);

    $this->relay = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 50,
        'gender' => 'girls',
        'age_group' => '11/12',
        'min_age' => 11,
        'max_age' => 12,
        'stroke' => Stroke::FreestyleRelay,
        'distance' => 200,
        'is_relay' => true,
        'relay_legs' => 4,
    ]);

    $this->relays = app(RelayService::class);
});

function relaySwimmer(string $name, string $gender, Organization $club, $context, string $dob = '2015-01-01'): Member
{
    $member = Member::factory()->create([
        'organization_id' => $club->getKey(),
        'first_name' => $name,
        'date_of_birth' => $dob,
        'gender' => $gender,
        'status' => MemberStatus::Active,
    ]);

    MemberRegistration::factory()->create([
        'member_id' => $member->getKey(),
        'season_id' => $context->season->getKey(),
        'organization_id' => $club->getKey(),
    ]);

    return $member;
}

function fourGirls($context): array
{
    return collect(['Ada', 'Bea', 'Cia', 'Dia'])
        ->map(fn (string $name): Member => relaySwimmer($name, 'female', $context->club, $context))
        ->pluck('id')
        ->all();
}

it('declares a team with a swimmer on each leg', function (): void {
    $ids = fourGirls($this);

    expect($this->relays->problems($this->relay, $ids, $this->club->getKey()))->toBe([]);

    $team = $this->relays->declare($this->relay, $this->club->getKey(), 'A', $ids, 131_450);

    expect($team->swimmers)->toHaveCount(4);
    expect($team->swimmers->pluck('leg')->all())->toBe([1, 2, 3, 4]);
    expect($team->swimmers->pluck('member_id')->all())->toBe($ids);
    expect($team->entryTime())->toBe('2:11.45');
    expect($team->scoresPoints())->toBeTrue();
});

it('rejects a team with the wrong number of legs', function (): void {
    $ids = array_slice(fourGirls($this), 0, 3);

    expect($this->relays->problems($this->relay, $ids, $this->club->getKey()))
        ->toContain('A team for this event needs exactly 4 swimmers.');
});

it('rejects the same swimmer on two legs', function (): void {
    $ids = fourGirls($this);
    $ids[3] = $ids[0];

    expect($this->relays->problems($this->relay, $ids, $this->club->getKey()))
        ->toContain('A swimmer can only swim one leg of a relay.');
});

it('rejects a swimmer outside the age band', function (): void {
    $ids = fourGirls($this);
    $tooOld = relaySwimmer('Grown', 'female', $this->club, $this, '2008-01-01');
    $ids[3] = $tooOld->getKey();

    expect($this->relays->problems($this->relay, $ids, $this->club->getKey()))
        ->toContain('Grown '.$tooOld->last_name.' is outside the 11/12 age band.');
});

it('rejects a boy in a girls relay', function (): void {
    $ids = fourGirls($this);
    $boy = relaySwimmer('Brian', 'male', $this->club, $this);
    $ids[3] = $boy->getKey();

    expect($this->relays->problems($this->relay, $ids, $this->club->getKey()))
        ->toContain('Brian '.$boy->last_name.' cannot swim in a girls relay.');
});

it('requires an even split of boys and girls in a mixed relay', function (): void {
    $mixed = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 51,
        'gender' => 'mixed',
        'age_group' => '11/12',
        'min_age' => 11,
        'max_age' => 12,
        'stroke' => Stroke::MedleyRelay,
        'distance' => 200,
        'is_relay' => true,
        'relay_legs' => 4,
    ]);

    $threeGirlsOneBoy = [
        relaySwimmer('Ada', 'female', $this->club, $this)->getKey(),
        relaySwimmer('Bea', 'female', $this->club, $this)->getKey(),
        relaySwimmer('Cia', 'female', $this->club, $this)->getKey(),
        relaySwimmer('Dan', 'male', $this->club, $this)->getKey(),
    ];

    expect($this->relays->problems($mixed, $threeGirlsOneBoy, $this->club->getKey()))
        ->toContain('A mixed relay must be an even split of boys and girls.');

    $twoAndTwo = [
        relaySwimmer('Eve', 'female', $this->club, $this)->getKey(),
        relaySwimmer('Fay', 'female', $this->club, $this)->getKey(),
        relaySwimmer('Gus', 'male', $this->club, $this)->getKey(),
        relaySwimmer('Hal', 'male', $this->club, $this)->getKey(),
    ];

    expect($this->relays->problems($mixed, $twoAndTwo, $this->club->getKey()))->toBe([]);
});

it('fixes the stroke order of a medley relay and leaves a freestyle relay open', function (): void {
    $medley = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 52,
        'gender' => 'girls',
        'age_group' => '11/12',
        'min_age' => 11,
        'max_age' => 12,
        'stroke' => Stroke::MedleyRelay,
        'distance' => 200,
        'is_relay' => true,
        'relay_legs' => 4,
    ]);

    $team = $this->relays->declare($medley, $this->club->getKey(), 'A', fourGirls($this));

    expect($team->swimmers->pluck('stroke')->all())
        ->toBe(['backstroke', 'breaststroke', 'butterfly', 'freestyle']);

    $free = $this->relays->declare($this->relay, $this->club->getKey(), 'A', fourGirls($this));

    expect($free->swimmers->pluck('stroke')->filter()->all())->toBe([]);
});

it('stops a swimmer being entered in two teams for the same event', function (): void {
    $first = fourGirls($this);
    $this->relays->declare($this->relay, $this->club->getKey(), 'A', $first);

    $second = fourGirls($this);
    $second[3] = $first[0];

    expect($this->relays->problems($this->relay, $second, $this->club->getKey(), 'B'))
        ->toContain(Member::find($first[0])->fullName().' is already in a team for this event.');
});

it('allows a club at most two teams in one event', function (): void {
    $this->relays->declare($this->relay, $this->club->getKey(), 'A', fourGirls($this));
    $this->relays->declare($this->relay, $this->club->getKey(), 'B', fourGirls($this));

    expect($this->relays->problems($this->relay, fourGirls($this), $this->club->getKey(), 'C'))
        ->toContain('A club may enter at most 2 teams in a relay event.');
});

it('refuses borrowing once the club has entered an A relay', function (): void {
    $this->relays->declare($this->relay, $this->club->getKey(), 'A', fourGirls($this));

    $borrowedSwimmer = relaySwimmer('Guest', 'female', $this->otherClub, $this);
    $ids = fourGirls($this);
    $ids[3] = $borrowedSwimmer->getKey();

    $problems = $this->relays->problems(
        $this->relay,
        $ids,
        $this->club->getKey(),
        'B',
        [$borrowedSwimmer->getKey() => true],
    );

    expect($problems)->toContain('Borrowing is not allowed once the club has entered an A relay in this event.');
});

it('permits borrowing for a club that has no A relay', function (): void {
    $borrowedSwimmer = relaySwimmer('Guest', 'female', $this->otherClub, $this);
    $ids = fourGirls($this);
    $ids[3] = $borrowedSwimmer->getKey();

    $problems = $this->relays->problems(
        $this->relay,
        $ids,
        $this->club->getKey(),
        'A',
        [$borrowedSwimmer->getKey() => true],
    );

    expect($problems)->toBe([]);
});

it('awards a borrowing team medals but no points', function (): void {
    $borrowedSwimmer = relaySwimmer('Guest', 'female', $this->otherClub, $this);
    $borrowingIds = fourGirls($this);
    $borrowingIds[3] = $borrowedSwimmer->getKey();

    $borrowing = $this->relays->declare(
        $this->relay,
        $this->club->getKey(),
        'A',
        $borrowingIds,
        null,
        [$borrowedSwimmer->getKey() => true],
    );

    $clean = $this->relays->declare(
        $this->relay,
        $this->otherClub->getKey(),
        'A',
        collect(['Ivy', 'Joy', 'Kim', 'Lea'])
            ->map(fn (string $name): Member => relaySwimmer($name, 'female', $this->otherClub, $this))
            ->pluck('id')
            ->all(),
    );

    expect($borrowing->scoresPoints())->toBeFalse();
    expect($clean->scoresPoints())->toBeTrue();

    // The borrowing team wins, so it takes the place but none of the points.
    $this->relays->record($borrowing, ['time_ms' => 130_000]);
    $this->relays->record($clean, ['time_ms' => 135_000]);

    app(ResultService::class)->placeEvent($this->relay->fresh());

    $winner = $borrowing->fresh()->event->results()->where('relay_team_id', $borrowing->getKey())->sole();
    $runnerUp = $clean->fresh()->event->results()->where('relay_team_id', $clean->getKey())->sole();

    expect($winner->place)->toBe(1);
    expect($winner->points)->toBe(0);
    expect($runnerUp->place)->toBe(2);
    expect($runnerUp->points)->toBe(8);
});

it('scores relays on the handbook table of sixteen, eight and four', function (): void {
    $clubs = [$this->club, $this->otherClub, Organization::factory()->club($this->province)->create()];
    $times = [120_000, 125_000, 130_000];

    foreach ($clubs as $index => $club) {
        $team = $this->relays->declare(
            $this->relay,
            $club->getKey(),
            'A',
            collect(range(1, 4))
                ->map(fn (int $leg): Member => relaySwimmer("S{$index}{$leg}", 'female', $club, $this))
                ->pluck('id')
                ->all(),
        );

        $this->relays->record($team, ['time_ms' => $times[$index]]);
    }

    app(ResultService::class)->placeEvent($this->relay->fresh());

    expect($this->relay->fresh()->results()->orderBy('place')->pluck('points')->all())
        ->toBe([16, 8, 4]);
});

it('seeds relay teams centre-out with the fastest in the final heat', function (): void {
    $clubs = collect(range(1, 10))
        ->map(fn (int $index): Organization => Organization::factory()->club($this->province)->create());

    foreach ($clubs as $index => $club) {
        $this->relays->declare(
            $this->relay,
            $club->getKey(),
            'A',
            collect(range(1, 4))
                ->map(fn (int $leg): Member => relaySwimmer("T{$index}{$leg}", 'female', $club, $this))
                ->pluck('id')
                ->all(),
            120_000 + $index * 1_000,
        );
    }

    $outcome = $this->relays->seed($this->relay, app(SeedingService::class), 8);

    expect($outcome['teams'])->toBe(10);
    expect($outcome['heats'])->toBe(2);

    // Ten teams over eight lanes would leave two alone, so the field is spread.
    expect(RelayTeam::query()->where('heat', 1)->count())->toBe(5);
    expect(RelayTeam::query()->where('heat', 2)->count())->toBe(5);

    $fastest = RelayTeam::query()->orderBy('entry_time_ms')->first();
    expect($fastest->heat)->toBe(2);
    expect($fastest->lane)->toBe(4);
});

it('lets a club administrator declare a team through the screen', function (): void {
    $user = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);
    $ids = fourGirls($this);

    $this->actingAs($user)
        ->post(route('admin.galas.relays.store', $this->gala), [
            'gala_event_id' => $this->relay->getKey(),
            'organization_id' => $this->club->getKey(),
            'letter' => 'A',
            'member_ids' => $ids,
            'entry_time' => '2:11.45',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(RelayTeam::query()->where('organization_id', $this->club->getKey())->count())->toBe(1);
});

it('does not let a club declare a team for another club', function (): void {
    $user = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($user)
        ->post(route('admin.galas.relays.store', $this->gala), [
            'gala_event_id' => $this->relay->getKey(),
            'organization_id' => $this->otherClub->getKey(),
            'letter' => 'A',
            'member_ids' => fourGirls($this),
        ])
        ->assertSessionHasErrors('organization_id');
});

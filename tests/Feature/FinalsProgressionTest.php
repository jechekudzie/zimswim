<?php

use App\Enums\Course;
use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\MemberRegistration;
use App\Models\Result;
use App\Models\Season;
use App\Models\Venue;
use App\Services\FinalsService;
use App\Support\Roles;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->season = Season::factory()->create(['age_up_date' => '2026-12-31']);
    $this->venue = Venue::factory()->create(['lanes' => 8]);

    $this->gala = Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $this->venue->getKey(),
        'course' => Course::LongCourse,
        'status' => GalaStatus::InProgress,
        'age_up_date' => '2026-12-31',
    ]);

    $this->heats = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 1,
        'gender' => 'girls',
        'age_group' => '11/12',
        'min_age' => 11,
        'max_age' => 12,
        'stroke' => Stroke::Freestyle,
        'distance' => 100,
        'round' => 'heats',
    ]);

    $this->final = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 2,
        'gender' => 'girls',
        'age_group' => '11/12',
        'min_age' => 11,
        'max_age' => 12,
        'stroke' => Stroke::Freestyle,
        'distance' => 100,
        'round' => 'final',
        'parent_event_id' => $this->heats->getKey(),
        'qualifiers' => 8,
    ]);

    $this->finals = app(FinalsService::class);
});

/**
 * Swim a heat: one swimmer per time, in the order given.
 */
function swimHeats(array $timesMs, $context): array
{
    $members = [];

    foreach ($timesMs as $index => $timeMs) {
        $member = Member::factory()->create([
            'organization_id' => $context->club->getKey(),
            'first_name' => 'Swimmer'.$index,
            'date_of_birth' => '2015-01-01',
            'gender' => 'female',
            'status' => MemberStatus::Active,
        ]);

        MemberRegistration::factory()->create([
            'member_id' => $member->getKey(),
            'season_id' => $context->season->getKey(),
            'organization_id' => $context->club->getKey(),
        ]);

        $entry = GalaEntry::factory()->create([
            'gala_id' => $context->gala->getKey(),
            'gala_event_id' => $context->heats->getKey(),
            'member_id' => $member->getKey(),
            'organization_id' => $context->club->getKey(),
            'status' => 'confirmed',
        ]);

        Result::factory()->create([
            'gala_id' => $context->gala->getKey(),
            'gala_event_id' => $context->heats->getKey(),
            'gala_entry_id' => $entry->getKey(),
            'member_id' => $member->getKey(),
            'organization_id' => $context->club->getKey(),
            'time_ms' => $timeMs,
            'status' => 'official',
        ]);

        $members[$timeMs] = $member;
    }

    return $members;
}

it('promotes the fastest eight from the heats and seeds the fastest into the centre lane', function (): void {
    $times = [];

    for ($index = 0; $index < 12; $index++) {
        $times[] = 60_000 + $index * 500;
    }

    swimHeats($times, $this);

    $outcome = $this->finals->promote($this->final);

    expect($outcome['entered'])->toBe(8);
    expect($outcome['heats'])->toBe(1);
    expect($outcome['swim_off'])->toBe(0);

    $entries = $this->final->entries()->orderBy('entry_time_ms')->get();
    expect($entries)->toHaveCount(8);
    expect($entries->first()->entry_time_ms)->toBe(60_000);
    expect($entries->first()->lane)->toBe(4);
    expect($entries->last()->entry_time_ms)->toBe(63_500);
});

it('takes everyone through when fewer swam the heats than there are places', function (): void {
    swimHeats([60_000, 61_000, 62_000], $this);

    $outcome = $this->finals->promote($this->final);

    expect($outcome['entered'])->toBe(3);
    expect($outcome['swim_off'])->toBe(0);
});

it('holds the last place open and names the swim-off when the cut-off time is tied', function (): void {
    // Seven clear qualifiers, then three swimmers tied on the eighth time.
    $times = [60_000, 60_500, 61_000, 61_500, 62_000, 62_500, 63_000];
    $tied = [64_000, 64_000, 64_000];

    swimHeats([...$times, ...$tied], $this);

    $assessment = $this->finals->assess($this->final);

    expect($assessment['qualifiers'])->toHaveCount(7);
    expect($assessment['swim_off'])->toHaveCount(3);
    expect($assessment['contested_places'])->toBe(1);

    $outcome = $this->finals->promote($this->final);

    // Only the seven who are clearly through are entered; the eighth lane
    // waits for the swim-off.
    expect($outcome['entered'])->toBe(7);
    expect($outcome['swim_off'])->toBe(3);
    expect($this->final->entries()->count())->toBe(7);
});

it('takes every tied swimmer when they all fit inside the places left', function (): void {
    // Six clear, then two tied for the last two places.
    swimHeats([60_000, 60_500, 61_000, 61_500, 62_000, 62_500, 64_000, 64_000], $this);

    $assessment = $this->finals->assess($this->final);

    expect($assessment['qualifiers'])->toHaveCount(8);
    expect($assessment['swim_off'])->toHaveCount(0);
});

it('ignores a disqualified heat swim when working out who is through', function (): void {
    $members = swimHeats([60_000, 61_000, 62_000], $this);

    Result::query()
        ->where('member_id', $members[60_000]->getKey())
        ->update(['status' => 'dq', 'time_ms' => null, 'dq_code' => '5.2']);

    $assessment = $this->finals->assess($this->final);

    expect($assessment['qualifiers'])->toHaveCount(2);
    expect($assessment['qualifiers']->pluck('member_id')->all())
        ->not->toContain($members[60_000]->getKey());
});

it('can be run again after a heat time is corrected', function (): void {
    $members = swimHeats([60_000, 61_000, 62_000], $this);

    $this->finals->promote($this->final);
    expect($this->final->entries()->count())->toBe(3);

    Result::query()
        ->where('member_id', $members[62_000]->getKey())
        ->update(['status' => 'dq', 'time_ms' => null]);

    $this->finals->promote($this->final->fresh());

    expect($this->final->fresh()->entries()->count())->toBe(2);
});

it('refuses to promote from heats with no times yet', function (): void {
    $this->finals->promote($this->final);
})->throws(ValidationException::class);

it('refuses to promote an event that has no heats behind it', function (): void {
    $standalone = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 9,
        'parent_event_id' => null,
    ]);

    $this->finals->promote($standalone);
})->throws(ValidationException::class);

it('shows the finals screen with the qualifiers before anything is written', function (): void {
    swimHeats([60_000, 61_000, 62_000], $this);
    $user = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($user)
        ->get(route('admin.galas.finals.index', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/galas/finals')
            ->where('finals.0.qualifiers', fn ($qualifiers) => count($qualifiers) === 3)
            ->where('finals.0.entered', 0));
});

it('promotes through the screen for a user who may record results', function (): void {
    swimHeats([60_000, 61_000, 62_000], $this);
    $user = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($user)
        ->post(route('admin.galas.finals.promote', $this->gala), [
            'gala_event_id' => $this->final->getKey(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->final->entries()->count())->toBe(3);
});

it('does not let a club administrator promote a final', function (): void {
    swimHeats([60_000], $this);
    $user = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($user)
        ->post(route('admin.galas.finals.promote', $this->gala), [
            'gala_event_id' => $this->final->getKey(),
        ])
        ->assertForbidden();
});

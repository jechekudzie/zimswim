<?php

use App\Enums\Course;
use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\RecordStatus;
use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\MemberRegistration;
use App\Models\Result;
use App\Models\Season;
use App\Models\SwimRecord;
use App\Models\Venue;
use App\Services\ResultService;
use App\Services\SeedingService;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->season = Season::factory()->create();
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

    $this->event = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gender' => 'girls',
        'age_group' => '11/12',
        'min_age' => 11,
        'max_age' => 12,
        'stroke' => Stroke::Freestyle,
        'distance' => 50,
    ]);
});

function enterSwimmer(string $firstName, string $dob, ?int $timeMs, $context): GalaEntry
{
    $member = Member::factory()->create([
        'organization_id' => $context->club->getKey(),
        'first_name' => $firstName,
        'date_of_birth' => $dob,
        'gender' => 'female',
        'status' => MemberStatus::Active,
    ]);

    MemberRegistration::factory()->create([
        'member_id' => $member->getKey(),
        'season_id' => $context->season->getKey(),
        'organization_id' => $context->club->getKey(),
    ]);

    return GalaEntry::factory()->create([
        'gala_id' => $context->gala->getKey(),
        'gala_event_id' => $context->event->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $context->club->getKey(),
        'entry_time_ms' => $timeMs,
        'status' => 'entered',
    ]);
}

it('seeds the fastest swimmers into the last heat and the centre lane', function (): void {
    foreach (range(1, 10) as $index) {
        enterSwimmer('Swimmer'.$index, '2014-05-01', 30_000 + ($index * 500), $this);
    }

    app(SeedingService::class)->seed($this->event->fresh(), 8);

    $fastest = GalaEntry::where('entry_time_ms', 30_500)->first();
    $slowest = GalaEntry::where('entry_time_ms', 35_000)->first();

    // Two heats: the slow heat swims first, the fast heat last.
    expect($fastest->heat)->toBe(2)
        ->and($fastest->lane)->toBe(4)
        ->and($slowest->heat)->toBe(1);
});

it('puts swimmers with no entry time in the slowest heat', function (): void {
    foreach (range(1, 8) as $index) {
        enterSwimmer('Timed'.$index, '2014-05-01', 30_000 + ($index * 100), $this);
    }

    $noTime = enterSwimmer('NoTime', '2014-05-01', null, $this);

    app(SeedingService::class)->seed($this->event->fresh(), 8);

    expect($noTime->fresh()->heat)->toBe(1);
});

it('records a time and marks it as a personal best', function (): void {
    $entry = enterSwimmer('Tanaka', '2014-05-01', 32_000, $this);
    $official = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($official)
        ->from(route('admin.galas.results.edit', [$this->gala, $this->event]))
        ->post(route('admin.galas.results.store', [$this->gala, $this->event]), [
            'results' => [
                ['gala_entry_id' => $entry->getKey(), 'time' => '31.50', 'status' => 'official'],
            ],
        ])
        ->assertRedirect();

    $result = Result::where('gala_entry_id', $entry->getKey())->firstOrFail();

    expect($result->time_ms)->toBe(31_500)
        ->and($result->is_personal_best)->toBeTrue()
        ->and($result->place)->toBe(1);
});

it('proposes a record when a swim beats the standing mark', function (): void {
    SwimRecord::factory()->create([
        'scope' => 'national',
        'course' => Course::LongCourse,
        'gender' => 'female',
        'age_group' => '11/12',
        'stroke' => Stroke::Freestyle,
        'distance' => 50,
        'time_ms' => 31_000,
        'status' => RecordStatus::Ratified,
    ]);

    $entry = enterSwimmer('Ruva', '2014-05-01', 31_500, $this);
    $official = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($official)
        ->from(route('admin.galas.results.edit', [$this->gala, $this->event]))
        ->post(route('admin.galas.results.store', [$this->gala, $this->event]), [
            'results' => [
                ['gala_entry_id' => $entry->getKey(), 'time' => '30.80', 'status' => 'official'],
            ],
        ])
        ->assertRedirect();

    $proposed = SwimRecord::awaitingRatification()->first();

    expect(Result::first()->record_flagged)->toBeTrue()
        ->and($proposed)->not->toBeNull()
        ->and($proposed->time_ms)->toBe(30_800)
        ->and($proposed->holder_name)->toBe('Ruva '.$proposed->member->last_name);
});

it('does not propose a record for a swim slower than the standing mark', function (): void {
    SwimRecord::factory()->create([
        'course' => Course::LongCourse,
        'gender' => 'female',
        'age_group' => '11/12',
        'stroke' => Stroke::Freestyle,
        'distance' => 50,
        'time_ms' => 29_000,
    ]);

    $entry = enterSwimmer('Chiedza', '2014-05-01', 31_500, $this);
    $official = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($official)
        ->from(route('admin.galas.results.edit', [$this->gala, $this->event]))
        ->post(route('admin.galas.results.store', [$this->gala, $this->event]), [
            'results' => [
                ['gala_entry_id' => $entry->getKey(), 'time' => '30.80', 'status' => 'official'],
            ],
        ]);

    expect(Result::first()->record_flagged)->toBeFalse()
        ->and(SwimRecord::awaitingRatification()->count())->toBe(0);
});

it('places a disqualified swimmer nowhere and keeps the rule code', function (): void {
    $first = enterSwimmer('Anesu', '2014-05-01', 31_000, $this);
    $second = enterSwimmer('Mazvita', '2014-05-01', 32_000, $this);

    $official = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($official)
        ->from(route('admin.galas.results.edit', [$this->gala, $this->event]))
        ->post(route('admin.galas.results.store', [$this->gala, $this->event]), [
            'results' => [
                ['gala_entry_id' => $first->getKey(), 'status' => 'dq', 'dq_code' => '7.4'],
                ['gala_entry_id' => $second->getKey(), 'time' => '32.10', 'status' => 'official'],
            ],
        ])
        ->assertRedirect();

    $dq = Result::where('gala_entry_id', $first->getKey())->firstOrFail();
    $winner = Result::where('gala_entry_id', $second->getKey())->firstOrFail();

    expect($dq->status)->toBe('dq')
        ->and($dq->dq_code)->toBe('7.4')
        ->and($dq->time_ms)->toBeNull()
        ->and($dq->place)->toBeNull()
        ->and($winner->place)->toBe(1);
});

it('publishes an event so it appears on the public results page', function (): void {
    $entry = enterSwimmer('Rufaro', '2014-05-01', 31_000, $this);
    $official = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($official)
        ->from(route('admin.galas.results.edit', [$this->gala, $this->event]))
        ->post(route('admin.galas.results.store', [$this->gala, $this->event]), [
            'results' => [
                ['gala_entry_id' => $entry->getKey(), 'time' => '31.00', 'status' => 'official'],
            ],
        ]);

    $this->actingAs($official)
        ->from(route('admin.galas.results.index', $this->gala))
        ->post(route('admin.galas.results.publish', [$this->gala, $this->event]))
        ->assertRedirect();

    $this->get(route('results.show', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/gala-results')
            ->count('events', 1)
            ->where('events.0.results.0.time', '31.00'));
});

it('keeps unpublished events off the public results page', function (): void {
    enterSwimmer('Hidden', '2014-05-01', 31_000, $this);

    $this->get(route('results.show', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('events', 0));
});

it('does not let a club administrator record results', function (): void {
    $entry = enterSwimmer('Tatenda', '2014-05-01', 31_000, $this);
    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.galas.results.store', [$this->gala, $this->event]), [
            'results' => [
                ['gala_entry_id' => $entry->getKey(), 'time' => '31.00', 'status' => 'official'],
            ],
        ])
        ->assertForbidden();
});

it('re-places an event on the corrected times, not on the places it already had', function (): void {
    $slow = enterSwimmer('Slow', '2014-05-01', 35_000, $this);
    $quick = enterSwimmer('Quick', '2014-05-01', 31_000, $this);

    $referee = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);
    $record = fn (array $rows) => $this->actingAs($referee)
        ->post(route('admin.galas.results.store', [$this->gala, $this->event]), ['results' => $rows])
        ->assertRedirect();

    $record([
        ['gala_entry_id' => $slow->getKey(), 'time' => '35.00', 'status' => 'official'],
        ['gala_entry_id' => $quick->getKey(), 'time' => '36.00', 'status' => 'official'],
    ]);

    expect(Result::query()->where('member_id', $slow->member_id)->sole()->place)->toBe(1);

    // The timekeeper misread a watch, so the second swimmer's time is corrected
    // to a winning one. Places must follow the new times.
    $record([
        ['gala_entry_id' => $quick->getKey(), 'time' => '30.00', 'status' => 'official'],
    ]);

    expect(Result::query()->where('member_id', $quick->member_id)->sole()->place)->toBe(1);
    expect(Result::query()->where('member_id', $slow->member_id)->sole()->place)->toBe(2);
});

it('scores individual places on the handbook table', function (): void {
    $times = [31_000, 32_000, 33_000, 34_000, 35_000, 36_000, 37_000, 38_000, 39_000];

    foreach ($times as $index => $timeMs) {
        $entry = enterSwimmer('Scorer'.$index, '2014-05-01', $timeMs, $this);

        app(ResultService::class)->record($entry, ['time_ms' => $timeMs]);
    }

    app(ResultService::class)->placeEvent($this->event->fresh());

    expect($this->event->fresh()->results()->orderBy('place')->pluck('points')->all())
        ->toBe([10, 8, 6, 5, 4, 3, 2, 1, 0]);
});

it('flags only the fastest swim in an event as a record, not every swim', function (): void {
    $winner = enterSwimmer('Winner', '2014-05-01', 31_000, $this);
    $second = enterSwimmer('Second', '2014-05-01', 32_000, $this);
    $third = enterSwimmer('Third', '2014-05-01', 33_000, $this);

    $results = app(ResultService::class);

    foreach ([[$winner, 30_500], [$second, 31_800], [$third, 33_200]] as [$entry, $timeMs]) {
        $results->record($entry, ['time_ms' => $timeMs]);
    }

    $results->placeEvent($this->event->fresh());

    expect(Result::query()->where('record_flagged', true)->count())->toBe(1);
    expect(Result::query()->where('record_flagged', true)->sole()->member_id)
        ->toBe($winner->member_id);
    expect(SwimRecord::query()->where('status', RecordStatus::Pending)->count())->toBe(1);
});

it('moves the record flag when a time is corrected and a different swimmer wins', function (): void {
    $first = enterSwimmer('First', '2014-05-01', 31_000, $this);
    $second = enterSwimmer('Second', '2014-05-01', 32_000, $this);

    $results = app(ResultService::class);
    $results->record($first, ['time_ms' => 30_500]);
    $results->record($second, ['time_ms' => 31_800]);
    $results->placeEvent($this->event->fresh());

    expect(Result::query()->where('record_flagged', true)->sole()->member_id)
        ->toBe($first->member_id);

    // The watch on lane two was misread; the correction makes it the fastest.
    $results->record($second, ['time_ms' => 29_900]);
    $results->placeEvent($this->event->fresh());

    expect(Result::query()->where('record_flagged', true)->count())->toBe(1);
    expect(Result::query()->where('record_flagged', true)->sole()->member_id)
        ->toBe($second->member_id);
});

it('does not flag a swim that is slower than the standing record', function (): void {
    SwimRecord::factory()->create([
        'scope' => 'national',
        'course' => $this->gala->course,
        'gender' => 'female',
        'age_group' => $this->event->age_group,
        'stroke' => $this->event->stroke,
        'distance' => $this->event->distance,
        'time_ms' => 28_000,
        'status' => RecordStatus::Ratified,
    ]);

    $entry = enterSwimmer('Honest', '2014-05-01', 31_000, $this);

    $results = app(ResultService::class);
    $results->record($entry, ['time_ms' => 30_500]);
    $results->placeEvent($this->event->fresh());

    expect(Result::query()->where('record_flagged', true)->count())->toBe(0);
    expect(SwimRecord::query()->where('status', RecordStatus::Pending)->count())->toBe(0);
});

it('does not flag a disqualified swim however fast the time was', function (): void {
    $entry = enterSwimmer('Disqualified', '2014-05-01', 31_000, $this);

    $results = app(ResultService::class);
    $results->record($entry, ['time_ms' => 20_000, 'status' => 'dq', 'dq_code' => '5.2']);
    $results->placeEvent($this->event->fresh());

    expect(Result::query()->where('record_flagged', true)->count())->toBe(0);
});

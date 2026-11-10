<?php

use App\Enums\Course;
use App\Enums\RecordStatus;
use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\Season;
use App\Models\SwimRecord;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
});

it('submits a new record as pending rather than ratified', function (): void {
    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.records.index'))
        ->post(route('admin.records.store'), [
            'scope' => 'national',
            'course' => Course::LongCourse->value,
            'gender' => 'female',
            'age_group' => '11/12',
            'stroke' => Stroke::Freestyle->value,
            'distance' => 50,
            'time' => '29.84',
            'holder_name' => 'Tanaka Moyo',
        ])
        ->assertRedirect();

    $record = SwimRecord::where('holder_name', 'Tanaka Moyo')->firstOrFail();

    expect($record->status)->toBe(RecordStatus::Pending)
        ->and($record->time_ms)->toBe(29_840);
});

it('supersedes the standing record when a new one is ratified', function (): void {
    $standing = SwimRecord::factory()->create([
        'age_group' => '11/12',
        'time_ms' => 30_000,
        'holder_name' => 'Previous holder',
    ]);

    $challenger = SwimRecord::factory()->pending()->create([
        'age_group' => '11/12',
        'time_ms' => 29_840,
        'holder_name' => 'New holder',
    ]);

    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.records.index'))
        ->post(route('admin.records.ratify', $challenger))
        ->assertRedirect();

    expect($challenger->fresh()->status)->toBe(RecordStatus::Ratified)
        ->and($standing->fresh()->status)->toBe(RecordStatus::Superseded);
});

it('leaves records in other age groups untouched when one is ratified', function (): void {
    $otherGroup = SwimRecord::factory()->create([
        'age_group' => '13/14',
        'time_ms' => 28_000,
    ]);

    $challenger = SwimRecord::factory()->pending()->create(['age_group' => '11/12']);

    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.records.index'))
        ->post(route('admin.records.ratify', $challenger))
        ->assertRedirect();

    expect($otherGroup->fresh()->status)->toBe(RecordStatus::Ratified);
});

it('does not let a club administrator ratify a record', function (): void {
    $record = SwimRecord::factory()->pending()->create();
    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.records.ratify', $record))
        ->assertForbidden();

    expect($record->fresh()->status)->toBe(RecordStatus::Pending);
});

it('rejects an unreadable time rather than storing it', function (): void {
    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.records.index'))
        ->post(route('admin.records.store'), [
            'scope' => 'national',
            'course' => Course::LongCourse->value,
            'gender' => 'female',
            'age_group' => '11/12',
            'stroke' => Stroke::Freestyle->value,
            'distance' => 50,
            'time' => 'quite fast',
            'holder_name' => 'Nobody',
        ])
        ->assertSessionHasErrors('time');

    expect(SwimRecord::where('holder_name', 'Nobody')->exists())->toBeFalse();
});

it('shows only ratified records on the public record list', function (): void {
    SwimRecord::factory()->create([
        'holder_name' => 'Ratified holder',
        'age_group' => '11/12',
        'gender' => 'female',
        'course' => Course::LongCourse,
    ]);

    SwimRecord::factory()->pending()->create([
        'holder_name' => 'Pending holder',
        'age_group' => '11/12',
        'gender' => 'female',
        'course' => Course::LongCourse,
    ]);

    $this->get(route('records'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/records')
            ->where('pendingCount', 1)
            ->where('records.11/12.0.holder', 'Ratified holder')
            ->count('records.11/12', 1));
});

it('groups proposals by the meet they were swum at', function (): void {
    $season = Season::factory()->create();

    $sprint = Gala::factory()->sanctioned()->create([
        'name' => 'National Sprint Gala',
        'season_id' => $season->getKey(),
        'host_organization_id' => $this->province->getKey(),
    ]);

    $champs = Gala::factory()->sanctioned()->create([
        'name' => 'Provincial Championships',
        'season_id' => $season->getKey(),
        'host_organization_id' => $this->province->getKey(),
    ]);

    SwimRecord::factory()->count(3)->pending()->create(['gala_id' => $sprint->getKey()]);
    SwimRecord::factory()->pending()->create(['gala_id' => $champs->getKey()]);

    // A time entered from the archive belongs to no meet, and must still be
    // reachable rather than dropped from the queue.
    SwimRecord::factory()->pending()->create(['gala_id' => null]);

    $this->actingAs(userWithRole(Roles::BOARD_MEMBER, $this->national))
        ->get(route('admin.records.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('pending', 3)
            ->count('standing', 0)
            ->where('total', 5)
            ->etc());
});

it('groups the standing list by course, gender and age group instead', function (): void {
    SwimRecord::factory()->count(2)->create([
        'status' => RecordStatus::Ratified,
        'course' => Course::LongCourse,
        'gender' => 'female',
        'age_group' => '11/12',
    ]);

    SwimRecord::factory()->create([
        'status' => RecordStatus::Ratified,
        'course' => Course::LongCourse,
        'gender' => 'male',
        'age_group' => '11/12',
    ]);

    $this->actingAs(userWithRole(Roles::BOARD_MEMBER, $this->national))
        ->get(route('admin.records.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('standing', 2)
            ->count('pending', 0)
            // Girls before boys, which is the order a records table prints.
            ->where('standing.0.gender', 'female')
            ->count('standing.0.records', 2)
            ->where('standing.1.gender', 'male')
            ->etc());
});

it('keeps a filtered view narrowed in both groupings', function (): void {
    SwimRecord::factory()->pending()->create(['course' => Course::LongCourse]);
    SwimRecord::factory()->pending()->create(['course' => Course::ShortCourse]);

    $this->actingAs(userWithRole(Roles::BOARD_MEMBER, $this->national))
        ->get(route('admin.records.index', ['course' => Course::ShortCourse->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('total', 1)->etc());
});

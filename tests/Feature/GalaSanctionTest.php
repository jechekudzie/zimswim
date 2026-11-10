<?php

use App\Enums\GalaLevel;
use App\Enums\GalaStatus;
use App\Enums\VenueGrade;
use App\Models\Gala;
use App\Models\Season;
use App\Models\Venue;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    $this->season = Season::factory()->create();
    [$this->national, $this->province, $this->club] = organizationTree();
});

it('lets the board sanction a gala held at a suitably graded venue', function (): void {
    $venue = Venue::factory()->graded(VenueGrade::National)->create();
    $gala = Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $venue->getKey(),
        'level' => GalaLevel::National,
    ]);

    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.galas.show', $gala))
        ->post(route('admin.galas.sanction', $gala), ['decision' => 'sanction'])
        ->assertRedirect();

    $gala->refresh();

    expect($gala->status)->toBe(GalaStatus::Sanctioned)
        ->and($gala->is_published)->toBeTrue()
        ->and($gala->sanctioned_by)->toBe($board->getKey());
});

it('refuses to sanction a national gala into a club graded pool', function (): void {
    $venue = Venue::factory()->graded(VenueGrade::ClubDevelopment)->create();
    $gala = Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $venue->getKey(),
        'level' => GalaLevel::National,
    ]);

    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.galas.show', $gala))
        ->post(route('admin.galas.sanction', $gala), ['decision' => 'sanction'])
        ->assertSessionHasErrors('decision');

    expect($gala->fresh()->status)->toBe(GalaStatus::Submitted);
});

it('refuses to sanction a gala with no venue booked', function (): void {
    $gala = Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => null,
    ]);

    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.galas.show', $gala))
        ->post(route('admin.galas.sanction', $gala), ['decision' => 'sanction'])
        ->assertSessionHasErrors('decision');
});

it('refuses to sanction a gala into a pool another sanctioned gala already claims', function (): void {
    $venue = Venue::factory()->graded(VenueGrade::National)->create();

    Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $venue->getKey(),
        'starts_on' => '2027-02-18',
        'ends_on' => '2027-02-22',
    ]);

    $clashing = Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $venue->getKey(),
        'starts_on' => '2027-02-20',
        'ends_on' => '2027-02-21',
    ]);

    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.galas.show', $clashing))
        ->post(route('admin.galas.sanction', $clashing), ['decision' => 'sanction'])
        ->assertSessionHasErrors('decision');
});

it('does not let a club administrator sanction their own gala', function (): void {
    $venue = Venue::factory()->graded(VenueGrade::National)->create();
    $gala = Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->club->getKey(),
        'venue_id' => $venue->getKey(),
        'level' => GalaLevel::Club,
    ]);

    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.galas.sanction', $gala), ['decision' => 'sanction'])
        ->assertForbidden();

    expect($gala->fresh()->status)->toBe(GalaStatus::Submitted);
});

it('returns a gala to the host when the board requests changes', function (): void {
    $venue = Venue::factory()->graded(VenueGrade::National)->create();
    $gala = Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $venue->getKey(),
    ]);

    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->from(route('admin.galas.show', $gala))
        ->post(route('admin.galas.sanction', $gala), [
            'decision' => 'reject',
            'notes' => 'Move to a 50m pool.',
        ])
        ->assertRedirect();

    $gala->refresh();

    expect($gala->status)->toBe(GalaStatus::Rejected)
        ->and($gala->is_published)->toBeFalse()
        ->and($gala->sanction_notes)->toBe('Move to a 50m pool.');
});

it('submits a new gala request for sanction rather than publishing it', function (): void {
    $venue = Venue::factory()->create();
    $provincialAdmin = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

    $this->actingAs($provincialAdmin)
        ->post(route('admin.galas.store'), [
            'name' => 'Mashonaland East Championships',
            'season_id' => $this->season->getKey(),
            'host_organization_id' => $this->province->getKey(),
            'venue_id' => $venue->getKey(),
            'level' => 'provincial',
            'course' => 'SCM',
            'starts_on' => '2026-11-11',
            'ends_on' => '2026-11-11',
            'entry_fee' => 20,
        ])
        ->assertRedirect();

    $gala = Gala::where('name', 'Mashonaland East Championships')->firstOrFail();

    expect($gala->status)->toBe(GalaStatus::Submitted)
        ->and($gala->is_published)->toBeFalse()
        ->and($gala->requested_by)->toBe($provincialAdmin->getKey());
});

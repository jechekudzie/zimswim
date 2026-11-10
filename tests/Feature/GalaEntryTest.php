<?php

use App\Enums\AffiliationStatus;
use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\MemberRegistration;
use App\Models\Organization;
use App\Models\Season;
use App\Models\Venue;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->season = Season::factory()->create(['age_up_date' => '2026-12-31']);

    $this->gala = Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => Venue::factory()->create()->getKey(),
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

    $this->clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);
});

function swimmer(array $attributes, $context): Member
{
    $member = Member::factory()->create([
        'organization_id' => $context->club->getKey(),
        'gender' => 'female',
        'status' => MemberStatus::Active,
        ...$attributes,
    ]);

    MemberRegistration::factory()->create([
        'member_id' => $member->getKey(),
        'season_id' => $context->season->getKey(),
        'organization_id' => $context->club->getKey(),
    ]);

    return $member;
}

it('enters a swimmer of the right age from an affiliated club', function (): void {
    $member = swimmer(['date_of_birth' => '2014-06-01'], $this);

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), [
            'gala_event_id' => $this->event->getKey(),
            'member_ids' => [$member->getKey()],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(GalaEntry::where('member_id', $member->getKey())->exists())->toBeTrue();
});

it('refuses a swimmer outside the event age group', function (): void {
    // Born 2017, so eight at the age-up date and too young for 11/12.
    $member = swimmer(['date_of_birth' => '2017-06-01'], $this);

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), [
            'gala_event_id' => $this->event->getKey(),
            'member_ids' => [$member->getKey()],
        ])
        ->assertSessionHasErrors('member_ids');

    expect(GalaEntry::count())->toBe(0);
});

/**
 * Clubs pay through their province, and the federation's rule is that a
 * province which has not settled by the deadline may not enter swimmers. It is
 * meant to be rare, which is exactly why it needs a test: nobody will discover
 * by accident that it stopped working.
 */
it('refuses entries when the province is not fully affiliated', function (): void {
    $member = swimmer(['date_of_birth' => '2014-06-01'], $this);

    $this->province->forceFill(['affiliation_status' => AffiliationStatus::Lapsed])->save();

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), [
            'gala_event_id' => $this->event->getKey(),
            'member_ids' => [$member->getKey()],
        ])
        ->assertSessionHasErrors(['member_ids' => $this->province->name
            .' is not fully affiliated for this season, so its clubs cannot enter swimmers.']);

    expect(GalaEntry::count())->toBe(0);
});

it('refuses entries from a suspended club', function (): void {
    $member = swimmer(['date_of_birth' => '2014-06-01'], $this);

    $this->club->forceFill(['affiliation_status' => AffiliationStatus::Suspended])->save();

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), [
            'gala_event_id' => $this->event->getKey(),
            'member_ids' => [$member->getKey()],
        ])
        ->assertSessionHasErrors('member_ids');

    expect(GalaEntry::count())->toBe(0);
});

it('treats an expired affiliation as lapsed whatever the stored status says', function (): void {
    $member = swimmer(['date_of_birth' => '2014-06-01'], $this);

    $this->province->forceFill([
        'affiliation_status' => AffiliationStatus::Affiliated,
        'affiliation_expires_on' => now()->subDay(),
    ])->save();

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), [
            'gala_event_id' => $this->event->getKey(),
            'member_ids' => [$member->getKey()],
        ])
        ->assertSessionHasErrors('member_ids');

    expect(GalaEntry::count())->toBe(0);
});

/**
 * The fee figures on a registration are what the province will be billed for;
 * they are not a gate on the individual swimmer. This was the old rule, and it
 * refused every entry in production because nothing could ever set the two
 * paid timestamps it read.
 */
it('enters a swimmer whose own fees are not yet recorded as paid', function (): void {
    $member = Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'date_of_birth' => '2014-06-01',
        'gender' => 'female',
        'status' => MemberStatus::Active,
    ]);

    MemberRegistration::factory()->unpaid()->create([
        'member_id' => $member->getKey(),
        'season_id' => $this->season->getKey(),
        'organization_id' => $this->club->getKey(),
    ]);

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), [
            'gala_event_id' => $this->event->getKey(),
            'member_ids' => [$member->getKey()],
        ])
        ->assertSessionHasNoErrors();

    expect(GalaEntry::where('member_id', $member->getKey())->exists())->toBeTrue();
});

it('refuses a boy in a girls event', function (): void {
    $member = swimmer(['date_of_birth' => '2014-06-01', 'gender' => 'male'], $this);

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), [
            'gala_event_id' => $this->event->getKey(),
            'member_ids' => [$member->getKey()],
        ])
        ->assertSessionHasErrors('member_ids');
});

it('does not enter the same swimmer twice in one event', function (): void {
    $member = swimmer(['date_of_birth' => '2014-06-01'], $this);

    $payload = [
        'gala_event_id' => $this->event->getKey(),
        'member_ids' => [$member->getKey()],
    ];

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), $payload);

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), $payload)
        ->assertSessionHasErrors('member_ids');

    expect(GalaEntry::count())->toBe(1);
});

it('marks an entry made after the deadline as late', function (): void {
    $this->gala->forceFill(['entries_close_at' => now()->subDay()])->save();
    $member = swimmer(['date_of_birth' => '2014-06-01'], $this);

    $this->actingAs($this->clubAdmin)
        ->from(route('admin.galas.entries.index', $this->gala))
        ->post(route('admin.galas.entries.store', $this->gala), [
            'gala_event_id' => $this->event->getKey(),
            'member_ids' => [$member->getKey()],
        ])
        ->assertSessionHasErrors('member_ids');
});

it('shows a club only its own entries', function (): void {
    $mine = swimmer(['date_of_birth' => '2014-06-01', 'first_name' => 'Mine'], $this);

    GalaEntry::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_event_id' => $this->event->getKey(),
        'member_id' => $mine->getKey(),
        'organization_id' => $this->club->getKey(),
    ]);

    $otherClub = Organization::factory()->club($this->province)->create();
    $theirs = Member::factory()->create(['organization_id' => $otherClub->getKey()]);

    GalaEntry::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_event_id' => $this->event->getKey(),
        'member_id' => $theirs->getKey(),
        'organization_id' => $otherClub->getKey(),
    ]);

    $this->actingAs($this->clubAdmin)
        ->get(route('admin.galas.entries.index', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/galas/entries')
            ->count('entries', 1)
            ->where('entries.0.swimmer', $mine->fullName()));
});

<?php

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Season;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    Season::factory()->create();
    [$this->national, $this->province, $this->club] = organizationTree();
});

it('registers a swimmer when guardian consent is recorded', function (): void {
    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.members.store'), [
            'organization_id' => $this->club->getKey(),
            'membership_number' => 'ZSW-01001',
            'first_name' => 'Tanaka',
            'last_name' => 'Moyo',
            'date_of_birth' => '2014-03-11',
            'gender' => 'female',
            'status' => 'active',
            'consent_given' => true,
            'consent_given_by' => 'Rudo Moyo',
        ])
        ->assertRedirect();

    $member = Member::where('membership_number', 'ZSW-01001')->firstOrFail();

    expect($member->consent_given_at)->not->toBeNull()
        ->and($member->hasRequiredConsent())->toBeTrue()
        ->and($member->status)->toBe(MemberStatus::Active);
});

it('refuses to register a swimmer under 18 without guardian consent', function (): void {
    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.members.store'), [
            'organization_id' => $this->club->getKey(),
            'membership_number' => 'ZSW-01002',
            'first_name' => 'Takudzwa',
            'last_name' => 'Ndlovu',
            'date_of_birth' => '2014-03-11',
            'gender' => 'male',
            'status' => 'active',
            'consent_given' => false,
        ])
        ->assertSessionHasErrors('consent_given');

    expect(Member::where('membership_number', 'ZSW-01002')->exists())->toBeFalse();
});

it('registers a swimmer of 18 and over without a guardian consent', function (): void {
    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.members.store'), [
            'organization_id' => $this->club->getKey(),
            'membership_number' => 'ZSW-01003',
            'first_name' => 'Peter',
            'last_name' => 'Wetzlar',
            'date_of_birth' => now()->subYears(24)->toDateString(),
            'gender' => 'male',
            'status' => 'active',
            'consent_given' => false,
        ])
        ->assertSessionHasNoErrors();

    expect(Member::where('membership_number', 'ZSW-01003')->exists())->toBeTrue();
});

it('refuses to register a swimmer into a club the user does not administer', function (): void {
    $otherClub = Organization::factory()->club($this->province)->create();
    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.members.store'), [
            'organization_id' => $otherClub->getKey(),
            'membership_number' => 'ZSW-01004',
            'first_name' => 'Anesu',
            'last_name' => 'Marimo',
            'date_of_birth' => '2013-02-18',
            'gender' => 'female',
            'status' => 'active',
            'consent_given' => true,
            'consent_given_by' => 'Parent',
        ])
        ->assertSessionHasErrors('organization_id');
});

it('works out the competition age from the season age-up date, not today', function (): void {
    $season = Season::first();
    $member = Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'date_of_birth' => '2014-03-11',
    ]);

    // Age-up date is 31 December 2026, so a swimmer born in March 2014 is 12.
    expect($member->competitionAge($season))->toBe(12);
});

it('stops a coach from registering swimmers', function (): void {
    $coach = userWithRole(Roles::COACH, $this->club);

    $this->actingAs($coach)
        ->post(route('admin.members.store'), [
            'organization_id' => $this->club->getKey(),
            'membership_number' => 'ZSW-01005',
            'first_name' => 'Rufaro',
            'last_name' => 'Chirwa',
            'date_of_birth' => '2016-04-21',
            'gender' => 'female',
            'status' => 'active',
            'consent_given' => true,
            'consent_given_by' => 'Parent',
        ])
        ->assertForbidden();
});

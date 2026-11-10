<?php

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Organization;
use App\Support\OrganizationContext;
use App\Support\Permissions;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
});

it('keeps national permissions when a board member looks at one province', function (): void {
    [$national, $province, $club] = organizationTree();
    $user = userWithRole(Roles::BOARD_MEMBER, $national);

    $context = app(OrganizationContext::class);
    $context->set($province);

    expect($context->permits($user, Permissions::SANCTION_GALAS))->toBeTrue()
        ->and($context->permits($user, Permissions::RATIFY_RECORDS))->toBeTrue();
});

it('narrows what a board member sees to the province they are looking at', function (): void {
    [$national, $province] = organizationTree();
    $otherProvince = Organization::factory()->province($national)->create();
    $otherClub = Organization::factory()->club($otherProvince)->create();

    $user = userWithRole(Roles::BOARD_MEMBER, $national);

    $context = app(OrganizationContext::class);
    $context->set($province);

    expect($context->scopeIds())
        ->not->toContain($otherProvince->getKey())
        ->not->toContain($otherClub->getKey());
});

it('does not grant a club administrator powers held only at national level', function (): void {
    [, , $club] = organizationTree();
    $user = userWithRole(Roles::CLUB_ADMINISTRATOR, $club);

    $context = app(OrganizationContext::class);
    $context->set($club);

    expect($context->permits($user, Permissions::MANAGE_MEMBERS))->toBeTrue()
        ->and($context->permits($user, Permissions::SANCTION_GALAS))->toBeFalse()
        ->and($context->permits($user, Permissions::RATIFY_RECORDS))->toBeFalse();
});

it('hides swimmers from other clubs on the members index', function (): void {
    [$national, $province, $club] = organizationTree();
    $otherClub = Organization::factory()->club($province)->create();

    $mine = Member::factory()->create(['organization_id' => $club->getKey()]);
    $theirs = Member::factory()->create(['organization_id' => $otherClub->getKey()]);

    $user = userWithRole(Roles::CLUB_ADMINISTRATOR, $club);

    $this->actingAs($user)
        ->get(route('admin.members.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/members/index')
            ->where('members.total', 1)
            ->where('members.data.0.membership_number', $mine->membership_number));
});

it('hides another province\'s swimmers from a provincial administrator', function (): void {
    [$national, $province, $club] = organizationTree();
    $otherProvince = Organization::factory()->province($national)->create();
    $otherClub = Organization::factory()->club($otherProvince)->create();

    $mine = Member::factory()->create(['organization_id' => $club->getKey()]);
    Member::factory()->create(['organization_id' => $otherClub->getKey()]);

    $user = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $province);

    $this->actingAs($user)
        ->get(route('admin.members.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('members.total', 1)
            ->where('members.data.0.membership_number', $mine->membership_number));
});

it('refuses to switch into an organisation the user cannot reach', function (): void {
    [$national, $province, $club] = organizationTree();
    $otherProvince = Organization::factory()->province($national)->create();

    $user = userWithRole(Roles::CLUB_ADMINISTRATOR, $club);

    $this->actingAs($user)
        ->post(route('admin.organization.switch'), ['organization_id' => $otherProvince->getKey()])
        ->assertForbidden();
});

it('allows switching into an organisation beneath the one the user belongs to', function (): void {
    [$national, $province, $club] = organizationTree();
    $user = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $province);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('admin.organization.switch'), ['organization_id' => $club->getKey()])
        ->assertRedirect();

    expect($user->fresh()->current_organization_id)->toBe($club->getKey());
});

it('builds the organisation tree top down with swimmers rolled up', function (): void {
    [$national, $province, $club] = organizationTree();
    $emptyProvince = Organization::factory()->province($national)->create();

    Member::factory()->count(3)->create([
        'organization_id' => $club->getKey(),
        'status' => MemberStatus::Active,
    ]);

    $user = userWithRole(Roles::BOARD_MEMBER, $national);

    $this->actingAs($user)
        ->get(route('admin.organizations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('view', 'hierarchy')
            ->count('tree', 1)
            ->where('tree.0.name', $national->name)
            // The national body counts the club beneath it, two tiers down.
            ->where('tree.0.total_swimmers', 3)
            ->where('tree.0.swimmers', 0)
            ->count('tree.0.children', 2)
            ->where('tree.0.children.0.total_swimmers', 3)
            ->count('tree.0.children.0.children', 1)
            ->where('tree.0.children.0.children.0.name', $club->name)
            ->where('tree.0.children.1.name', $emptyProvince->name)
            ->count('tree.0.children.1.children', 0));
});

it('shows a province administrator their own province at the root of the tree', function (): void {
    [$national, $province, $club] = organizationTree();

    $user = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $province);
    app(OrganizationContext::class)->set($province);

    $this->actingAs($user)
        ->get(route('admin.organizations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('tree', 1)
            ->where('tree.0.name', $province->name)
            ->where('tree.0.children.0.name', $club->name));
});

it('orders the organisation table from the national body down', function (): void {
    [$national, $province, $club] = organizationTree();

    $user = userWithRole(Roles::BOARD_MEMBER, $national);

    $this->actingAs($user)
        ->get(route('admin.organizations.index', ['view' => 'table']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('organizations.data.0.name', $national->name)
            ->where('organizations.data.1.name', $province->name)
            ->where('organizations.data.2.name', $club->name));
});

<?php

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Organization;
use App\Models\TechnicalOfficial;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->harare, $this->lesBrown] = organizationTree();

    $this->bulawayo = Organization::factory()->province($this->national)->create();
    $this->barracudas = Organization::factory()->club($this->bulawayo)->create();

    $this->admin = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
});

it('narrows the swimmers register to one province', function (): void {
    Member::factory()->count(3)->create([
        'organization_id' => $this->lesBrown->getKey(),
        'province_organization_id' => $this->harare->getKey(),
        'status' => MemberStatus::Active,
    ]);

    Member::factory()->count(2)->create([
        'organization_id' => $this->barracudas->getKey(),
        'province_organization_id' => $this->bulawayo->getKey(),
        'status' => MemberStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.index', ['province' => $this->bulawayo->getKey()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('members.total', 2)
            ->where('filters.province', (string) $this->bulawayo->getKey())
            ->etc());
});

it('narrows the swimmers register to one club', function (): void {
    Member::factory()->count(3)->create([
        'organization_id' => $this->lesBrown->getKey(),
        'province_organization_id' => $this->harare->getKey(),
    ]);

    Member::factory()->create([
        'organization_id' => $this->barracudas->getKey(),
        'province_organization_id' => $this->bulawayo->getKey(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.index', ['club' => $this->barracudas->getKey()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('members.total', 1)->etc());
});

it('narrows the officials register the same way', function (): void {
    TechnicalOfficial::factory()->count(2)->create([
        'organization_id' => $this->lesBrown->getKey(),
        'province_organization_id' => $this->harare->getKey(),
    ]);

    TechnicalOfficial::factory()->create([
        'organization_id' => $this->barracudas->getKey(),
        'province_organization_id' => $this->bulawayo->getKey(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.officials.index', ['province' => $this->harare->getKey()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('officials.total', 2)->etc());
});

it('offers each club with the province it belongs to, so the pair can narrow itself', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.members.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('provinces', 2)
            ->count('clubs', 2)
            ->where('clubs.0.province_id', fn ($id) => in_array(
                $id,
                [$this->harare->getKey(), $this->bulawayo->getKey()],
                true,
            ))
            ->etc());
});

it('carries the filter into the spreadsheet, so the file matches the page', function (): void {
    Member::factory()->count(3)->create([
        'organization_id' => $this->lesBrown->getKey(),
        'province_organization_id' => $this->harare->getKey(),
    ]);

    Member::factory()->create([
        'organization_id' => $this->barracudas->getKey(),
        'province_organization_id' => $this->bulawayo->getKey(),
    ]);

    $csv = $this->actingAs($this->admin)
        ->get(route('admin.exports.members', ['club' => $this->barracudas->getKey()]))
        ->assertOk()
        ->streamedContent();

    // One heading row and exactly one swimmer.
    expect(array_filter(explode("\n", trim($csv))))->toHaveCount(2);
});

it('keeps a club administrator from filtering to a province they cannot see', function (): void {
    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->lesBrown);

    $this->actingAs($clubAdmin)
        ->get(route('admin.members.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('provinces', 0)
            ->count('clubs', 1)
            ->etc());
});

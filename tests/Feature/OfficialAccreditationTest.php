<?php

use App\Enums\AccreditationStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Models\OfficialAccreditation;
use App\Models\TechnicalOfficial;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();

    $this->official = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'province_organization_id' => $this->province->getKey(),
    ]);
});

it('starts a category that has no prerequisites', function (): void {
    $admin = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

    $this->actingAs($admin)
        ->from(route('admin.officials.show', $this->official))
        ->post(route('admin.officials.accreditations.store', $this->official), [
            'category' => OfficialCategory::Timekeeper->value,
        ])
        ->assertRedirect();

    expect($this->official->accreditations()->where('category', OfficialCategory::Timekeeper)->exists())
        ->toBeTrue();
});

it('refuses a category whose prerequisite is not yet held', function (): void {
    $admin = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

    $this->actingAs($admin)
        ->from(route('admin.officials.show', $this->official))
        ->post(route('admin.officials.accreditations.store', $this->official), [
            'category' => OfficialCategory::Referee->value,
        ])
        ->assertSessionHasErrors('category');

    expect($this->official->accreditations()->count())->toBe(0);
});

it('refuses a category the official is too young for', function (): void {
    $young = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'date_of_birth' => now()->subYears(14)->toDateString(),
    ]);

    $admin = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

    $this->actingAs($admin)
        ->from(route('admin.officials.show', $young))
        ->post(route('admin.officials.accreditations.store', $young), [
            'category' => OfficialCategory::Timekeeper->value,
        ])
        ->assertSessionHasNoErrors();

    // Chief timekeeper has a minimum age of 16.
    $this->actingAs($admin)
        ->from(route('admin.officials.show', $young))
        ->post(route('admin.officials.accreditations.store', $young), [
            'category' => OfficialCategory::ChiefTimekeeper->value,
        ])
        ->assertSessionHasErrors('category');
});

it('refuses to ratify a grade before the exam is passed', function (): void {
    $accreditation = OfficialAccreditation::factory()->create([
        'technical_official_id' => $this->official->getKey(),
        'category' => OfficialCategory::Timekeeper,
        'grade' => OfficialGrade::Learner,
        'status' => AccreditationStatus::InProgress,
        'learner_sessions_completed' => 6,
        'exam_passed_on' => null,
    ]);

    $technical = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($technical)
        ->from(route('admin.officials.show', $this->official))
        ->post(route('admin.officials.accreditations.ratify', [$this->official, $accreditation]), [
            'grade' => OfficialGrade::Provincial->value,
        ])
        ->assertSessionHasErrors('grade');

    expect($accreditation->fresh()->status)->toBe(AccreditationStatus::InProgress);
});

it('refuses to ratify a grade while learner sessions are outstanding', function (): void {
    $accreditation = OfficialAccreditation::factory()->create([
        'technical_official_id' => $this->official->getKey(),
        'category' => OfficialCategory::Timekeeper,
        'grade' => OfficialGrade::Learner,
        'status' => AccreditationStatus::InProgress,
        'learner_sessions_completed' => 2,
        'exam_passed_on' => now()->toDateString(),
    ]);

    $technical = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($technical)
        ->from(route('admin.officials.show', $this->official))
        ->post(route('admin.officials.accreditations.ratify', [$this->official, $accreditation]), [
            'grade' => OfficialGrade::Provincial->value,
        ])
        ->assertSessionHasErrors('grade');
});

it('ratifies a grade and sets a two year expiry once the requirements are met', function (): void {
    $accreditation = OfficialAccreditation::factory()->create([
        'technical_official_id' => $this->official->getKey(),
        'category' => OfficialCategory::Timekeeper,
        'grade' => OfficialGrade::Learner,
        'status' => AccreditationStatus::InProgress,
        'learner_sessions_completed' => 6,
        'exam_passed_on' => now()->toDateString(),
    ]);

    $technical = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($technical)
        ->from(route('admin.officials.show', $this->official))
        ->post(route('admin.officials.accreditations.ratify', [$this->official, $accreditation]), [
            'grade' => OfficialGrade::Provincial->value,
        ])
        ->assertRedirect();

    $accreditation->refresh();

    expect($accreditation->grade)->toBe(OfficialGrade::Provincial)
        ->and($accreditation->status)->toBe(AccreditationStatus::Active)
        ->and($accreditation->ratified_by)->toBe($technical->getKey())
        ->and($accreditation->expires_on->toDateString())
        ->toBe(now()->addYears(2)->toDateString());
});

it('does not let a club administrator ratify an accreditation', function (): void {
    $accreditation = OfficialAccreditation::factory()->create([
        'technical_official_id' => $this->official->getKey(),
        'status' => AccreditationStatus::InProgress,
    ]);

    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.officials.accreditations.ratify', [$this->official, $accreditation]), [
            'grade' => OfficialGrade::National->value,
        ])
        ->assertForbidden();
});

it('only offers categories whose prerequisites the official already holds', function (): void {
    OfficialAccreditation::factory()->create([
        'technical_official_id' => $this->official->getKey(),
        'category' => OfficialCategory::Timekeeper,
        'grade' => OfficialGrade::Provincial,
        'status' => AccreditationStatus::Active,
    ]);

    $available = collect($this->official->fresh()->load('accreditations')->availableCategories())
        ->map(fn (OfficialCategory $category): string => $category->value);

    expect($available)->toContain(OfficialCategory::ChiefTimekeeper->value)
        ->toContain(OfficialCategory::InspectorOfTurns->value)
        ->not->toContain(OfficialCategory::Timekeeper->value)
        ->not->toContain(OfficialCategory::Referee->value);
});

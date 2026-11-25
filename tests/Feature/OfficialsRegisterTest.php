<?php

use App\Enums\AccreditationStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Models\OfficialAccreditation;
use App\Models\TechnicalOfficial;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
});

/**
 * An official holding one ratified, active accreditation.
 */
function accredited(array $official = [], array $accreditation = []): TechnicalOfficial
{
    $record = TechnicalOfficial::factory()->create([
        'organization_id' => test()->club->getKey(),
        'province_organization_id' => test()->province->getKey(),
        'status' => 'active',
        ...$official,
    ]);

    OfficialAccreditation::factory()->create([
        'technical_official_id' => $record->getKey(),
        'category' => OfficialCategory::Referee,
        'grade' => OfficialGrade::National,
        'status' => AccreditationStatus::Active,
        'ratified_at' => now()->subMonth(),
        'expires_on' => now()->addYear()->toDateString(),
        ...$accreditation,
    ]);

    return $record;
}

it('names an official the committee has ratified', function (): void {
    accredited(['first_name' => 'Tendai', 'last_name' => 'Moyo']);

    $this->get(route('officials.register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/officials-register')
            ->where('officials.0.name', 'Tendai Moyo')
            ->where('officials.0.accreditations.0.category', 'Referee')
            ->where('officials.0.accreditations.0.grade', 'Qualified National')
            ->etc());
});

it('keeps an accreditation that has not been ratified off the register', function (): void {
    // Ratification is the committee's decision, and until it is made the
    // person does not hold the qualification.
    accredited(['last_name' => 'Unratified'], ['ratified_at' => null]);

    $this->get(route('officials.register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('officials', [])->etc());
});

it('keeps a learner off the register', function (): void {
    accredited(['last_name' => 'Learning'], ['grade' => OfficialGrade::Learner]);

    $this->get(route('officials.register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('officials', [])->etc());
});

it('drops an accreditation once it has expired', function (): void {
    accredited(['last_name' => 'Lapsed'], ['expires_on' => now()->subDay()->toDateString()]);

    $this->get(route('officials.register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('officials', [])->etc());
});

it('narrows the register to one category', function (): void {
    accredited(['last_name' => 'Referee']);
    accredited(['last_name' => 'Starter'], ['category' => OfficialCategory::Starter]);

    $this->get(route('officials.register', ['category' => OfficialCategory::Starter->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('officials', fn ($officials): bool => collect($officials)->pluck('name')->count() === 1
                && str_contains((string) collect($officials)->first()['name'], 'Starter'))
            ->etc());
});

it('still shows everything an official holds when the list is filtered', function (): void {
    $official = accredited(['last_name' => 'Both']);

    OfficialAccreditation::factory()->create([
        'technical_official_id' => $official->getKey(),
        'category' => OfficialCategory::Starter,
        'grade' => OfficialGrade::Provincial,
        'status' => AccreditationStatus::Active,
        'ratified_at' => now()->subMonth(),
        'expires_on' => now()->addYear()->toDateString(),
    ]);

    /*
     * Filtering to starters narrows who is listed, not what each of them
     * holds: a convenor ringing round needs to know this starter is also a
     * national referee.
     */
    $this->get(route('officials.register', ['category' => OfficialCategory::Starter->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('officials', 1)
            ->count('officials.0.accreditations', 2)
            ->where('officials.0.accreditations.0.category', 'Referee')
            ->etc());
});

it('publishes no way to contact anybody', function (): void {
    $official = accredited(['email' => 'official@example.org', 'phone' => '0772000111']);

    /*
     * An official agreed to be on a register, not to be reachable by whoever
     * reads one. Clubs and provincial boards carry the contact.
     */
    $this->get(route('officials.register'))
        ->assertOk()
        ->assertDontSee($official->email)
        ->assertDontSee('0772000111');
});

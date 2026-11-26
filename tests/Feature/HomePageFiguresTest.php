<?php

use App\Enums\AccreditationStatus;
use App\Enums\OfficialGrade;
use App\Models\Gala;
use App\Models\Member;
use App\Models\OfficialAccreditation;
use App\Models\Season;
use App\Models\TechnicalOfficial;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->season = Season::factory()->create(['is_current' => true]);
});

it('counts the bodies and clubs the federation actually has', function (): void {
    Member::factory()->count(2)->create(['organization_id' => $this->club->getKey()]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.provinces', 1)
            ->where('stats.clubs', 1)
            ->where('stats.swimmers', 2)
            ->etc());
});

it('counts the whole season, not only the galas still to come', function (): void {
    Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'is_published' => true,
        'starts_on' => now()->subMonth(),
        'ends_on' => now()->subMonth()->addDay(),
    ]);

    Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'is_published' => true,
        'starts_on' => now()->addMonth(),
        'ends_on' => now()->addMonth()->addDay(),
    ]);

    // Counting only what is left would shrink the figure as the season is
    // swum, and read nought by April.
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('stats.galas', 2)->etc());
});

it('does not call a learner a qualified official', function (): void {
    /*
     * The register publishes somebody once the technical committee has
     * ratified them. The home page figure has to agree with it, or it
     * reports people as qualified that the register will not name.
     */
    $official = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'status' => 'active',
    ]);

    OfficialAccreditation::factory()->create([
        'technical_official_id' => $official->getKey(),
        'grade' => OfficialGrade::Learner,
        'status' => AccreditationStatus::Active,
        'ratified_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.officials', 1)
            ->where('stats.accredited', 0)
            ->etc());
});

it('reports nothing rather than nought on a federation that has just started', function (): void {
    /*
     * The state the site goes live in: the handbook is loaded, but no
     * swimmer has been registered through it and no record ratified on it.
     * A hero band announcing nought swimmers says something about the
     * federation that is not true, so the figure is left out instead.
     */
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.swimmers', 0)
            ->where('stats.officials', 0)
            ->where('stats.records', 0)
            ->etc())
        ->assertDontSee('Registered swimmers')
        ->assertDontSee('Technical officials')
        ->assertDontSee('Ratified records');
});

<?php

use App\Enums\GalaLevel;
use App\Enums\OfficialCategory;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\Post;
use App\Models\Season;
use App\Models\Venue;

beforeEach(function (): void {
    $this->season = Season::factory()->create();
    [$this->national, $this->province, $this->club] = organizationTree();
});

it('shows the home page to a visitor who is not signed in', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/home'));
});

it('invites visitors to officiate on the entry requirements the register enforces', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('officialsEntry.category', OfficialCategory::Timekeeper->label())
            ->where('officialsEntry.minimum_age', OfficialCategory::Timekeeper->minimumAge())
            ->where('officialsEntry.learner_sessions', OfficialCategory::Timekeeper->learnerSessionsRequired())
            ->etc());
});

it('lists only published galas on the public calendar', function (): void {
    $published = Gala::factory()->sanctioned()->create([
        'name' => 'National Sprint Gala',
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
    ]);

    Gala::factory()->create([
        'name' => 'Unsanctioned Gala',
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'is_published' => false,
    ]);

    $this->get(route('calendar'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/calendar')
            ->count('galas', 1)
            ->where('galas.0.name', $published->name));
});

it('filters the calendar by gala level', function (): void {
    Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'level' => GalaLevel::National,
    ]);

    Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'level' => GalaLevel::Club,
    ]);

    $this->get(route('calendar', ['level' => 'national']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('galas', 1));
});

it('counts the whole season on the calendar filters, not the filtered view', function (): void {
    Gala::factory()->sanctioned()->count(2)->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'level' => GalaLevel::National,
    ]);

    Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'level' => GalaLevel::Club,
    ]);

    $this->get(route('calendar', ['level' => 'club']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('galas', 1)
            ->count('levels', 2)
            ->where('levels.0.value', 'club')
            ->where('levels.0.count', 1)
            ->where('levels.1.value', 'national')
            ->where('levels.1.count', 2));
});

it('leaves a level off the calendar filters when the season holds no meets at it', function (): void {
    Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'level' => GalaLevel::Provincial,
    ]);

    $this->get(route('calendar'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('levels', 1)
            ->where('levels.0.value', 'provincial'));
});

it('marks a calendar meet whose results have been published', function (): void {
    $withResults = Gala::factory()->sanctioned()->create([
        'name' => 'Published Gala',
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'starts_on' => now()->subMonth(),
        'ends_on' => now()->subMonth(),
    ]);

    GalaEvent::factory()->for($withResults)->create([
        'results_published_at' => now()->subWeek(),
    ]);

    $withoutResults = Gala::factory()->sanctioned()->create([
        'name' => 'Unpublished Gala',
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'starts_on' => now()->subWeek(),
        'ends_on' => now()->subWeek(),
    ]);

    GalaEvent::factory()->for($withoutResults)->create();

    $this->get(route('calendar'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('galas.0.name', 'Published Gala')
            ->where('galas.0.has_results', true)
            ->where('galas.1.name', 'Unpublished Gala')
            ->where('galas.1.has_results', false));
});

it('hides an unpublished gala page from the public', function (): void {
    $gala = Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'is_published' => false,
    ]);

    $this->get(route('galas.show', $gala))->assertNotFound();
});

it('shows a published gala page with its venue', function (): void {
    $venue = Venue::factory()->create(['name' => 'Mutare Municipal City Pool']);

    $gala = Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $venue->getKey(),
    ]);

    $this->get(route('galas.show', $gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/gala')
            ->where('gala.venue.name', 'Mutare Municipal City Pool'));
});

it('lists clubs grouped under their province', function (): void {
    $this->get(route('clubs'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/clubs')
            ->count('provinces', 1)
            ->count('provinces.0.clubs', 1));
});

it('builds the officials pathway from the grading standards', function (): void {
    $this->get(route('officials'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/officials')
            ->count('categories', 8)
            ->where('categories.0.minimum_age', 13));
});

it('hides an unpublished news article', function (): void {
    $post = Post::factory()->create(['published_at' => null]);

    $this->get(route('news.show', $post))->assertNotFound();
});

it('shows a published news article', function (): void {
    $post = Post::factory()->create([
        'title' => 'The season is open',
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('news.show', $post))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/news-article')
            ->where('post.title', 'The season is open'));
});

it('shows the venue register with its grading', function (): void {
    Venue::factory()->create(['name' => 'Les Brown Pool']);

    $this->get(route('venues'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/venues')
            ->where('venues.0.name', 'Les Brown Pool')
            ->where('venues.0.grade_label', 'National'));
});

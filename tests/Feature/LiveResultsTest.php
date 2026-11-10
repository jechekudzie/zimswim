<?php

use App\Enums\Course;
use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Result;
use App\Models\Season;
use App\Models\Venue;
use App\Services\RelayService;
use App\Services\ResultService;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->season = Season::factory()->create(['age_up_date' => '2026-12-31']);
    $this->venue = Venue::factory()->create(['lanes' => 8]);

    $this->gala = Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => $this->venue->getKey(),
        'course' => Course::LongCourse,
        'status' => GalaStatus::InProgress,
        'starts_on' => now()->subDay()->toDateString(),
        'ends_on' => now()->addDay()->toDateString(),
        'age_up_date' => '2026-12-31',
        'is_published' => true,
    ]);
});

function publishEvent(int $number, array $timesMs, $context, bool $publish = true): GalaEvent
{
    $event = GalaEvent::factory()->create([
        'gala_id' => $context->gala->getKey(),
        'event_number' => $number,
        'gender' => 'girls',
        'age_group' => '11/12',
        'stroke' => Stroke::Freestyle,
        'distance' => 50,
    ]);

    foreach ($timesMs as $index => $timeMs) {
        $member = Member::factory()->create([
            'organization_id' => $context->club->getKey(),
            'first_name' => 'Live'.$number.$index,
            'status' => MemberStatus::Active,
        ]);

        $entry = GalaEntry::factory()->create([
            'gala_id' => $context->gala->getKey(),
            'gala_event_id' => $event->getKey(),
            'member_id' => $member->getKey(),
            'organization_id' => $context->club->getKey(),
            'status' => 'confirmed',
        ]);

        Result::factory()->create([
            'gala_id' => $context->gala->getKey(),
            'gala_event_id' => $event->getKey(),
            'gala_entry_id' => $entry->getKey(),
            'member_id' => $member->getKey(),
            'organization_id' => $context->club->getKey(),
            'time_ms' => $timeMs,
            'status' => 'official',
        ]);
    }

    if ($publish) {
        app(ResultService::class)->publish($event);
    }

    return $event->fresh();
}

it('shows the live board with the latest published event first', function (): void {
    publishEvent(1, [31_000, 32_000], $this);
    $this->travel(1)->minutes();
    publishEvent(2, [29_000, 30_000], $this);

    $this->get(route('results.live', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/live-results')
            ->where('isLive', true)
            ->where('latest.number', 2)
            ->where('progress.published', 2)
            ->where('progress.total', 2)
            ->where('earlier.0.number', 1));
});

it('never shows an event that has not been published', function (): void {
    publishEvent(1, [31_000], $this);
    publishEvent(2, [29_000], $this, publish: false);

    $this->get(route('results.live', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('latest.number', 1)
            ->where('progress.published', 1)
            ->where('progress.total', 2)
            ->where('earlier', []));
});

it('says a gala is not live when it is not being swum today', function (): void {
    $this->gala->update([
        'starts_on' => now()->subMonth()->toDateString(),
        'ends_on' => now()->subMonth()->addDay()->toDateString(),
        'status' => GalaStatus::Completed,
    ]);

    publishEvent(1, [31_000], $this);

    $this->get(route('results.live', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('isLive', false));
});

it('copes with a gala that has nothing published yet', function (): void {
    GalaEvent::factory()->create(['gala_id' => $this->gala->getKey()]);

    $this->get(route('results.live', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('latest', null)
            ->where('progress.percent', 0));
});

it('hides the live board for an unpublished gala', function (): void {
    $this->gala->update(['is_published' => false]);

    $this->get(route('results.live', $this->gala))->assertNotFound();
});

it('names a relay team rather than a swimmer in the results', function (): void {
    $relayEvent = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 90,
        'gender' => 'girls',
        'age_group' => '11/12',
        'min_age' => 11,
        'max_age' => 12,
        'stroke' => Stroke::FreestyleRelay,
        'distance' => 200,
        'is_relay' => true,
        'relay_legs' => 4,
    ]);

    $members = collect(range(1, 4))
        ->map(fn (int $leg): Member => Member::factory()->create([
            'organization_id' => $this->club->getKey(),
            'date_of_birth' => '2015-01-01',
            'gender' => 'female',
            'status' => MemberStatus::Active,
        ]))
        ->pluck('id')
        ->all();

    $relays = app(RelayService::class);
    $team = $relays->declare($relayEvent, $this->club->getKey(), 'A', $members, 130_000);
    $relays->record($team, ['time_ms' => 128_500]);

    app(ResultService::class)->publish($relayEvent->fresh());

    $this->get(route('results.live', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('latest.is_relay', true)
            ->where('latest.results.0.swimmer', $team->displayName()));
});

it('serves only the live props on a polling request', function (): void {
    publishEvent(1, [31_000], $this);

    // The asset version has to match or Inertia answers 409 and asks the
    // browser to reload, so it is read off a first visit.
    $version = $this->get(route('results.live', $this->gala))->viewData('page')['version'];

    $response = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'public/live-results',
        'X-Inertia-Partial-Data' => 'latest,progress,refreshedAt',
    ])->get(route('results.live', $this->gala));

    $response->assertOk();

    $props = $response->json('props');

    expect($props)->toHaveKeys(['latest', 'progress', 'refreshedAt']);
    expect($props)->not->toHaveKey('earlier');
});

it('serves the gala results page with the slug the live board is reached by', function (): void {
    publishEvent(1, [31_000], $this);

    $this->get(route('results.show', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/gala-results')
            ->where('gala.slug', $this->gala->slug));

    $this->get(route('results.live', $this->gala))->assertOk();
});

it('flags a gala being swum as live on the results index', function (): void {
    publishEvent(1, [31_000], $this);

    $this->get(route('results.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('galas.0.is_live', true));
});

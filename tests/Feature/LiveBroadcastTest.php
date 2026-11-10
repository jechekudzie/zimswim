<?php

use App\Enums\Course;
use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\Stroke;
use App\Events\EventResultsPublished;
use App\Events\ResultsRecorded;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Result;
use App\Models\Season;
use App\Models\Venue;
use App\Services\ResultsBoard;
use App\Services\ResultService;
use App\Support\OrganizationContext;
use App\Support\Roles;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->season = Season::factory()->create(['age_up_date' => '2026-12-31']);

    $this->gala = Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'venue_id' => Venue::factory()->create(['lanes' => 8])->getKey(),
        'course' => Course::LongCourse,
        'status' => GalaStatus::InProgress,
        'starts_on' => now()->subDay()->toDateString(),
        'ends_on' => now()->addDay()->toDateString(),
        'age_up_date' => '2026-12-31',
        'is_published' => true,
    ]);

    $this->event = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 101,
        'gender' => 'girls',
        'age_group' => '11/12',
        'stroke' => Stroke::Freestyle,
        'distance' => 50,
    ]);
});

/**
 * How many database queries a piece of work costs.
 */
function countQueries(callable $work): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $work();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

/**
 * One swimmer entered in the event, with a time already recorded against the
 * entry but not yet released.
 */
function recordedSwim(int $timeMs, object $context): GalaEntry
{
    $member = Member::factory()->create([
        'organization_id' => $context->club->getKey(),
        'date_of_birth' => '2014-05-01',
        'gender' => 'female',
        'status' => MemberStatus::Active,
    ]);

    $entry = GalaEntry::factory()->create([
        'gala_id' => $context->gala->getKey(),
        'gala_event_id' => $context->event->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $context->club->getKey(),
        'status' => 'confirmed',
    ]);

    Result::factory()->create([
        'gala_id' => $context->gala->getKey(),
        'gala_event_id' => $context->event->getKey(),
        'gala_entry_id' => $entry->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $context->club->getKey(),
        'time_ms' => $timeMs,
        'status' => 'official',
    ]);

    return $entry;
}

it('tells the public board to listen for published results on the gala channel', function (): void {
    recordedSwim(31_000, $this);
    app(ResultService::class)->publish($this->event);

    $page = $this->get(route('results.live', $this->gala))->viewData('page');

    expect($page['liveProps'])->toHaveKeys([
        'isLive', 'progress', 'latest', 'earlier', 'pointsTable', 'refreshedAt',
    ]);

    expect($page['liveProps']['latest']['listeners'])->toBe([[
        'channel' => ['name' => 'galas.'.$this->gala->slug, 'type' => 'public'],
        'events' => ['results.published'],
    ]]);
});

it('listens on a public channel, because published results are public', function (): void {
    $channels = (new EventResultsPublished($this->gala))->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->not->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('galas.'.$this->gala->slug);
});

it('says only which event it was, so the message can never outgrow the protocol', function (): void {
    // Carrying the board itself would be rejected as too large part-way
    // through a gala, and the board would quietly stop updating.
    recordedSwim(31_000, $this);
    app(ResultService::class)->publish($this->event);

    $payload = (new EventResultsPublished($this->gala, $this->event->fresh()))->broadcastWith();

    expect($payload)->toBe(['event_number' => 101])
        ->and(strlen((string) json_encode($payload)))->toBeLessThan(10_000);
});

it('answers the burst of readers that a broadcast causes from one reading of the board', function (): void {
    recordedSwim(31_000, $this);
    recordedSwim(32_000, $this);
    app(ResultService::class)->publish($this->event);

    $board = app(ResultsBoard::class);

    $first = countQueries(fn () => $board->board($this->gala));
    $second = countQueries(fn () => $board->board($this->gala));

    // The second reader pays only for the few aggregates that prove the
    // board has not changed.
    expect($second)->toBeLessThan($first)
        ->and($second)->toBeLessThanOrEqual(3);
});

it('reads the board again once a timekeeper corrects a time', function (): void {
    $entry = recordedSwim(31_000, $this);
    app(ResultService::class)->publish($this->event);

    $board = app(ResultsBoard::class);

    expect($board->board($this->gala)['latest']['results'][0]['time'])->toBe('31.00');

    // Results are stamped to the second, so that is the resolution the board
    // is held at. A correction is never more than the cache window behind.
    $this->travel(1)->second();

    app(ResultService::class)->record($entry, ['time_ms' => 30_500, 'status' => 'official']);
    app(ResultService::class)->publish($this->event->fresh());

    expect($board->board($this->gala)['latest']['results'][0]['time'])->toBe('30.50');
});

it('pushes the board the moment the referee publishes an event', function (): void {
    Event::fake([EventResultsPublished::class]);

    recordedSwim(31_000, $this);
    app(ResultService::class)->publish($this->event);

    Event::assertDispatched(
        EventResultsPublished::class,
        fn (EventResultsPublished $broadcast): bool => $broadcast->gala->is($this->gala)
            && $broadcast->event?->is($this->event) === true,
    );
});

it('still publishes the results when the websocket server cannot be reached', function (): void {
    Event::listen(EventResultsPublished::class, function (): void {
        throw new RuntimeException('Connection refused');
    });

    recordedSwim(31_000, $this);
    app(ResultService::class)->publish($this->event);

    expect($this->event->fresh()->results_published_at)->not->toBeNull();
});

it('tells the other recording screens when a timekeeper saves a heat', function (): void {
    Event::fake([ResultsRecorded::class]);

    $entry = recordedSwim(31_000, $this);
    $official = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($official)
        ->from(route('admin.galas.results.edit', [$this->gala, $this->event]))
        ->post(route('admin.galas.results.store', [$this->gala, $this->event]), [
            'results' => [
                ['gala_entry_id' => $entry->getKey(), 'time' => '30.80', 'status' => 'official'],
            ],
        ])
        ->assertRedirect();

    Event::assertDispatched(
        ResultsRecorded::class,
        fn (ResultsRecorded $broadcast): bool => $broadcast->event->is($this->event),
    );
});

it('keeps times that are not yet published on a private channel', function (): void {
    $channels = (new ResultsRecorded($this->gala, $this->event))->broadcastOn();

    expect($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-galas.'.$this->gala->slug.'.recording');
});

/**
 * A channel callback is authorised the same way a page is, from the roles the
 * user holds at the organisation they are acting within. A real subscription
 * arrives over HTTP and has that set for it by middleware, so it is set here.
 */
function actingWithin(Organization $organization): void
{
    app(OrganizationContext::class)->set($organization);
    setPermissionsTeamId($organization->getKey());
}

it('lets someone who enters times listen to the ones other people enter', function (): void {
    $official = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);
    actingWithin($this->national);

    expect(ResultsRecorded::canListen($official, $this->gala->slug))->toBeTrue();
});

it('refuses the recording channel to a swimmer who may only read results', function (): void {
    // A swimmer holds "view results" so they can look up their own swims. That
    // must not reach times the referee has not signed off yet.
    $swimmer = userWithRole(Roles::SWIMMER, $this->club);
    actingWithin($this->club);

    expect(ResultsRecorded::canListen($swimmer, $this->gala->slug))->toBeFalse()
        ->and(ResultsRecorded::canListen($swimmer, 'no-such-gala'))->toBeFalse();
});

it('does not ask a read-only viewer to subscribe to a channel they cannot join', function (): void {
    $swimmer = userWithRole(Roles::SWIMMER, $this->club);

    $page = $this->actingAs($swimmer)
        ->get(route('admin.galas.results.index', $this->gala))
        ->viewData('page');

    expect($page)->not->toHaveKey('liveProps');
});

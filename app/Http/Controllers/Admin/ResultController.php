<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisqualificationCode;
use App\Enums\GalaStatus;
use App\Events\EventResultsPublished;
use App\Events\ResultsRecorded;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Organization;
use App\Models\RelayTeam;
use App\Notifications\ResultsPublished;
use App\Services\RelayService;
use App\Services\ResultService;
use App\Support\Broadcasts;
use App\Support\NoticeRecipients;
use App\Support\Permissions;
use App\Support\SwimTime;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Capturing times on the day: one screen per event, heat by heat.
 */
class ResultController extends Controller
{
    public function __construct(
        private readonly ResultService $results,
        private readonly RelayService $relays,
    ) {}

    public function index(Gala $gala): Response
    {
        $this->authorize('view', $gala);

        $gala->load('events.session');

        return Inertia::render('admin/galas/results', [
            'gala' => [
                'slug' => $gala->slug,
                'name' => $gala->name,
                'status_label' => $gala->status->label(),
            ],
            'events' => $this->liveProp($gala, $gala->events->map(fn (GalaEvent $event): array => [
                'id' => $event->getKey(),
                'number' => $event->event_number,
                'title' => $event->title(),
                'age_group' => $event->age_group,
                'session' => $event->session?->name,
                'is_relay' => $event->is_relay,
                'seeded' => $event->seeded_at !== null,
                'published' => $event->results_published_at !== null,
                'entries' => $event->is_relay
                    ? $event->relayTeams()->count()
                    : $event->entries()->count(),
                'recorded' => $event->results()->count(),
            ])),
            'pointsTable' => $this->liveProp($gala, request()->user()->can('recordResults', $gala)
                ? $this->results->pointsTable($gala->getKey())
                : $this->results->publishedPointsTable($gala->getKey())),
            'can' => ['record' => request()->user()->can('recordResults', $gala)],
        ]);
    }

    /**
     * Mark a prop as one that keeps itself up to date while a gala is running.
     *
     * Two or three people enter times at once on a gala day, on separate
     * tablets, and a fourth watches the event list waiting to publish. These
     * props refresh themselves as each of them saves, so nobody works from a
     * screen that went stale ten minutes ago.
     *
     * Someone who may read these screens but not enter times is not allowed on
     * the recording channel, so they are handed a plain prop rather than one
     * that would try to subscribe and be turned away.
     *
     * The event classes are named rather than instantiated because neither
     * one's channel depends on anything but the gala, which is passed here.
     */
    private function liveProp(Gala $gala, mixed $value): mixed
    {
        if (! request()->user()->can('recordResults', $gala)) {
            return $value;
        }

        return Inertia::live(
            $value,
            on: ResultsRecorded::class,
            channel: new PrivateChannel(ResultsRecorded::channelFor($gala)),
        )->live(
            on: EventResultsPublished::class,
            channel: new Channel(EventResultsPublished::channelFor($gala)),
        );
    }

    /**
     * The recording screen, which shows times the referee has not released.
     *
     * Entering times is what this screen is for, and the federation's rule is
     * that nobody else sees a time before the referee signs it off: times are
     * corrected here, and a swimmer who read 31.05 off this screen and then
     * saw 31.50 published would have every reason to think a result had been
     * altered. So this asks for the right to record, not merely to read.
     */
    public function edit(Gala $gala, GalaEvent $event): Response
    {
        $this->authorize('recordResults', $gala);

        if ($event->is_relay) {
            return $this->editRelay($gala, $event);
        }

        $entries = $event->entries()
            ->whereIn('status', ['entered', 'confirmed', 'no_show'])
            ->with(['member:id,first_name,last_name', 'club:id,name'])
            ->orderBy('heat')
            ->orderBy('lane')
            ->get();

        $event->load('results');

        return Inertia::render('admin/galas/record-event', [
            'gala' => ['slug' => $gala->slug, 'name' => $gala->name],
            'event' => $this->liveProp($gala, [
                'id' => $event->getKey(),
                'number' => $event->event_number,
                'title' => $event->title(),
                'age_group' => $event->age_group,
                'is_relay' => false,
                'seeded' => $event->seeded_at !== null,
                'published' => $event->results_published_at !== null,
            ]),
            'entries' => $this->liveProp($gala, $entries->map(function (GalaEntry $entry) use ($event): array {
                $result = $event->results->firstWhere('gala_entry_id', $entry->getKey());

                return [
                    'id' => $entry->getKey(),
                    'heat' => $entry->heat,
                    'lane' => $entry->lane,
                    'swimmer' => $entry->member->fullName(),
                    'club' => $entry->club->name,
                    'entry_time' => $entry->entryTime(),
                    'recorded_time' => $result !== null && $result->time_ms !== null
                        ? SwimTime::format($result->time_ms)
                        : '',
                    'status' => $result->status ?? 'official',
                    'dq_code' => $result->dq_code ?? null,
                    'place' => $result->place ?? null,
                    'is_personal_best' => (bool) ($result->is_personal_best ?? false),
                    'record_flagged' => (bool) ($result->record_flagged ?? false),
                ];
            })),
            'disqualificationCodes' => DisqualificationCode::options(),
            'can' => ['record' => request()->user()->can('recordResults', $gala)],
        ]);
    }

    /**
     * The same recording screen for a relay, where a lane holds a team rather
     * than a swimmer.
     */
    private function editRelay(Gala $gala, GalaEvent $event): Response
    {
        $teams = RelayTeam::query()
            ->where('gala_event_id', $event->getKey())
            ->with(['club:id,name', 'swimmers.member:id,first_name,last_name'])
            ->orderBy('heat')
            ->orderBy('lane')
            ->get();

        $results = $event->results()->whereNotNull('relay_team_id')->get();

        return Inertia::render('admin/galas/record-event', [
            'gala' => ['slug' => $gala->slug, 'name' => $gala->name],
            'event' => $this->liveProp($gala, [
                'id' => $event->getKey(),
                'number' => $event->event_number,
                'title' => $event->title(),
                'age_group' => $event->age_group,
                'is_relay' => true,
                'seeded' => $event->seeded_at !== null,
                'published' => $event->results_published_at !== null,
            ]),
            'entries' => $this->liveProp($gala, $teams->map(function (RelayTeam $team) use ($results): array {
                $result = $results->firstWhere('relay_team_id', $team->getKey());

                return [
                    'id' => $team->getKey(),
                    'heat' => $team->heat,
                    'lane' => $team->lane,
                    'swimmer' => $team->displayName(),
                    'club' => $team->club->name,
                    'legs' => $team->swimmers
                        ->map(fn ($swimmer): string => $swimmer->member->fullName())
                        ->all(),
                    'scores_points' => $team->scoresPoints(),
                    'entry_time' => $team->entryTime(),
                    'recorded_time' => $result !== null && $result->time_ms !== null
                        ? SwimTime::format($result->time_ms)
                        : '',
                    'status' => $result->status ?? 'official',
                    'dq_code' => $result->dq_code ?? null,
                    'place' => $result->place ?? null,
                    'is_personal_best' => false,
                    'record_flagged' => (bool) ($result->record_flagged ?? false),
                ];
            })),
            'disqualificationCodes' => DisqualificationCode::options(),
            'can' => ['record' => request()->user()->can('recordResults', $gala)],
        ]);
    }

    /**
     * Save a whole event's times in one go, which is how a recorder works
     * through a heat on a tablet.
     */
    public function store(Request $request, Gala $gala, GalaEvent $event): RedirectResponse
    {
        $this->authorize('recordResults', $gala);

        $table = $event->is_relay ? 'relay_teams' : 'gala_entries';

        $validated = $request->validate([
            'results' => ['required', 'array'],
            'results.*.gala_entry_id' => [
                'required', 'integer',
                Rule::exists($table, 'id')->where('gala_event_id', $event->getKey()),
            ],
            'results.*.time' => ['nullable', 'string', 'max:16'],
            'results.*.status' => ['required', Rule::in(['official', 'dq', 'dns', 'dnf'])],
            'results.*.dq_code' => ['nullable', Rule::enum(DisqualificationCode::class)],
        ]);

        $recorded = 0;

        foreach ($validated['results'] as $row) {
            $timeMs = SwimTime::tryParse($row['time'] ?? null);

            if ($row['status'] === 'official' && $timeMs === null) {
                continue;
            }

            $payload = [
                'time_ms' => $timeMs,
                'status' => $row['status'],
                'dq_code' => $row['dq_code'] ?? null,
            ];

            $competitorId = (int) $row['gala_entry_id'];

            if ($event->is_relay) {
                $this->relays->record(
                    RelayTeam::query()->findOrFail($competitorId),
                    $payload,
                    $request->user()->getKey(),
                );
            } else {
                $this->results->record(
                    GalaEntry::query()->findOrFail($competitorId),
                    $payload,
                    $request->user()->getKey(),
                );
            }

            $recorded++;
        }

        $this->results->placeEvent($event);
        $records = $this->results->proposedRecords($event);

        // Put the other recording screens in step. The tablet that saved is
        // already getting the new places back in this response.
        Broadcasts::quietlyToOthers(new ResultsRecorded($gala, $event));

        if ($gala->status->isSanctioned() && $gala->status !== GalaStatus::Completed) {
            $gala->forceFill(['status' => GalaStatus::InProgress])->save();
        }

        $message = $recorded.' '.($recorded === 1 ? 'swim' : 'swims').' recorded.';

        if ($records > 0) {
            $message .= ' '.$records.' proposed as a record, awaiting ratification.';
        }

        return back()->with('success', $message);
    }

    public function publish(Gala $gala, GalaEvent $event): RedirectResponse
    {
        $this->authorize('recordResults', $gala);

        $this->results->publish($event);

        $this->announceResults($gala, $event);

        return back()->with('success', 'Event '.$event->event_number.' results published.');
    }

    /**
     * Tell the clubs that had a swimmer in this event that it is now public.
     * The 30-minute protest window runs from publication, so this is the
     * notice that starts their clock.
     */
    private function announceResults(Gala $gala, GalaEvent $event): void
    {
        // reorder() first: the relation is ordered by place, and Postgres will
        // not accept an ORDER BY column that a SELECT DISTINCT does not select.
        $clubIds = $event->results()->reorder()->distinct()->pluck('organization_id');
        $notification = new ResultsPublished($gala, $event->title(), $event->event_number);

        Organization::query()
            ->whereKey($clubIds)
            ->get()
            ->each(fn (Organization $club) => NoticeRecipients::at($club, Permissions::VIEW_RESULTS)
                ->each(fn ($user) => $user->notify($notification)));
    }
}

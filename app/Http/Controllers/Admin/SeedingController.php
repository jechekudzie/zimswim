<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GalaStatus;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\Organization;
use App\Notifications\HeatSheetPublished;
use App\Services\RelayService;
use App\Services\SeedingService;
use App\Support\NoticeRecipients;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Seeding turns entries into heats and lanes, and produces the heat sheet the
 * meet is run from.
 *
 * Individual and relay events seed on the same rules — slowest heat first,
 * lanes from the centre out — but one deals in swimmers and the other in
 * teams, so each has its own path through here.
 */
class SeedingController extends Controller
{
    public function __construct(
        private readonly SeedingService $seeding,
        private readonly RelayService $relays,
    ) {}

    public function show(Gala $gala): Response
    {
        $this->authorize('view', $gala);

        $gala->load(['venue:id,name,lanes', 'events.session']);

        return Inertia::render('admin/galas/heat-sheet', [
            'gala' => [
                'slug' => $gala->slug,
                'name' => $gala->name,
                'venue' => $gala->venue?->name,
                'lanes' => $gala->laneCount(),
                'status_label' => $gala->status->label(),
            ],
            'events' => $gala->events->map(fn (GalaEvent $event): array => [
                'id' => $event->getKey(),
                'number' => $event->event_number,
                'title' => $event->title(),
                'age_group' => $event->age_group,
                'session' => $event->session?->name,
                'is_relay' => $event->is_relay,
                'seeded' => $event->seeded_at !== null,
                'entry_count' => $this->entryCount($event),
                'heats' => $event->is_relay
                    ? $this->relays->heatSheet($event)
                    : $this->seeding->heatSheet($event),
            ]),
            'can' => ['seed' => request()->user()->can('recordResults', $gala)],
        ]);
    }

    /**
     * Seed every event in the gala, or one event when an id is given.
     */
    public function store(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('recordResults', $gala);

        $validated = $request->validate([
            'gala_event_id' => ['nullable', 'integer', 'exists:gala_events,id'],
        ]);

        $events = $validated['gala_event_id'] ?? null
            ? $gala->events()->whereKey($validated['gala_event_id'])->get()
            : $gala->events()->get();

        $seeded = 0;
        $swimmers = 0;

        foreach ($events as $event) {
            if ($event->is_relay) {
                $outcome = $this->relays->seed($event, $this->seeding, $gala->laneCount());
                $seeded += $outcome['heats'] > 0 ? 1 : 0;
                $swimmers += $outcome['teams'];

                continue;
            }

            $outcome = $this->seeding->seed($event, $gala->laneCount());
            $seeded += $outcome['heats'] > 0 ? 1 : 0;
            $swimmers += $outcome['swimmers'];
        }

        if ($gala->status === GalaStatus::EntriesClosed) {
            $gala->forceFill(['status' => GalaStatus::InProgress])->save();
        }

        if ($seeded > 0) {
            $this->announceHeatSheet($gala, $seeded);
        }

        return back()->with(
            'success',
            $seeded.' '.($seeded === 1 ? 'event' : 'events').' seeded for '.$swimmers.' swims.',
        );
    }

    /**
     * Tell the clubs with swimmers in this gala that their heats and lanes are
     * ready. Only clubs who entered are told, and each is told once however
     * many events were seeded.
     */
    private function announceHeatSheet(Gala $gala, int $events): void
    {
        $clubIds = $gala->entries()->distinct()->pluck('organization_id')
            ->merge($gala->relayTeams()->distinct()->pluck('organization_id'))
            ->unique();

        $notification = new HeatSheetPublished($gala, $events);

        Organization::query()
            ->whereKey($clubIds)
            ->get()
            ->each(fn (Organization $club) => NoticeRecipients::at($club, Permissions::MANAGE_ENTRIES)
                ->each(fn ($user) => $user->notify($notification)));
    }

    private function entryCount(GalaEvent $event): int
    {
        if ($event->is_relay) {
            return $event->relayTeams()->whereIn('status', ['entered', 'confirmed'])->count();
        }

        return $event->entries()->whereIn('status', ['entered', 'confirmed'])->count();
    }
}

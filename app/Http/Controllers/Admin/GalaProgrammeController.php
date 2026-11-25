<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Stroke;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\GalaSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Building a gala's programme: its sessions, and the events in each.
 *
 * The public gala page has always been able to show a programme and there
 * was no way to enter one, so every gala but the sprint gala — which was
 * seeded — said the programme was not published yet. A championship
 * programme barely changes from season to season; it needs typing once.
 */
class GalaProgrammeController extends Controller
{
    public function index(Gala $gala): Response
    {
        $this->authorize('view', $gala);

        $gala->load(['sessions.events' => fn ($query) => $query->orderBy('event_number')]);

        return Inertia::render('admin/galas/programme', [
            'gala' => [
                'id' => $gala->getKey(),
                'slug' => $gala->slug,
                'name' => $gala->name,
                'starts_on' => $gala->starts_on->toDateString(),
                'ends_on' => $gala->ends_on->toDateString(),
            ],
            'sessions' => $gala->sessions->map(fn (GalaSession $session): array => [
                'id' => $session->getKey(),
                'name' => $session->name,
                'position' => $session->position,
                'held_on' => $session->held_on->toDateString(),
                'warmup_at' => $this->clock($session->warmup_at),
                'starts_at' => $this->clock($session->starts_at),
                'events' => $session->events->map(fn (GalaEvent $event): array => [
                    'id' => $event->getKey(),
                    'event_number' => $event->event_number,
                    'gender' => $event->gender,
                    'age_group' => $event->age_group,
                    'min_age' => $event->min_age,
                    'max_age' => $event->max_age,
                    'stroke' => $event->stroke->value,
                    'stroke_label' => $event->stroke->label(),
                    'distance' => $event->distance,
                    'round' => $event->round,
                    'is_relay' => $event->is_relay,
                    'relay_legs' => $event->relay_legs,
                    // Seeded or swum, so the screen can warn before a change
                    // reaches something already under way.
                    'is_locked' => $event->seeded_at !== null || $event->results_published_at !== null,
                ]),
            ]),
            'strokes' => Stroke::options(),
            'can' => ['manage' => request()->user()->can('update', $gala)],
        ]);
    }

    public function storeSession(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('update', $gala);

        $gala->sessions()->create([
            ...$this->validatedSession($request),
            // Appended rather than inserted: a session added later is the
            // next one on the programme unless it is moved deliberately.
            'position' => ($gala->sessions()->max('position') ?? 0) + 1,
        ]);

        return back()->with('success', 'Session added.');
    }

    public function updateSession(Request $request, Gala $gala, GalaSession $session): RedirectResponse
    {
        $this->authorize('update', $gala);
        abort_unless($session->gala_id === $gala->getKey(), 404);

        $session->update($this->validatedSession($request));

        return back()->with('success', 'Session updated.');
    }

    public function destroySession(Gala $gala, GalaSession $session): RedirectResponse
    {
        $this->authorize('update', $gala);
        abort_unless($session->gala_id === $gala->getKey(), 404);

        abort_if(
            $session->events()->exists(),
            422,
            'Move or remove this session\'s events first. Deleting it would take them with it.',
        );

        $session->delete();

        return back()->with('success', 'Session removed.');
    }

    public function storeEvent(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('update', $gala);

        $gala->events()->create($this->validatedEvent($request, $gala));

        return back()->with('success', 'Event added.');
    }

    public function updateEvent(Request $request, Gala $gala, GalaEvent $event): RedirectResponse
    {
        $this->authorize('update', $gala);
        abort_unless($event->gala_id === $gala->getKey(), 404);

        $event->update($this->validatedEvent($request, $gala, $event));

        return back()->with('success', 'Event updated.');
    }

    public function destroyEvent(Gala $gala, GalaEvent $event): RedirectResponse
    {
        $this->authorize('update', $gala);
        abort_unless($event->gala_id === $gala->getKey(), 404);

        /*
         * Once an event has been seeded or its results published, swimmers
         * and times hang off it. Removing it at that point loses them, and
         * that is not a decision for a programme screen.
         */
        abort_if(
            $event->seeded_at !== null || $event->results_published_at !== null,
            422,
            'This event has been seeded or swum. It cannot be removed from the programme.',
        );

        $event->delete();

        return back()->with('success', 'Event removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSession(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'held_on' => ['required', 'date'],
            'warmup_at' => ['nullable', 'date_format:H:i'],
            'starts_at' => ['nullable', 'date_format:H:i'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedEvent(Request $request, Gala $gala, ?GalaEvent $event = null): array
    {
        $validated = $request->validate([
            'gala_session_id' => [
                'required', 'integer',
                Rule::exists('gala_sessions', 'id')->where('gala_id', $gala->getKey()),
            ],
            'event_number' => [
                'required', 'integer', 'min:1', 'max:9999',
                Rule::unique('gala_events', 'event_number')
                    ->where('gala_id', $gala->getKey())
                    ->ignore($event?->getKey()),
            ],
            'gender' => ['required', Rule::in(['girls', 'boys', 'mixed'])],
            'age_group' => ['required', 'string', 'max:40'],
            'min_age' => ['nullable', 'integer', 'min:5', 'max:99'],
            'max_age' => ['nullable', 'integer', 'min:5', 'max:99', 'gte:min_age'],
            'stroke' => ['required', Rule::enum(Stroke::class)],
            'distance' => ['required', 'integer', 'min:25', 'max:10000'],
            'round' => ['required', Rule::in(['timed_final', 'heat', 'final', 'semi_final'])],
            'is_relay' => ['boolean'],
            'relay_legs' => ['nullable', 'integer', 'min:2', 'max:10'],
        ]);

        // A relay without legs is a distance nobody can swim, and legs on an
        // individual event are meaningless. Keep the pair honest.
        $validated['is_relay'] = $validated['is_relay'] ?? false;
        $validated['relay_legs'] = $validated['is_relay'] ? ($validated['relay_legs'] ?? 4) : null;

        return $validated;
    }

    /**
     * Times come back from the database with seconds nobody typed.
     */
    private function clock(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}

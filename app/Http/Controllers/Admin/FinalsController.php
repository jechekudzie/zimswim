<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\Result;
use App\Services\FinalsService;
use App\Support\SwimTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Promoting swimmers from heats into a final.
 *
 * The screen shows who is through on their heat time before anything is
 * written, so the referee can see the field, and names anyone who has to swim
 * off for the last place rather than quietly picking one of them.
 */
class FinalsController extends Controller
{
    public function __construct(private readonly FinalsService $finals) {}

    public function index(Request $request, Gala $gala): Response
    {
        $this->authorize('view', $gala);

        $finals = $this->finals->finals($gala->getKey());

        return Inertia::render('admin/galas/finals', [
            'gala' => [
                'slug' => $gala->slug,
                'name' => $gala->name,
                'lanes' => $gala->laneCount(),
            ],
            'finals' => $finals->map(function (GalaEvent $final) use ($gala): array {
                $heats = $final->parent;
                $recorded = $heats?->results()->official()->whereNotNull('time_ms')->count() ?? 0;

                $assessment = $recorded > 0
                    ? $this->finals->assess($final)
                    : ['qualifiers' => collect(), 'swim_off' => collect(), 'contested_places' => 0];

                return [
                    'id' => $final->getKey(),
                    'number' => $final->event_number,
                    'title' => $final->title(),
                    'age_group' => $final->age_group,
                    'places' => $final->qualifiers ?? $gala->laneCount(),
                    'heats_event' => $heats === null ? null : [
                        'number' => $heats->event_number,
                        'recorded' => $recorded,
                        'published' => $heats->results_published_at !== null,
                    ],
                    'entered' => $final->entries()->count(),
                    'seeded' => $final->seeded_at !== null,
                    'contested_places' => $assessment['contested_places'],
                    'qualifiers' => $assessment['qualifiers']->values()->map(fn (Result $result, int $index): array => [
                        'rank' => $index + 1,
                        'swimmer' => $result->competitorName(),
                        'club' => $result->club->name,
                        'time' => $result->time_ms === null ? '—' : SwimTime::format($result->time_ms),
                    ]),
                    'swim_off' => $assessment['swim_off']->values()->map(fn (Result $result): array => [
                        'swimmer' => $result->competitorName(),
                        'club' => $result->club->name,
                        'time' => $result->time_ms === null ? '—' : SwimTime::format($result->time_ms),
                    ]),
                ];
            }),
            'can' => ['promote' => $request->user()->can('recordResults', $gala)],
        ]);
    }

    public function promote(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('recordResults', $gala);

        $validated = $request->validate([
            'gala_event_id' => [
                'required', 'integer',
                Rule::exists('gala_events', 'id')->where('gala_id', $gala->getKey()),
            ],
        ]);

        $final = GalaEvent::query()->findOrFail((int) $validated['gala_event_id']);
        $outcome = $this->finals->promote($final, $request->user()->getKey());

        $message = $outcome['entered'].' '.($outcome['entered'] === 1 ? 'swimmer' : 'swimmers')
            .' promoted into event '.$final->event_number.' and seeded.';

        if ($outcome['swim_off'] > 0) {
            $message .= ' '.$outcome['swim_off'].' swimmers are tied for '
                .$outcome['contested_places'].' remaining '
                .($outcome['contested_places'] === 1 ? 'place' : 'places')
                .' and must swim off.';
        }

        return back()->with('success', $message);
    }
}

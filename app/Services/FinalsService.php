<?php

namespace App\Services;

use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Taking a field from heats through to a final.
 *
 * The qualifiers are the fastest swimmers from the heats, however the heats
 * were spread. They are entered into the final with their heat time, and the
 * final is seeded so the fastest qualifier takes the centre lane.
 *
 * A tie on the last qualifying place cannot be settled on time, so it is not
 * settled at all: the swimmers who are clearly through are promoted, the
 * contested place is left open, and the referee orders a swim-off.
 */
class FinalsService
{
    public function __construct(private readonly SeedingService $seeding) {}

    /**
     * Work out who is through and who has to swim off.
     *
     * @return array{
     *     qualifiers: Collection<int, Result>,
     *     swim_off: Collection<int, Result>,
     *     contested_places: int,
     * }
     */
    public function assess(GalaEvent $final): array
    {
        $heats = $this->heatsFor($final);
        $places = $final->qualifiers ?? $final->gala->laneCount();

        // Strictly on time: the relation's default order is by place, which
        // would otherwise decide who qualifies.
        $times = $heats->results()
            ->official()
            ->whereNotNull('time_ms')
            ->with(['member:id,first_name,last_name', 'club:id,name'])
            ->reorder('time_ms')
            ->get();

        $empty = collect();

        if ($times->count() <= $places) {
            return ['qualifiers' => $times, 'swim_off' => $empty, 'contested_places' => 0];
        }

        $cutoff = $times->get($places - 1)?->time_ms;

        if ($cutoff === null) {
            return ['qualifiers' => $times->take($places), 'swim_off' => $empty, 'contested_places' => 0];
        }

        $clear = $times->filter(fn (Result $result): bool => $result->time_ms < $cutoff)->values();
        $tied = $times->filter(fn (Result $result): bool => $result->time_ms === $cutoff)->values();
        $remaining = $places - $clear->count();

        // Everyone on the cut-off time fits, so nobody needs to swim off.
        if ($tied->count() <= $remaining) {
            return [
                'qualifiers' => $clear->concat($tied)->values(),
                'swim_off' => $empty,
                'contested_places' => 0,
            ];
        }

        return [
            'qualifiers' => $clear,
            'swim_off' => $tied,
            'contested_places' => $remaining,
        ];
    }

    /**
     * Enter the qualifiers into the final and seed it.
     *
     * Any entries already in the final are cleared first, so this can be run
     * again after a heat is re-timed or a disqualification is overturned.
     *
     * @return array{entered: int, heats: int, swim_off: int, contested_places: int}
     */
    public function promote(GalaEvent $final, ?int $enteredBy = null): array
    {
        $heats = $this->heatsFor($final);

        if ($heats->results()->official()->whereNotNull('time_ms')->doesntExist()) {
            throw ValidationException::withMessages([
                'gala_event_id' => 'The heats for event '.$final->event_number.' have no times yet.',
            ]);
        }

        $assessment = $this->assess($final);

        DB::transaction(function () use ($final, $assessment, $enteredBy): void {
            $final->entries()->delete();

            foreach ($assessment['qualifiers'] as $result) {
                GalaEntry::create([
                    'gala_id' => $final->gala_id,
                    'gala_event_id' => $final->getKey(),
                    'member_id' => $result->member_id,
                    'organization_id' => $result->organization_id,
                    'entry_time_ms' => $result->time_ms,
                    'status' => 'confirmed',
                    'is_late_entry' => false,
                    'entered_by' => $enteredBy,
                ]);
            }
        });

        $outcome = $this->seeding->seed($final->fresh() ?? $final, $final->gala->laneCount());

        return [
            'entered' => $assessment['qualifiers']->count(),
            'heats' => $outcome['heats'],
            'swim_off' => $assessment['swim_off']->count(),
            'contested_places' => $assessment['contested_places'],
        ];
    }

    /**
     * The heats this final is swum from.
     */
    public function heatsFor(GalaEvent $final): GalaEvent
    {
        if ($final->parent_event_id === null) {
            throw ValidationException::withMessages([
                'gala_event_id' => 'Event '.$final->event_number.' is not linked to a heats event.',
            ]);
        }

        return GalaEvent::query()->findOrFail($final->parent_event_id);
    }

    /**
     * Finals in a gala that have heats behind them, with those heats' progress
     * so the screen can say which are ready to promote.
     *
     * @return Collection<int, GalaEvent>
     */
    public function finals(int $galaId): Collection
    {
        return GalaEvent::query()
            ->where('gala_id', $galaId)
            ->whereNotNull('parent_event_id')
            ->with('parent')
            ->orderBy('event_number')
            ->get();
    }
}

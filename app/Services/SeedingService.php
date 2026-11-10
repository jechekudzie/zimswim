<?php

namespace App\Services;

use App\Models\GalaEntry;
use App\Models\GalaEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns the entries for an event into heats and lanes.
 *
 * Heats run slowest first, so heat one is the slowest and the final heat is the
 * fastest. Inside a heat, lanes are filled from the centre outwards, which is
 * the standard seeding used at every level of the sport.
 */
class SeedingService
{
    /**
     * The order lanes are filled in, fastest swimmer first: the middle lane,
     * then alternating outwards.
     *
     * @return array<int, int>
     */
    public function laneOrder(int $lanes): array
    {
        $centre = intdiv($lanes + 1, 2);
        $order = [$centre];

        for ($offset = 1; count($order) < $lanes; $offset++) {
            if ($centre + $offset <= $lanes) {
                $order[] = $centre + $offset;
            }

            if ($centre - $offset >= 1 && count($order) < $lanes) {
                $order[] = $centre - $offset;
            }

            if ($offset > $lanes) {
                break;
            }
        }

        return $order;
    }

    /**
     * How many swimmers belong in each heat, listed fastest heat first.
     *
     * Heats are normally filled to capacity from the fastest down. If that
     * would leave the slowest heat with only one or two swimmers, the field is
     * spread evenly instead so nobody swims alone.
     *
     * @return array<int, int>
     */
    public function heatSizes(int $swimmers, int $lanes): array
    {
        if ($swimmers <= 0 || $lanes <= 0) {
            return [];
        }

        $heats = (int) ceil($swimmers / $lanes);

        if ($heats === 1) {
            return [$swimmers];
        }

        $remainder = $swimmers % $lanes;

        if ($remainder !== 0 && $remainder < 3) {
            $base = intdiv($swimmers, $heats);
            $extra = $swimmers % $heats;

            return array_map(
                fn (int $index): int => $base + ($index < $extra ? 1 : 0),
                range(0, $heats - 1),
            );
        }

        $sizes = array_fill(0, $heats, $lanes);
        $sizes[$heats - 1] = $remainder === 0 ? $lanes : $remainder;

        return $sizes;
    }

    /**
     * Seed an event, writing the heat and lane onto every confirmed entry.
     *
     * @return array{heats: int, swimmers: int, lanes: int}
     */
    public function seed(GalaEvent $event, ?int $lanes = null): array
    {
        $lanes ??= $event->gala->laneCount();

        $entries = $this->seedableEntries($event);

        if ($entries->isEmpty()) {
            $event->forceFill(['seeded_at' => now()])->save();

            return ['heats' => 0, 'swimmers' => 0, 'lanes' => $lanes];
        }

        $sizes = $this->heatSizes($entries->count(), $lanes);
        $heatCount = count($sizes);
        $laneOrder = $this->laneOrder($lanes);

        $position = 0;

        DB::transaction(function () use ($entries, $sizes, $heatCount, $laneOrder, &$position, $event): void {
            foreach ($sizes as $index => $size) {
                // The first slice is the fastest, and the fastest swim last.
                $heatNumber = $heatCount - $index;

                foreach ($entries->slice($position, $size)->values() as $seat => $entry) {
                    $entry->forceFill([
                        'heat' => $heatNumber,
                        'lane' => $laneOrder[$seat] ?? null,
                    ])->save();
                }

                $position += $size;
            }

            $event->forceFill(['seeded_at' => now()])->save();
        });

        return ['heats' => $heatCount, 'swimmers' => $entries->count(), 'lanes' => $lanes];
    }

    /**
     * Entries in seeding order: fastest first, with no-time swimmers last so
     * they land in the slowest heat.
     *
     * @return Collection<int, GalaEntry>
     */
    public function seedableEntries(GalaEvent $event): Collection
    {
        return $event->entries()
            ->whereIn('status', ['entered', 'confirmed'])
            ->with('member:id,first_name,last_name,organization_id', 'club:id,name')
            ->get()
            ->sortBy([
                fn (GalaEntry $a, GalaEntry $b): int => ($a->entry_time_ms === null ? 1 : 0) <=> ($b->entry_time_ms === null ? 1 : 0),
                fn (GalaEntry $a, GalaEntry $b): int => ($a->entry_time_ms ?? PHP_INT_MAX) <=> ($b->entry_time_ms ?? PHP_INT_MAX),
            ])
            ->values();
    }

    /**
     * The heat sheet for an event, ready to print or publish.
     *
     * @return array<int, array{heat: int, lanes: array<int, array<string, mixed>>}>
     */
    public function heatSheet(GalaEvent $event): array
    {
        $entries = $event->entries()
            ->whereNotNull('heat')
            ->whereIn('status', ['entered', 'confirmed'])
            ->with(['member:id,first_name,last_name', 'club:id,name'])
            ->orderBy('heat')
            ->orderBy('lane')
            ->get();

        return $entries
            ->groupBy('heat')
            ->map(fn (Collection $heat, int|string $number): array => [
                'heat' => (int) $number,
                'lanes' => $heat->map(fn (GalaEntry $entry): array => [
                    'lane' => $entry->lane,
                    'swimmer' => $entry->member->fullName(),
                    'club' => $entry->club->name,
                    'entry_time' => $entry->entryTime(),
                ])->values()->all(),
            ])
            ->sortBy('heat')
            ->values()
            ->all();
    }
}

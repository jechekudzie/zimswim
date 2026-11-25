<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FeeSchedule;
use App\Models\Gala;
use App\Models\Season;
use Inertia\Inertia;
use Inertia\Response;

/**
 * What the season costs, on one page.
 *
 * The figures were already on the site, scattered: capitation in the
 * handbook, entry fees on each gala, late fees nowhere a club could find
 * before the deadline passed. A treasurer budgeting for a season should not
 * have to open eleven galas to add them up.
 */
class FeeController extends Controller
{
    public function __invoke(): Response
    {
        $season = Season::where('is_current', true)->first();

        $fees = $season === null ? collect() : FeeSchedule::query()
            ->where('season_id', $season->getKey())
            ->orderBy('position')
            ->get()
            ->map(fn (FeeSchedule $fee): array => [
                'name' => $fee->name,
                'amount' => $fee->amount === null ? null : (float) $fee->amount,
                'unit' => $fee->unit,
                'notes' => $fee->notes,
            ]);

        /*
         * Only galas that still charge something. A meet with no fee on it
         * has not been priced yet, and a row of blanks reads as free.
         */
        $galas = $season === null ? collect() : Gala::query()
            ->published()
            ->where('season_id', $season->getKey())
            ->where(fn ($query) => $query
                ->where('entry_fee', '>', 0)
                ->orWhere('late_entry_fee', '>', 0))
            ->orderBy('starts_on')
            ->get()
            ->map(fn (Gala $gala): array => [
                'name' => $gala->name,
                'slug' => $gala->slug,
                'starts_on' => $gala->starts_on->toDateString(),
                'entries_close_at' => $gala->entries_close_at?->toDateString(),
                'entry_fee' => $this->charged($gala->entry_fee),
                'late_entry_fee' => $this->charged($gala->late_entry_fee),
                'late_entry_event_fee' => $this->charged($gala->late_entry_event_fee),
                'no_show_fine' => $this->charged($gala->no_show_fine),
                'appeal_deposit' => $this->charged($gala->appeal_deposit),
            ]);

        return Inertia::render('public/fees', [
            'season' => $season?->name,
            'fees' => $fees,
            'galas' => $galas,
        ]);
    }

    /**
     * A fee only counts as set when something is actually charged.
     *
     * The columns are not null and default to zero, so an unpriced gala
     * carries 0.00 rather than nothing. Printed as $0.00 that reads as free;
     * the page needs to be able to say the figure has not been set.
     */
    private function charged(int|float|string|null $amount): ?float
    {
        return $amount === null || (float) $amount <= 0.0 ? null : (float) $amount;
    }
}

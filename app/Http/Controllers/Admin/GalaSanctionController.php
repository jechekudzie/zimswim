<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GalaStatus;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Notifications\GalaSanctioned;
use App\Support\NoticeRecipients;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The board's decision on a gala request. Sanctioning is the gate that puts a
 * gala on the public calendar and lets entries open.
 */
class GalaSanctionController extends Controller
{
    public function store(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('sanction', $gala);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['sanction', 'reject'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['decision'] === 'reject') {
            $gala->update([
                'status' => GalaStatus::Rejected,
                'sanction_notes' => $validated['notes'] ?? null,
                'is_published' => false,
            ]);

            return back()->with('success', $gala->name.' has been returned to the host with changes requested.');
        }

        $this->guardVenue($gala);

        $gala->update([
            'status' => GalaStatus::Sanctioned,
            'sanctioned_by' => $request->user()->getKey(),
            'sanctioned_at' => now(),
            'sanction_notes' => $validated['notes'] ?? null,
            'is_published' => true,
        ]);

        NoticeRecipients::atOrAbove($gala->host, Permissions::MANAGE_GALAS)
            ->each(fn ($user) => $user->notify(new GalaSanctioned($gala->fresh(['venue', 'sanctioningBody']) ?? $gala)));

        return back()->with('success', $gala->name.' has been sanctioned and is now on the public calendar.');
    }

    /**
     * A gala may not be sanctioned into a pool that is not graded for it, or
     * one already claimed by another sanctioned gala.
     */
    private function guardVenue(Gala $gala): void
    {
        if ($gala->venue === null) {
            throw ValidationException::withMessages([
                'decision' => 'Book a venue before sanctioning this gala.',
            ]);
        }

        if (! $gala->hasSuitableVenue()) {
            throw ValidationException::withMessages([
                'decision' => sprintf(
                    '%s is graded %s and cannot host a %s gala, which needs a %s venue.',
                    $gala->venue->name,
                    $gala->venue->grade->label(),
                    $gala->level->label(),
                    $gala->level->minimumVenueGrade()->label(),
                ),
            ]);
        }

        if (! $gala->venue->isAvailableBetween($gala->starts_on, $gala->ends_on, $gala->getKey())) {
            throw ValidationException::withMessages([
                'decision' => $gala->venue->name.' is not available on those dates.',
            ]);
        }
    }
}

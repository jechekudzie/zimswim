<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Services\EntryEligibility;
use App\Support\OrganizationContext;
use App\Support\SwimTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GalaEntryController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly EntryEligibility $eligibility,
    ) {}

    public function index(Request $request, Gala $gala): Response
    {
        $this->authorize('viewAny', GalaEntry::class);

        $scopeIds = $this->context->scopeIds();
        $gala->load(['venue:id,name,lanes', 'season']);

        $entries = $gala->entries()
            ->whereIn('organization_id', $scopeIds)
            ->with(['member:id,first_name,last_name,membership_number', 'event', 'club:id,name'])
            ->get()
            ->sortBy([
                fn (GalaEntry $a, GalaEntry $b): int => $a->event->event_number <=> $b->event->event_number,
                fn (GalaEntry $a, GalaEntry $b): int => ($a->entry_time_ms ?? PHP_INT_MAX) <=> ($b->entry_time_ms ?? PHP_INT_MAX),
            ])
            ->values()
            ->map(fn (GalaEntry $entry): array => [
                'id' => $entry->getKey(),
                'event_number' => $entry->event->event_number,
                'event' => $entry->event->title(),
                'age_group' => $entry->event->age_group,
                'swimmer' => $entry->member->fullName(),
                'membership_number' => $entry->member->membership_number,
                'club' => $entry->club->name,
                'entry_time' => $entry->entryTime(),
                'heat' => $entry->heat,
                'lane' => $entry->lane,
                'status' => $entry->status,
                'is_late_entry' => $entry->is_late_entry,
            ]);

        return Inertia::render('admin/galas/entries', [
            'gala' => [
                'id' => $gala->getKey(),
                'slug' => $gala->slug,
                'name' => $gala->name,
                'status_label' => $gala->status->label(),
                'accepting_entries' => $gala->isAcceptingEntries(),
                'entries_close_at' => $gala->entries_close_at?->toDateString(),
                'age_up_date' => $gala->effectiveAgeUpDate()->toDateString(),
                'qualifying_times_apply' => $gala->qualifying_times_apply,
                'lanes' => $gala->laneCount(),
            ],
            'entries' => $entries,
            'events' => $gala->events()->get()->map(fn (GalaEvent $event): array => [
                'value' => $event->getKey(),
                'label' => sprintf('%d · %s %s', $event->event_number, $event->age_group, $event->title()),
                'seeded' => $event->seeded_at !== null,
                'entry_count' => $event->entries()->count(),
            ]),
            'swimmers' => Member::query()
                ->forOrganizations($scopeIds)
                ->active()
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'membership_number'])
                ->map(fn (Member $member): array => [
                    'value' => $member->getKey(),
                    'label' => $member->fullName().' ('.$member->membership_number.')',
                ]),
            // Rows a club's import file could not place, flashed back by the
            // importer so they are shown next to the upload box.
            'importProblems' => $request->session()->get('importProblems', []),
            'can' => [
                'manage' => $request->user()->can('manage', GalaEntry::class),
                'seed' => $request->user()->can('sanction', $gala) || $request->user()->can('recordResults', $gala),
            ],
        ]);
    }

    public function store(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('create', GalaEntry::class);

        $validated = $request->validate([
            'gala_event_id' => [
                'required', 'integer',
                Rule::exists('gala_events', 'id')->where('gala_id', $gala->getKey()),
            ],
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'exists:members,id'],
            'entry_times' => ['array'],
        ]);

        $event = GalaEvent::query()->findOrFail((int) $validated['gala_event_id']);
        $members = Member::query()
            ->forOrganizations($this->context->scopeIds())
            ->whereKey($validated['member_ids'])
            ->get();

        $entered = 0;
        $rejected = [];

        DB::transaction(function () use ($members, $event, $gala, $request, $validated, &$entered, &$rejected): void {
            foreach ($members as $member) {
                $problems = $this->eligibility->problems($member, $event);

                if ($this->eligibility->alreadyEntered($member, $event)) {
                    $problems[] = $member->fullName().' is already entered in this event.';
                }

                if ($problems !== []) {
                    $rejected[] = $problems[0];

                    continue;
                }

                GalaEntry::create([
                    'gala_id' => $gala->getKey(),
                    'gala_event_id' => $event->getKey(),
                    'member_id' => $member->getKey(),
                    'organization_id' => $member->organization_id,
                    'entry_time_ms' => SwimTime::tryParse($validated['entry_times'][$member->getKey()] ?? null)
                        ?? $this->eligibility->bestTimeFor($member, $event),
                    'status' => 'entered',
                    'is_late_entry' => $this->eligibility->isLate($event),
                    'entered_by' => $request->user()->getKey(),
                ]);

                $entered++;
            }
        });

        if ($entered === 0) {
            return back()->withErrors(['member_ids' => $rejected[0] ?? 'No swimmers could be entered.']);
        }

        $message = $entered.' '.($entered === 1 ? 'entry' : 'entries').' added.';

        if ($rejected !== []) {
            $message .= ' '.count($rejected).' could not be entered: '.$rejected[0];
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Gala $gala, GalaEntry $entry): RedirectResponse
    {
        $this->authorize('update', $entry);

        $validated = $request->validate([
            'entry_time' => ['nullable', 'string', 'max:16'],
            'status' => ['required', Rule::in(['entered', 'confirmed', 'withdrawn', 'scratched', 'no_show'])],
        ]);

        $entry->update([
            'entry_time_ms' => SwimTime::tryParse($validated['entry_time'] ?? null),
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Entry updated.');
    }

    public function destroy(Gala $gala, GalaEntry $entry): RedirectResponse
    {
        $this->authorize('delete', $entry);

        $entry->delete();

        return back()->with('success', 'Entry withdrawn.');
    }
}

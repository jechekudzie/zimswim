<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Course;
use App\Enums\RecordStatus;
use App\Enums\Stroke;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SwimRecord;
use App\Notifications\RecordRatified;
use App\Support\Csv;
use App\Support\NoticeRecipients;
use App\Support\Permissions;
use App\Support\SwimTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SwimRecordController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SwimRecord::class);

        $records = $this->filtered($request)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (SwimRecord $record): array => [
                'id' => $record->getKey(),
                'scope' => $record->scope,
                'course' => $record->course->value,
                'gender' => $record->gender,
                'age_group' => $record->age_group,
                'event' => $record->eventTitle(),
                'time' => $record->formattedTime(),
                'holder' => $record->holder_name,
                'club' => $record->club?->name,
                'venue' => $record->venue_name,
                'set_on' => $record->set_on?->toDateString(),
                'status' => $record->status->value,
                'status_label' => $record->status->label(),
            ]);

        return Inertia::render('admin/records/index', [
            'records' => $records,
            'filters' => [
                'status' => $request->string('status')->toString() ?: null,
                'course' => $request->string('course')->toString() ?: null,
            ],
            'statuses' => RecordStatus::options(),
            'courses' => Course::options(),
            'strokes' => Stroke::options(),
            'can' => ['ratify' => $request->user()->can('create', SwimRecord::class)],
        ]);
    }

    /**
     * The record book as a spreadsheet, narrowed exactly as the page is.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', SwimRecord::class);

        return Csv::download('records', [
            'Scope',
            'Course',
            'Gender',
            'Age group',
            'Event',
            'Time',
            'Holder',
            'Club',
            'Venue',
            'Set on',
            'Status',
        ], $this->filtered($request)->lazy()->map(fn (SwimRecord $record): array => [
            $record->scope,
            $record->course->value,
            $record->gender,
            $record->age_group,
            $record->eventTitle(),
            $record->formattedTime(),
            $record->holder_name,
            $record->club?->name,
            $record->venue_name,
            $record->set_on?->toDateString(),
            $record->status->label(),
        ]));
    }

    /**
     * @return Builder<SwimRecord>
     */
    private function filtered(Request $request): Builder
    {
        return SwimRecord::query()
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('course')->toString(), fn ($query, $course) => $query->where('course', $course))
            ->with(['province:id,name', 'club:id,name'])
            ->orderByDesc('status')
            ->orderBy('age_group')
            ->orderBy('distance');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SwimRecord::class);

        $validated = $request->validate([
            'scope' => ['required', Rule::in(['national', 'provincial'])],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'course' => ['required', Rule::enum(Course::class)],
            'gender' => ['required', Rule::in(['male', 'female', 'mixed'])],
            'age_group' => ['required', 'string', 'max:40'],
            'stroke' => ['required', Rule::enum(Stroke::class)],
            'distance' => ['required', 'integer', 'min:25', 'max:1500'],
            'time' => ['required', 'string', 'max:16'],
            'holder_name' => ['required', 'string', 'max:255'],
            'club_organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'set_on' => ['nullable', 'date'],
        ]);

        $timeMs = SwimTime::tryParse($validated['time']);

        if ($timeMs === null) {
            return back()->withErrors(['time' => 'Enter the time as 1:02.34 or 58.07.']);
        }

        $stroke = Stroke::from($validated['stroke']);
        unset($validated['time']);

        SwimRecord::create([
            ...$validated,
            'time_ms' => $timeMs,
            'is_relay' => $stroke->isRelay(),
            'status' => RecordStatus::Pending,
        ]);

        return back()->with('success', 'Record submitted for ratification.');
    }

    /**
     * A record only changes once the authorised body approves it, and the
     * record it beats is marked superseded in the same step.
     */
    public function ratify(Request $request, SwimRecord $record): RedirectResponse
    {
        $this->authorize('ratify', $record);

        $validated = $request->validate([
            'ratifying_body' => ['nullable', 'string', 'max:255'],
        ]);

        SwimRecord::query()
            ->ratified()
            ->where('scope', $record->scope)
            ->where('organization_id', $record->organization_id)
            ->where('course', $record->course)
            ->where('gender', $record->gender)
            ->where('age_group', $record->age_group)
            ->where('stroke', $record->stroke)
            ->where('distance', $record->distance)
            ->whereKeyNot($record->getKey())
            ->update(['status' => RecordStatus::Superseded]);

        $record->update([
            'status' => RecordStatus::Ratified,
            'ratified_by' => $request->user()->getKey(),
            'ratified_at' => now(),
            'ratifying_body' => $validated['ratifying_body'] ?? 'Zimbabwe Swimming',
        ]);

        $this->announce($record);

        return back()->with('success', 'Record ratified and the previous record superseded.');
    }

    public function reject(SwimRecord $record): RedirectResponse
    {
        $this->authorize('ratify', $record);

        $record->update(['status' => RecordStatus::Rejected]);

        return back()->with('success', 'Record rejected.');
    }

    public function destroy(SwimRecord $record): RedirectResponse
    {
        $this->authorize('delete', $record);

        $record->delete();

        return back()->with('success', 'Record removed.');
    }

    /**
     * Tell the swimmer's club, and the body that ratified it, that the record
     * now stands. Clubs hear about their own swimmers; nobody is copied on
     * every record in the country.
     */
    private function announce(SwimRecord $record): void
    {
        $club = $record->club_organization_id === null
            ? null
            : Organization::query()->find($record->club_organization_id);

        if ($club === null) {
            return;
        }

        NoticeRecipients::atOrAbove($club, Permissions::VIEW_RESULTS)
            ->each(fn ($user) => $user->notify(new RecordRatified($record)));
    }
}

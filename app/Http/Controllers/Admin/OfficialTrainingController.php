<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfficialCategory;
use App\Enums\TrainingKind;
use App\Http\Controllers\Controller;
use App\Models\OfficialTraining;
use App\Models\OfficialTrainingRegistration;
use App\Models\Venue;
use App\Support\Csv;
use App\Support\OrganizationContext;
use App\Support\OrganizationOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Scheduling clinics, exam sittings and refreshers.
 */
class OfficialTrainingController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly OrganizationOptions $options,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', OfficialTraining::class);

        $sessions = OfficialTraining::query()
            ->whereIn('organization_id', $this->context->scopeIds())
            ->with(['host:id,name', 'venue:id,name'])
            ->withCount('registrations')
            ->orderByDesc('held_on')
            ->paginate(20)
            ->through(fn (OfficialTraining $training): array => [
                'id' => $training->getKey(),
                'slug' => $training->slug,
                'title' => $training->title,
                'kind' => $training->kind->label(),
                'category' => $training->category?->label(),
                'host' => $training->host->name,
                'held_on' => $training->held_on->toDateString(),
                'venue' => $training->venue->name ?? $training->location,
                'registrations' => $training->registrations_count,
                'capacity' => $training->capacity,
                'is_published' => $training->is_published,
                'has_finished' => $training->hasFinished(),
            ]);

        return Inertia::render('admin/training/index', [
            'sessions' => $sessions,
        ]);
    }

    public function show(OfficialTraining $training): Response
    {
        $this->authorize('view', $training);

        $training->load(['host:id,name', 'venue:id,name,city']);

        return Inertia::render('admin/training/show', [
            'training' => [
                'id' => $training->getKey(),
                'slug' => $training->slug,
                'title' => $training->title,
                'kind' => $training->kind->label(),
                'category' => $training->category?->label(),
                'host' => $training->host->name,
                'held_on' => $training->held_on->toDateString(),
                'venue' => $training->venue->name ?? $training->location,
                'capacity' => $training->capacity,
                'is_published' => $training->is_published,
            ],
            'registrations' => $training->registrations()
                ->with('official:id,registration_number')
                ->orderBy('registered_at')
                ->get()
                ->map(fn (OfficialTrainingRegistration $registration): array => [
                    'name' => $registration->name,
                    'email' => $registration->email,
                    'phone' => $registration->phone,
                    'club_name' => $registration->club_name,
                    'notes' => $registration->notes,
                    // Tells the host at a glance who is renewing and who is
                    // coming to this for the first time.
                    'registration_number' => $registration->official?->registration_number,
                    'registered_at' => $registration->registered_at->toDateString(),
                ]),
            'can' => ['manage' => request()->user()->can('update', $training)],
        ]);
    }

    /**
     * The attendance list, for a host who needs it on paper at the door.
     */
    public function export(OfficialTraining $training): StreamedResponse
    {
        $this->authorize('view', $training);

        return Csv::download(
            'attendance-'.$training->slug,
            ['Name', 'Email', 'Phone', 'Club', 'Registration number', 'Registered on', 'Notes'],
            $training->registrations()->with('official:id,registration_number')->orderBy('name')->lazy()
                ->map(fn (OfficialTrainingRegistration $registration): array => [
                    $registration->name,
                    $registration->email,
                    $registration->phone,
                    $registration->club_name,
                    $registration->official?->registration_number,
                    $registration->registered_at->toDateString(),
                    $registration->notes,
                ]),
        );
    }

    public function create(): Response
    {
        $this->authorize('create', OfficialTraining::class);

        return Inertia::render('admin/training/form', [
            'training' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', OfficialTraining::class);

        $training = OfficialTraining::create($this->validated($request));

        return to_route('admin.training.show', $training)
            ->with('success', $training->title.' is on the calendar.');
    }

    public function edit(OfficialTraining $training): Response
    {
        $this->authorize('update', $training);

        return Inertia::render('admin/training/form', [
            'training' => [
                'id' => $training->getKey(),
                'slug' => $training->slug,
                'organization_id' => $training->organization_id,
                'venue_id' => $training->venue_id,
                'title' => $training->title,
                'kind' => $training->kind->value,
                'category' => $training->category?->value,
                'description' => $training->description,
                'held_on' => $training->held_on->toDateString(),
                'ends_on' => $training->ends_on?->toDateString(),
                'starts_at' => $training->starts_at,
                'ends_at' => $training->ends_at,
                'location' => $training->location,
                'is_online' => $training->is_online,
                'capacity' => $training->capacity,
                'registration_closes_at' => $training->registration_closes_at?->toDateString(),
                'contact_name' => $training->contact_name,
                'contact_email' => $training->contact_email,
                'contact_phone' => $training->contact_phone,
                'is_published' => $training->is_published,
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, OfficialTraining $training): RedirectResponse
    {
        $this->authorize('update', $training);

        $training->update($this->validated($request));

        return back()->with('success', $training->title.' has been updated.');
    }

    public function destroy(OfficialTraining $training): RedirectResponse
    {
        $this->authorize('delete', $training);

        $training->delete();

        return to_route('admin.training.index')
            ->with('success', $training->title.' has been taken off the calendar.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'organization_id' => ['required', 'integer', Rule::in($this->context->scopeIds())],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(TrainingKind::class)],
            'category' => ['nullable', Rule::enum(OfficialCategory::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'held_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:held_on'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_online' => ['boolean'],
            // Left empty on purpose means no cap, which is the default the
            // federation asked for: a clinic exists to be attended.
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'registration_closes_at' => ['nullable', 'date'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'is_published' => ['boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            ...$this->options->all(),
            'kinds' => TrainingKind::options(),
            'categories' => OfficialCategory::options(),
            'venues' => Venue::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'city'])
                ->map(fn (Venue $venue): array => [
                    'value' => $venue->getKey(),
                    'label' => $venue->city === null ? $venue->name : $venue->name.' — '.$venue->city,
                ]),
        ];
    }
}

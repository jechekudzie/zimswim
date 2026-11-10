<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Course;
use App\Enums\GalaLevel;
use App\Enums\GalaStatus;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\Organization;
use App\Models\Season;
use App\Models\Venue;
use App\Support\Csv;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GalaController extends Controller
{
    public function __construct(private readonly OrganizationContext $context) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Gala::class);

        $galas = $this->filtered($request)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Gala $gala): array => [
                'id' => $gala->getKey(),
                'slug' => $gala->slug,
                'name' => $gala->name,
                'level' => $gala->level->value,
                'level_label' => $gala->level->label(),
                'status' => $gala->status->value,
                'status_label' => $gala->status->label(),
                'is_sanctioned' => $gala->status->isSanctioned(),
                'starts_on' => $gala->starts_on->toDateString(),
                'ends_on' => $gala->ends_on->toDateString(),
                'venue' => $gala->venue?->name,
                'host' => $gala->host->name,
                'entries_count' => $gala->entries_count,
                'is_published' => $gala->is_published,
            ]);

        return Inertia::render('admin/galas/index', [
            'galas' => $galas,
            'filters' => [
                'status' => $request->string('status')->toString() ?: null,
                'level' => $request->string('level')->toString() ?: null,
            ],
            'statuses' => GalaStatus::options(),
            'levels' => GalaLevel::options(),
        ]);
    }

    public function show(Gala $gala): Response
    {
        $this->authorize('view', $gala);

        $gala->load(['venue', 'host', 'season', 'sessions.events', 'sanctioner']);

        return Inertia::render('admin/galas/show', [
            'gala' => [
                'id' => $gala->getKey(),
                'slug' => $gala->slug,
                'name' => $gala->name,
                'level_label' => $gala->level->label(),
                'course_label' => $gala->course->label(),
                'status' => $gala->status->value,
                'status_label' => $gala->status->label(),
                'is_sanctioned' => $gala->status->isSanctioned(),
                'starts_on' => $gala->starts_on->toDateString(),
                'ends_on' => $gala->ends_on->toDateString(),
                'age_up_date' => $gala->effectiveAgeUpDate()->toDateString(),
                'entries_close_at' => $gala->entries_close_at?->toDateString(),
                'entry_fee' => $gala->entry_fee,
                'eligible_ages' => $gala->eligible_ages,
                'description' => $gala->description,
                'host' => $gala->host->name,
                'venue' => $gala->venue?->name,
                'venue_grade' => $gala->venue?->grade->label(),
                'has_suitable_venue' => $gala->hasSuitableVenue(),
                'required_venue_grade' => $gala->level->minimumVenueGrade()->label(),
                'sanctioned_by' => $gala->sanctioner?->displayName(),
                'sanctioned_at' => $gala->sanctioned_at?->toDateString(),
                'is_published' => $gala->is_published,
            ],
            'clashes' => $gala->clashes()->map(fn (Gala $clash): array => [
                'name' => $clash->name,
                'slug' => $clash->slug,
                'starts_on' => $clash->starts_on->toDateString(),
                'ends_on' => $clash->ends_on->toDateString(),
            ]),
            'sessions' => $gala->sessions->map(fn ($session): array => [
                'name' => $session->name,
                'held_on' => $session->held_on->toDateString(),
                'warmup_at' => $session->warmup_at,
                'starts_at' => $session->starts_at,
                'events' => $session->events->map(fn ($event): array => [
                    'number' => $event->event_number,
                    'title' => $event->title(),
                    'age_group' => $event->age_group,
                    'round' => str_replace('_', ' ', $event->round),
                ]),
            ]),
            'can' => [
                'update' => request()->user()->can('update', $gala),
                'sanction' => request()->user()->can('sanction', $gala),
            ],
        ]);
    }

    /**
     * The calendar as a spreadsheet, narrowed exactly as the page is.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Gala::class);

        return Csv::download('galas', [
            'Name',
            'Season',
            'Level',
            'Status',
            'Starts on',
            'Ends on',
            'Venue',
            'Host',
            'Entries',
            'Published',
        ], $this->filtered($request)->lazy()->map(fn (Gala $gala): array => [
            $gala->name,
            $gala->season?->name,
            $gala->level->label(),
            $gala->status->label(),
            $gala->starts_on->toDateString(),
            $gala->ends_on->toDateString(),
            $gala->venue?->name,
            $gala->host->name,
            (int) $gala->getAttribute('entries_count'),
            $gala->is_published,
        ]));
    }

    /**
     * @return Builder<Gala>
     */
    private function filtered(Request $request): Builder
    {
        return Gala::query()
            ->forOrganizations($this->context->scopeIds())
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('level')->toString(), fn ($query, $level) => $query->where('level', $level))
            ->with(['venue:id,name,city', 'host:id,name', 'season:id,name'])
            ->withCount('entries')
            ->orderByDesc('starts_on');
    }

    public function create(): Response
    {
        $this->authorize('create', Gala::class);

        return Inertia::render('admin/galas/form', [
            'gala' => null,
            'hosts' => $this->hosts(),
            'venues' => $this->venues(),
            'seasons' => Season::query()->orderByDesc('starts_on')->get(['id', 'name'])
                ->map(fn (Season $season): array => ['value' => $season->getKey(), 'label' => $season->name])->all(),
            'levels' => GalaLevel::options(),
            'courses' => Course::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Gala::class);

        $gala = Gala::create([
            ...$this->validated($request),
            // A new gala always starts as a request for the board to consider.
            'status' => GalaStatus::Submitted,
            'requested_by' => $request->user()->getKey(),
        ]);

        return to_route('admin.galas.show', $gala)
            ->with('success', $gala->name.' has been submitted for sanction.');
    }

    public function edit(Gala $gala): Response
    {
        $this->authorize('update', $gala);

        return Inertia::render('admin/galas/form', [
            'gala' => [
                'id' => $gala->getKey(),
                'slug' => $gala->slug,
                'name' => $gala->name,
                'season_id' => $gala->season_id,
                'host_organization_id' => $gala->host_organization_id,
                'venue_id' => $gala->venue_id,
                'level' => $gala->level->value,
                'course' => $gala->course->value,
                'starts_on' => $gala->starts_on->toDateString(),
                'ends_on' => $gala->ends_on->toDateString(),
                'age_up_date' => $gala->age_up_date?->toDateString(),
                'entries_close_at' => $gala->entries_close_at?->toDateString(),
                'entry_fee' => $gala->entry_fee,
                'late_entry_fee' => $gala->late_entry_fee,
                'eligible_ages' => $gala->eligible_ages,
                'description' => $gala->description,
                'entries_email' => $gala->entries_email,
                'qualifying_times_apply' => $gala->qualifying_times_apply,
            ],
            'hosts' => $this->hosts(),
            'venues' => $this->venues(),
            'seasons' => Season::query()->orderByDesc('starts_on')->get(['id', 'name'])
                ->map(fn (Season $season): array => ['value' => $season->getKey(), 'label' => $season->name])->all(),
            'levels' => GalaLevel::options(),
            'courses' => Course::options(),
        ]);
    }

    public function update(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('update', $gala);

        $gala->update($this->validated($request));

        return back()->with('success', $gala->name.' has been updated.');
    }

    public function destroy(Gala $gala): RedirectResponse
    {
        $this->authorize('delete', $gala);

        $gala->delete();

        return to_route('admin.galas.index')->with('success', $gala->name.' has been removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'host_organization_id' => ['required', 'integer', Rule::in($this->context->scopeIds())],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'level' => ['required', Rule::enum(GalaLevel::class)],
            'course' => ['required', Rule::enum(Course::class)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'age_up_date' => ['nullable', 'date'],
            'entries_close_at' => ['nullable', 'date', 'before_or_equal:starts_on'],
            'entry_fee' => ['required', 'numeric', 'min:0'],
            'late_entry_fee' => ['nullable', 'numeric', 'min:0'],
            'eligible_ages' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'entries_email' => ['nullable', 'email', 'max:255'],
            'qualifying_times_apply' => ['boolean'],
        ]);
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function hosts(): array
    {
        return Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Organization $organization): array => ['value' => $organization->getKey(), 'label' => $organization->name])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string, grade: string}>
     */
    private function venues(): array
    {
        return Venue::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'grade'])
            ->map(fn (Venue $venue): array => [
                'value' => $venue->getKey(),
                'label' => $venue->name.($venue->city === null ? '' : ', '.$venue->city),
                'grade' => $venue->grade->label(),
            ])
            ->all();
    }
}

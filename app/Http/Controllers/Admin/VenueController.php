<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationType;
use App\Enums\VenueGrade;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Venue;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VenueController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Venue::class);

        $venues = $this->filtered($request)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Venue $venue): array => [
                'id' => $venue->getKey(),
                'slug' => $venue->slug,
                'name' => $venue->name,
                'city' => $venue->city,
                'province' => $venue->province?->name,
                'pool_length' => $venue->pool_length,
                'lanes' => $venue->lanes,
                'grade' => $venue->grade->value,
                'grade_label' => $venue->grade->label(),
                'electronic_timing' => $venue->electronic_timing,
                'starting_blocks' => $venue->starting_blocks,
                'is_active' => $venue->is_active,
                'condition_notes' => $venue->condition_notes,
            ]);

        return Inertia::render('admin/venues/index', [
            'venues' => $venues,
            'filters' => [
                'search' => $request->string('search')->toString() ?: null,
                'grade' => $request->string('grade')->toString() ?: null,
            ],
            'grades' => VenueGrade::options(),
        ]);
    }

    /**
     * The pool list as a spreadsheet, narrowed exactly as the page is.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Venue::class);

        return Csv::download('venues', [
            'Name',
            'City',
            'Province',
            'Pool length',
            'Lanes',
            'Grade',
            'Electronic timing',
            'Starting blocks',
            'Active',
            'Condition notes',
        ], $this->filtered($request)->lazy()->map(fn (Venue $venue): array => [
            $venue->name,
            $venue->city,
            $venue->province?->name,
            $venue->pool_length,
            $venue->lanes,
            $venue->grade->label(),
            $venue->electronic_timing,
            $venue->starting_blocks,
            $venue->is_active,
            $venue->condition_notes,
        ]));
    }

    /**
     * @return Builder<Venue>
     */
    private function filtered(Request $request): Builder
    {
        return Venue::query()
            ->when($request->string('grade')->toString(), fn ($query, $grade) => $query->where('grade', $grade))
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $query->where(fn ($inner) => $inner->where('name', 'ilike', $like)->orWhere('city', 'ilike', $like));
            })
            ->with('province:id,name')
            ->orderBy('name');
    }

    public function create(): Response
    {
        $this->authorize('create', Venue::class);

        return Inertia::render('admin/venues/form', [
            'venue' => null,
            'provinces' => $this->provinces(),
            'grades' => VenueGrade::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Venue::class);

        $venue = Venue::create($this->validated($request));

        return to_route('admin.venues.edit', $venue)
            ->with('success', $venue->name.' has been added to the venue register.');
    }

    public function edit(Venue $venue): Response
    {
        $this->authorize('update', $venue);

        return Inertia::render('admin/venues/form', [
            'venue' => [
                'id' => $venue->getKey(),
                'slug' => $venue->slug,
                'name' => $venue->name,
                'province_organization_id' => $venue->province_organization_id,
                'owner_type' => $venue->owner_type,
                'owner_name' => $venue->owner_name,
                'contact_name' => $venue->contact_name,
                'contact_phone' => $venue->contact_phone,
                'contact_email' => $venue->contact_email,
                'address' => $venue->address,
                'city' => $venue->city,
                'pool_length' => $venue->pool_length,
                'lanes' => $venue->lanes,
                'depth_shallow' => $venue->depth_shallow,
                'depth_deep' => $venue->depth_deep,
                'starting_blocks' => $venue->starting_blocks,
                'backstroke_ledges' => $venue->backstroke_ledges,
                'backstroke_flags' => $venue->backstroke_flags,
                'electronic_timing' => $venue->electronic_timing,
                'scoreboard' => $venue->scoreboard,
                'warmup_pool' => $venue->warmup_pool,
                'seating_capacity' => $venue->seating_capacity,
                'changing_rooms' => $venue->changing_rooms,
                'parking' => $venue->parking,
                'power_backup' => $venue->power_backup,
                'grade' => $venue->grade->value,
                'last_inspected_on' => $venue->last_inspected_on?->toDateString(),
                'condition_notes' => $venue->condition_notes,
                'notes' => $venue->notes,
                'is_active' => $venue->is_active,
            ],
            'provinces' => $this->provinces(),
            'grades' => VenueGrade::options(),
        ]);
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        $venue->update($this->validated($request));

        return back()->with('success', $venue->name.' has been updated.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $this->authorize('delete', $venue);

        $venue->delete();

        return to_route('admin.venues.index')->with('success', $venue->name.' has been removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'province_organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'owner_type' => ['required', Rule::in(['council', 'school', 'club', 'private'])],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'pool_length' => ['nullable', 'integer', Rule::in([25, 50])],
            'lanes' => ['nullable', 'integer', 'min:1', 'max:12'],
            'depth_shallow' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'depth_deep' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'starting_blocks' => ['boolean'],
            'backstroke_ledges' => ['boolean'],
            'backstroke_flags' => ['boolean'],
            'electronic_timing' => ['boolean'],
            'scoreboard' => ['boolean'],
            'warmup_pool' => ['boolean'],
            'seating_capacity' => ['nullable', 'integer', 'min:0'],
            'changing_rooms' => ['boolean'],
            'parking' => ['boolean'],
            'power_backup' => ['boolean'],
            'grade' => ['required', Rule::enum(VenueGrade::class)],
            'last_inspected_on' => ['nullable', 'date'],
            'condition_notes' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function provinces(): array
    {
        return Organization::query()
            ->ofType(OrganizationType::Province)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Organization $province): array => ['value' => $province->getKey(), 'label' => $province->name])
            ->all();
    }
}

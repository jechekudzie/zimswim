<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AffiliationStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\Csv;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationContext $context) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Organization::class);

        // The tree is what the page is about, so it is the view a visit
        // lands on; the flat table stays for finding one body by name.
        $view = $request->string('view')->toString() === 'table' ? 'table' : 'hierarchy';

        $totals = $this->rolledUpSwimmers();

        $organizations = $view !== 'table' ? null : $this->filtered($request)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Organization $organization): array => [
                'id' => $organization->getKey(),
                'name' => $organization->name,
                'slug' => $organization->slug,
                'code' => $organization->code,
                'type' => $organization->type->value,
                'type_label' => $organization->type->label(),
                'parent' => $organization->parent?->name,
                'city' => $organization->city,
                'email' => $organization->email,
                'affiliation_status' => $organization->affiliation_status->value,
                'is_active' => $organization->is_active,
                'swimmers_count' => $totals[$organization->getKey()] ?? 0,
                'children_count' => $organization->children_count,
            ]);

        return Inertia::render('admin/organizations/index', [
            'view' => $view,
            'tiers' => $this->tiers(),
            'tree' => $view === 'hierarchy' ? $this->tree($totals) : [],
            'organizations' => $organizations,
            'filters' => [
                'search' => $request->string('search')->toString() ?: null,
                'type' => $request->string('type')->toString() ?: null,
            ],
            'types' => OrganizationType::options(),
        ]);
    }

    /**
     * How many bodies sit at each tier of the structure in view.
     *
     * @return array<int, array{value: string, label: string, count: int}>
     */
    private function tiers(): array
    {
        $counts = Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->get(['type'])
            ->countBy(fn (Organization $organization): string => $organization->type->value);

        return collect(OrganizationType::cases())
            ->map(fn (OrganizationType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'count' => (int) $counts->get($type->value, 0),
            ])
            ->all();
    }

    /**
     * The structure as it actually is: the national body, the provinces
     * affiliated to it, and the clubs affiliated to each province.
     *
     * Built in one query and assembled in memory rather than walked
     * recursively, so a club secretary and the national board both cost the
     * same. A body whose parent sits outside the user's scope becomes a root
     * of the tree they are shown, which is how a province administrator sees
     * their own province at the top rather than an orphaned branch.
     *
     * @param  array<int, int>  $totals
     * @return array<int, array<string, mixed>>
     */
    private function tree(array $totals): array
    {
        $scopeIds = $this->context->scopeIds();

        $organizations = Organization::query()
            ->whereIn('id', $scopeIds)
            ->withCount(['members as swimmers_count' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $byParent = $organizations->groupBy('parent_id');

        $build = function (Organization $organization) use (&$build, $byParent, $totals): array {
            /** @var array<int, array<string, mixed>> $children */
            $children = $byParent
                ->get($organization->getKey(), collect())
                ->map(fn (Organization $child): array => $build($child))
                ->all();

            return [
                'id' => $organization->getKey(),
                'name' => $organization->name,
                'slug' => $organization->slug,
                'code' => $organization->code,
                'type' => $organization->type->value,
                'type_label' => $organization->type->label(),
                'city' => $organization->city,
                'affiliation_status' => $organization->affiliation_status->value,
                'affiliation_label' => $organization->affiliation_status->label(),
                'is_active' => $organization->is_active,
                'swimmers' => (int) $organization->getAttribute('swimmers_count'),
                'total_swimmers' => $totals[$organization->getKey()] ?? 0,
                'children' => $children,
            ];
        };

        return $organizations
            ->filter(fn (Organization $organization): bool => $organization->parent_id === null
                || ! in_array($organization->parent_id, $scopeIds, true))
            ->map(fn (Organization $organization): array => $build($organization))
            ->values()
            ->all();
    }

    /**
     * The affiliation list as a spreadsheet, narrowed exactly as the page is.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Organization::class);

        // The same rolled-up figure the listing shows, so a province does
        // not read zero in the spreadsheet while reading twenty on screen.
        $totals = $this->rolledUpSwimmers();

        return Csv::download('organisations', [
            'Name',
            'Code',
            'Type',
            'Parent body',
            'City',
            'Email',
            'Affiliation status',
            'Active',
            'Swimmers',
            'Bodies beneath',
        ], $this->filtered($request)->lazy()->map(fn (Organization $organization): array => [
            $organization->name,
            $organization->code,
            $organization->type->label(),
            $organization->parent?->name,
            $organization->city,
            $organization->email,
            $organization->affiliation_status->label(),
            $organization->is_active,
            $totals[$organization->getKey()] ?? 0,
            (int) $organization->getAttribute('children_count'),
        ]));
    }

    /**
     * Active swimmers at each body in scope, counting the bodies beneath it.
     *
     * Swimmers register to a club, so counting only the members attached
     * directly to a body reports zero for every province and for the
     * national body itself - which is what the listing used to show. The
     * structure is three tiers deep at most, so each body's own count is
     * walked up to its roots in memory rather than with a recursive query.
     *
     * @return array<int, int>
     */
    private function rolledUpSwimmers(): array
    {
        $organizations = Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->withCount(['members as swimmers_count' => fn ($query) => $query->where('status', 'active')])
            ->get(['id', 'parent_id']);

        /** @var array<int, int> $totals */
        $totals = [];
        /** @var array<int, int|null> $parents */
        $parents = [];

        foreach ($organizations as $organization) {
            $totals[$organization->getKey()] = (int) $organization->getAttribute('swimmers_count');
            $parents[$organization->getKey()] = $organization->parent_id;
        }

        foreach ($organizations as $organization) {
            $own = (int) $organization->getAttribute('swimmers_count');
            $parent = $parents[$organization->getKey()] ?? null;
            // A malformed tree must not hang the page, so the walk is bounded
            // by the number of bodies it could possibly pass through.
            $steps = count($parents);

            while ($parent !== null && array_key_exists($parent, $totals) && $steps-- > 0) {
                $totals[$parent] += $own;
                $parent = $parents[$parent] ?? null;
            }
        }

        return $totals;
    }

    /**
     * @return Builder<Organization>
     */
    private function filtered(Request $request): Builder
    {
        return Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->when(
                $request->string('type')->toString(),
                fn ($query, $type) => $query->where('type', $type),
            )
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $query->where(fn ($inner) => $inner->where('name', 'ilike', $like)->orWhere('code', 'ilike', $like));
            })
            ->with('parent:id,name')
            ->withCount([
                'members as swimmers_count' => fn ($query) => $query->where('status', 'active'),
                'children as children_count',
            ])
            ->orderByRaw("case type when 'national' then 0 when 'province' then 1 else 2 end")
            ->orderBy('name');
    }

    public function create(): Response
    {
        $this->authorize('create', Organization::class);

        return Inertia::render('admin/organizations/form', [
            'organization' => null,
            'parents' => $this->parents(),
            'types' => OrganizationType::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Organization::class);

        $organization = Organization::create($this->validated($request));

        return to_route('admin.organizations.edit', $organization)
            ->with('success', $organization->name.' has been created.');
    }

    public function edit(Organization $organization): Response
    {
        $this->authorize('update', $organization);

        return Inertia::render('admin/organizations/form', [
            'organization' => [
                'id' => $organization->getKey(),
                'slug' => $organization->slug,
                'name' => $organization->name,
                'short_name' => $organization->short_name,
                'code' => $organization->code,
                'type' => $organization->type->value,
                'parent_id' => $organization->parent_id,
                'description' => $organization->description,
                'email' => $organization->email,
                'phone' => $organization->phone,
                'website' => $organization->website,
                'address' => $organization->address,
                'city' => $organization->city,
                'affiliation_status' => $organization->affiliation_status->value,
                'is_active' => $organization->is_active,
            ],
            'parents' => $this->parents($organization),
            'types' => OrganizationType::options(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $organization->update($this->validated($request, $organization));

        return back()->with('success', $organization->name.' has been updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        abort_if($organization->children()->exists(), 422, 'Remove the organisations beneath this one first.');

        $organization->delete();

        return to_route('admin.organizations.index')
            ->with('success', $organization->name.' has been removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Organization $organization = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:16', Rule::unique('organizations', 'code')->ignore($organization?->getKey())],
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'parent_id' => ['nullable', 'integer', 'exists:organizations,id', Rule::notIn([$organization?->getKey()])],
            'description' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'affiliation_status' => ['required', Rule::enum(AffiliationStatus::class)],
            'is_active' => ['boolean'],
        ]);
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function parents(?Organization $exclude = null): array
    {
        return Organization::query()
            ->whereIn('type', [OrganizationType::National, OrganizationType::Province])
            ->when($exclude, fn ($query) => $query->whereKeyNot($exclude->getKey()))
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (Organization $organization): array => [
                'value' => $organization->getKey(),
                'label' => $organization->name.' ('.$organization->type->label().')',
            ])
            ->all();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Committees and the people on them.
 *
 * Shown for the body being acted in rather than for the whole federation,
 * because a committee belongs to whoever elected it. A province administrator
 * opens this page and sees their own executive; the national office sees the
 * federation's.
 *
 * A member can be taken off the published list without being deleted, which
 * is what the federation needs between somebody standing down and their
 * successor being confirmed.
 */
class CommitteeController extends Controller
{
    public function __construct(private readonly OrganizationContext $context) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Committee::class);

        $organization = $this->context->current();

        return Inertia::render('admin/committees/index', [
            'organization' => $organization?->name,
            'committees' => Committee::query()
                ->where('organization_id', $organization?->getKey())
                ->with('members')
                ->orderBy('position')
                ->orderBy('name')
                ->get()
                ->map(fn (Committee $committee): array => [
                    'id' => $committee->getKey(),
                    'name' => $committee->name,
                    'kind' => $committee->kind,
                    'description' => $committee->description,
                    'position' => $committee->position,
                    'members' => $committee->members->map(fn (CommitteeMember $member): array => [
                        'id' => $member->getKey(),
                        'person_name' => $member->person_name,
                        'role' => $member->role,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'position' => $member->position,
                        'is_published' => $member->is_published,
                    ])->all(),
                ])
                ->all(),
            'kinds' => [
                ['value' => 'executive', 'label' => 'Executive'],
                ['value' => 'committee', 'label' => 'Standing committee'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Committee::class);

        $organization = $this->context->current();
        abort_if($organization === null, 403);

        $validated = $this->validated($request);

        Committee::create($validated + [
            'organization_id' => $organization->getKey(),
            'slug' => $this->slug($validated['name'], $organization->getKey()),
        ]);

        return back()->with('success', $validated['name'].' has been added.');
    }

    public function update(Request $request, Committee $committee): RedirectResponse
    {
        $this->authorize('update', $committee);

        $committee->update($this->validated($request));

        return back()->with('success', $committee->name.' has been updated.');
    }

    public function destroy(Committee $committee): RedirectResponse
    {
        $this->authorize('delete', $committee);

        $committee->delete();

        return back()->with('success', $committee->name.' and its members have been removed.');
    }

    public function storeMember(Request $request, Committee $committee): RedirectResponse
    {
        $this->authorize('update', $committee);

        $committee->members()->create($this->validatedMember($request));

        return back()->with('success', 'Added to '.$committee->name.'.');
    }

    public function updateMember(Request $request, Committee $committee, CommitteeMember $member): RedirectResponse
    {
        $this->authorize('update', $committee);
        abort_unless($member->committee_id === $committee->getKey(), 404);

        $member->update($this->validatedMember($request));

        return back()->with('success', $member->person_name.' has been updated.');
    }

    public function destroyMember(Committee $committee, CommitteeMember $member): RedirectResponse
    {
        $this->authorize('update', $committee);
        abort_unless($member->committee_id === $committee->getKey(), 404);

        $member->delete();

        return back()->with('success', $member->person_name.' has been removed from '.$committee->name.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(['executive', 'committee'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        // An empty box means the front of the list, not an empty column.
        $validated['position'] ??= 0;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedMember(Request $request): array
    {
        $validated = $request->validate([
            'person_name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_published' => ['boolean'],
        ]);

        $validated['position'] ??= 0;

        return $validated;
    }

    /**
     * A slug unique within the body, since two provinces may each have a
     * finance committee.
     */
    private function slug(string $name, int $organizationId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Committee::where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}

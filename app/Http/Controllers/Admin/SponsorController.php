<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use App\Support\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The sponsor board on the front page.
 *
 * Ordered by hand rather than by name or by when they were added, because the
 * order on that band is an agreement with the people in it. Tiers group them;
 * the position decides who stands first within a tier.
 */
class SponsorController extends Controller
{
    /** Tiers in the order they appear on the page. */
    private const TIERS = ['principal', 'partner', 'supporter'];

    public function index(): Response
    {
        $this->authorize('viewAny', Sponsor::class);

        return Inertia::render('admin/sponsors/index', [
            'sponsors' => Sponsor::query()
                /*
                 * Tiers sort by standing rather than alphabetically, and the
                 * database has no opinion about which outranks which.
                 */
                ->orderByRaw("case tier when 'principal' then 1 when 'partner' then 2 else 3 end")
                ->orderBy('position')
                ->orderBy('name')
                ->get()
                ->map(fn (Sponsor $sponsor): array => [
                    'id' => $sponsor->getKey(),
                    'slug' => $sponsor->slug,
                    'name' => $sponsor->name,
                    'tier' => $sponsor->tier,
                    'website' => $sponsor->website,
                    'position' => $sponsor->position,
                    'is_active' => $sponsor->is_active,
                    'logo_url' => Images::url($sponsor->logo_path),
                ])
                ->all(),
            'tiers' => $this->tiers(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Sponsor::class);

        return Inertia::render('admin/sponsors/form', [
            'sponsor' => null,
            'tiers' => $this->tiers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Sponsor::class);

        $sponsor = Sponsor::create($this->validated($request));

        Images::replace($request, $sponsor, 'logo', 'logo_path', 'sponsors');

        return to_route('admin.sponsors.edit', $sponsor)
            ->with('success', $sponsor->name.' has been added to the sponsor board.');
    }

    public function edit(Sponsor $sponsor): Response
    {
        $this->authorize('update', $sponsor);

        return Inertia::render('admin/sponsors/form', [
            'sponsor' => [
                'id' => $sponsor->getKey(),
                'slug' => $sponsor->slug,
                'name' => $sponsor->name,
                'tier' => $sponsor->tier,
                'website' => $sponsor->website,
                'description' => $sponsor->description,
                'position' => $sponsor->position,
                'is_active' => $sponsor->is_active,
                'logo_url' => Images::url($sponsor->logo_path),
            ],
            'tiers' => $this->tiers(),
        ]);
    }

    public function update(Request $request, Sponsor $sponsor): RedirectResponse
    {
        $this->authorize('update', $sponsor);

        $sponsor->update($this->validated($request));

        Images::replace($request, $sponsor, 'logo', 'logo_path', 'sponsors');

        return back()->with('success', $sponsor->name.' has been updated.');
    }

    public function destroy(Sponsor $sponsor): RedirectResponse
    {
        $this->authorize('delete', $sponsor);

        Images::forget($sponsor, 'logo_path');
        $sponsor->delete();

        return to_route('admin.sponsors.index')
            ->with('success', $sponsor->name.' has been taken off the sponsor board.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tier' => ['required', Rule::in(self::TIERS)],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ], [
            'logo.max' => 'That logo is over 2MB. A mark for the web rarely needs to be.',
        ]);

        unset($validated['logo']);
        $validated['position'] ??= 0;

        return $validated;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function tiers(): array
    {
        return [
            ['value' => 'principal', 'label' => 'Principal sponsor'],
            ['value' => 'partner', 'label' => 'Partner'],
            ['value' => 'supporter', 'label' => 'Supporter'],
        ];
    }
}

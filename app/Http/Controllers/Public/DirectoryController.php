<?php

namespace App\Http\Controllers\Public;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Committee;
use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DirectoryController extends Controller
{
    public function clubs(Request $request): Response
    {
        $provinces = Organization::query()
            ->ofType(OrganizationType::Province)
            ->active()
            ->with(['children' => fn ($query) => $query->where('type', OrganizationType::Club)->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (Organization $province): array => [
                'name' => $province->name,
                'slug' => $province->slug,
                'code' => $province->code,
                'city' => $province->city,
                'description' => $province->description,
                'clubs' => $province->children->map(fn (Organization $club): array => [
                    'name' => $club->name,
                    'slug' => $club->slug,
                    'code' => $club->code,
                    'city' => $club->city,
                ]),
            ]);

        return Inertia::render('public/clubs', [
            'provinces' => $provinces,
        ]);
    }

    public function venues(Request $request): Response
    {
        $venues = Venue::query()
            ->active()
            ->with('province:id,name,slug')
            ->when($request->string('grade')->toString(), fn ($query, $grade) => $query->where('grade', $grade))
            ->orderBy('name')
            ->get()
            ->map(fn (Venue $venue): array => [
                'name' => $venue->name,
                'slug' => $venue->slug,
                'city' => $venue->city,
                'province' => $venue->province?->name,
                'owner_type' => $venue->owner_type,
                'owner_name' => $venue->owner_name,
                'pool_length' => $venue->pool_length,
                'lanes' => $venue->lanes,
                'starting_blocks' => $venue->starting_blocks,
                'electronic_timing' => $venue->electronic_timing,
                'warmup_pool' => $venue->warmup_pool,
                'grade' => $venue->grade->value,
                'grade_label' => $venue->grade->label(),
                'can_host' => $venue->grade->canHost(),
                'condition_notes' => $venue->condition_notes,
                'notes' => $venue->notes,
            ]);

        return Inertia::render('public/venues', [
            'venues' => $venues,
            'filters' => ['grade' => $request->string('grade')->toString() ?: null],
        ]);
    }

    public function about(): Response
    {
        $national = Organization::where('type', OrganizationType::National)->firstOrFail();

        $committees = Committee::query()
            ->where('organization_id', $national->getKey())
            ->with(['members' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('position')
            ->get()
            ->map(fn (Committee $committee): array => [
                'name' => $committee->name,
                'kind' => $committee->kind,
                'members' => $committee->members->map(fn ($member): array => [
                    'name' => $member->person_name,
                    'role' => $member->role,
                    'email' => $member->email,
                ]),
            ]);

        $provinces = Organization::query()
            ->ofType(OrganizationType::Province)
            ->active()
            ->with(['committees' => fn ($query) => $query->where('kind', 'executive')->with(['members' => fn ($inner) => $inner->where('is_published', true)])])
            ->orderBy('name')
            ->get()
            ->map(function (Organization $province): array {
                $officers = [];

                foreach ($province->committees as $committee) {
                    foreach ($committee->members as $member) {
                        $officers[] = [
                            'name' => $member->person_name,
                            'role' => $member->role,
                        ];
                    }
                }

                return [
                    'name' => $province->name,
                    'slug' => $province->slug,
                    'city' => $province->city,
                    'officers' => $officers,
                ];
            });

        return Inertia::render('public/about', [
            'national' => [
                'name' => $national->name,
                'description' => $national->description,
                'email' => $national->email,
            ],
            'committees' => $committees,
            'provinces' => $provinces,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\Committee;
use App\Models\Organization;
use App\Models\Venue;
use App\Support\Content\RichText;
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

    /**
     * One province and the clubs affiliated to it.
     *
     * The executive board is here too, because a club secretary looking up
     * their province is usually looking for the person to write to.
     */
    public function province(Organization $province): Response
    {
        abort_unless($province->type === OrganizationType::Province && $province->is_active, 404);

        $province->load([
            'children' => fn ($query) => $query
                ->where('type', OrganizationType::Club)
                ->where('is_active', true)
                ->withCount(['members' => fn ($inner) => $inner->active()])
                ->with('homeVenue:id,name,city')
                ->orderBy('name'),
            'committees' => fn ($query) => $query
                ->where('kind', 'executive')
                ->with(['members' => fn ($inner) => $inner->where('is_published', true)->orderBy('position')]),
        ]);

        $officers = [];

        foreach ($province->committees as $committee) {
            foreach ($committee->members as $member) {
                $officers[] = [
                    'name' => $member->person_name,
                    'role' => $member->role,
                    'email' => $member->email,
                ];
            }
        }

        return Inertia::render('public/province', [
            'province' => [
                'name' => $province->name,
                'slug' => $province->slug,
                'code' => $province->code,
                'city' => $province->city,
                'description' => $province->description,
                'email' => $province->email,
                'phone' => $province->phone,
            ],
            'officers' => $officers,
            'clubs' => $province->children->map(fn (Organization $club): array => [
                'name' => $club->name,
                'slug' => $club->slug,
                'code' => $club->code,
                'city' => $club->city,
                'swimmers' => $club->members_count,
                'trains_at' => $club->homeVenue?->name,
            ]),
        ]);
    }

    /**
     * A single club: who coaches it, where it swims, and how to reach it.
     *
     * The swimmer count is the only figure published. Names, ages and times
     * belong to the swimmers and their families, not to a public directory.
     */
    public function club(Organization $province, Organization $club): Response
    {
        abort_unless(
            $club->type === OrganizationType::Club
                && $club->is_active
                && $club->parent_id === $province->getKey(),
            404,
        );

        $club->loadCount(['members' => fn ($query) => $query->active()]);
        $club->load('homeVenue');

        $coaches = Coach::query()
            ->where('organization_id', $club->getKey())
            ->where('status', 'active')
            ->orderByDesc('is_head_coach')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Coach $coach): array => [
                'name' => $coach->fullName(),
                'role' => $coach->is_head_coach ? 'Head coach' : 'Coach',
                'qualification' => $coach->qualification,
                // Only the head coach's line is published. The federation
                // asked for the person a parent should call, and printing
                // every coach's mobile is not that.
                'email' => $coach->is_head_coach ? $coach->email : null,
                'phone' => $coach->is_head_coach ? $coach->phone : null,
            ]);

        return Inertia::render('public/club', [
            'club' => [
                'name' => $club->name,
                'slug' => $club->slug,
                'code' => $club->code,
                'city' => $club->city,
                'description' => $club->description,
                'email' => $club->email,
                'phone' => $club->phone,
                'website' => $club->website,
                'address' => $club->address,
                'swimmers' => $club->members_count,
                'training_times' => $club->training_times,
            ],
            'province' => [
                'name' => $province->name,
                'slug' => $province->slug,
            ],
            'venue' => $club->homeVenue === null ? null : [
                'name' => $club->homeVenue->name,
                'city' => $club->homeVenue->city,
                'pool_length' => $club->homeVenue->pool_length,
                'lanes' => $club->homeVenue->lanes,
            ],
            'coaches' => $coaches,
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
                'description' => $committee->description,
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
                /*
                 * Just the words: this stands as the single line under the
                 * page's title, where a paragraph of markup has nowhere to go.
                 */
                'description' => RichText::toPlain((string) $national->description) ?: null,
                'email' => $national->email,
            ],
            'committees' => $committees,
            'provinces' => $provinces,
        ]);
    }
}

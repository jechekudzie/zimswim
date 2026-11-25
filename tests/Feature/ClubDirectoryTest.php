<?php

use App\Models\Coach;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Venue;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
});

describe('province page', function (): void {
    it('lists the clubs affiliated to the province', function (): void {
        $other = Organization::factory()->province($this->national)->create();
        Organization::factory()->club($other)->create(['name' => 'Somebody Else']);

        $this->get(route('provinces.show', $this->province))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/province')
                ->where('clubs', fn ($clubs): bool => collect($clubs)->pluck('name')->all() === [$this->club->name])
                ->etc());
    });

    it('counts only the swimmers a club has registered', function (): void {
        Member::factory()->count(3)->create(['organization_id' => $this->club->getKey()]);
        Member::factory()->create(['organization_id' => $this->club->getKey(), 'status' => 'lapsed']);

        $this->get(route('provinces.show', $this->province))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('clubs.0.swimmers', 3)
                ->etc());
    });

    it('will not open a club as though it were a province', function (): void {
        $this->get(route('provinces.show', $this->club))->assertNotFound();
    });

    it('keeps a province that has been stood down off the site', function (): void {
        $this->province->update(['is_active' => false]);

        $this->get(route('provinces.show', $this->province))->assertNotFound();
    });
});

describe('club page', function (): void {
    it('shows where the club trains and who coaches it', function (): void {
        $venue = Venue::factory()->create(['name' => 'Les Brown Pool']);
        $this->club->update(['home_venue_id' => $venue->getKey()]);

        Coach::factory()->head()->create([
            'organization_id' => $this->club->getKey(),
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'email' => 'head@example.org',
            'phone' => '0772000111',
        ]);

        $this->get(route('clubs.show', [$this->province, $this->club]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/club')
                ->where('venue.name', 'Les Brown Pool')
                ->where('coaches.0.name', 'Tendai Moyo')
                ->where('coaches.0.role', 'Head coach')
                ->where('coaches.0.phone', '0772000111')
                ->etc());
    });

    it('publishes a line for the head coach but not for the rest', function (): void {
        Coach::factory()->head()->create([
            'organization_id' => $this->club->getKey(),
            'last_name' => 'Aardvark',
            'phone' => '0772000111',
        ]);

        Coach::factory()->create([
            'organization_id' => $this->club->getKey(),
            'last_name' => 'Banda',
            'email' => 'assistant@example.org',
            'phone' => '0772000222',
        ]);

        /*
         * An assistant coach did not ask to have their mobile on a public
         * page. The head coach is the person the federation asked us to
         * publish, and the head coach sorts the rest.
         */
        $this->get(route('clubs.show', [$this->province, $this->club]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('coaches.0.role', 'Head coach')
                ->where('coaches.1.role', 'Coach')
                ->where('coaches.1.email', null)
                ->where('coaches.1.phone', null)
                ->etc());
    });

    it('refuses a club asked for under the wrong province', function (): void {
        $other = Organization::factory()->province($this->national)->create();

        $this->get(route('clubs.show', [$other, $this->club]))->assertNotFound();
    });

    it('names no swimmer', function (): void {
        $member = Member::factory()->create([
            'organization_id' => $this->club->getKey(),
            'first_name' => 'Rutendo',
            'last_name' => 'Chikwanha',
        ]);

        // The count is public. Who makes it up is not.
        $this->get(route('clubs.show', [$this->province, $this->club]))
            ->assertOk()
            ->assertDontSee($member->last_name)
            ->assertInertia(fn ($page) => $page->where('club.swimmers', 1)->etc());
    });
});

describe('admin', function (): void {
    it('records the pool a club trains at', function (): void {
        $editor = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
        $venue = Venue::factory()->create(['name' => 'Bulawayo City Pool']);

        $this->actingAs($editor)
            ->put(route('admin.organizations.update', $this->club), [
                'name' => $this->club->name,
                'type' => 'club',
                'parent_id' => $this->province->getKey(),
                'affiliation_status' => $this->club->affiliation_status->value,
                'home_venue_id' => $venue->getKey(),
                'training_times' => '<p>Monday to Thursday, 5pm.</p>',
                'is_active' => true,
            ])->assertRedirect();

        expect($this->club->fresh()->home_venue_id)->toBe($venue->getKey());

        $this->get(route('clubs.show', [$this->province, $this->club]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('venue.name', 'Bulawayo City Pool')
                ->where('club.training_times', '<p>Monday to Thursday, 5pm.</p>')
                ->etc());
    });
});

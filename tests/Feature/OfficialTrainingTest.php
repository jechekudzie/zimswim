<?php

use App\Enums\TrainingKind;
use App\Models\OfficialTraining;
use App\Models\OfficialTrainingRegistration;
use App\Models\TechnicalOfficial;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
});

describe('the public calendar', function (): void {
    it('shows what is coming up and holds back a draft', function (): void {
        OfficialTraining::factory()->create([
            'organization_id' => $this->province->getKey(),
            'title' => 'Timekeeper clinic',
        ]);

        OfficialTraining::factory()->draft()->create([
            'organization_id' => $this->province->getKey(),
            'title' => 'Not announced yet',
        ]);

        $this->get(route('officials.training'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/officials-training')
                ->where('upcoming', fn ($sessions): bool => collect($sessions)->pluck('title')->all() === ['Timekeeper clinic'])
                ->etc());
    });

    it('moves a session that has been held into the recent list', function (): void {
        OfficialTraining::factory()->past()->create([
            'organization_id' => $this->province->getKey(),
            'title' => 'Last term clinic',
        ]);

        // Somebody who missed it needs to see that it happened, rather than
        // conclude the federation has not run one.
        $this->get(route('officials.training'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('upcoming', [])
                ->where('recent.0.title', 'Last term clinic')
                ->etc());
    });

    it('will not open a draft session directly', function (): void {
        $draft = OfficialTraining::factory()->draft()->create([
            'organization_id' => $this->province->getKey(),
        ]);

        $this->get(route('officials.training.show', $draft))->assertNotFound();
    });
});

describe('putting a name down', function (): void {
    it('takes a registration from somebody with no account at all', function (): void {
        $clinic = OfficialTraining::factory()->create([
            'organization_id' => $this->province->getKey(),
        ]);

        /*
         * The whole point of the page: the people a clinic most needs to
         * reach are the ones who are not on the register yet.
         */
        $this->post(route('officials.training.register', $clinic), [
            'name' => 'Rudo Chikafu',
            'email' => 'rudo@example.org',
            'phone' => '0772000333',
            'club_name' => 'Spartans',
        ])->assertRedirect();

        expect($clinic->registrations()->count())->toBe(1);
        expect($clinic->registrations()->first()->name)->toBe('Rudo Chikafu');
    });

    it('does not take a second place when somebody registers twice', function (): void {
        $clinic = OfficialTraining::factory()->create([
            'organization_id' => $this->province->getKey(),
        ]);

        foreach (['0772000111', '0772000222'] as $phone) {
            $this->post(route('officials.training.register', $clinic), [
                'name' => 'Rudo Chikafu',
                'email' => 'rudo@example.org',
                'phone' => $phone,
            ])->assertRedirect();
        }

        expect($clinic->registrations()->count())->toBe(1);
        expect($clinic->registrations()->first()->phone)->toBe('0772000222');
    });

    it('links the registration to an official already on the register', function (): void {
        $clinic = OfficialTraining::factory()->create([
            'organization_id' => $this->province->getKey(),
            'kind' => TrainingKind::Refresher,
        ]);

        $official = TechnicalOfficial::factory()->create([
            'organization_id' => $this->club->getKey(),
            'email' => 'serving@example.org',
        ]);

        $this->post(route('officials.training.register', $clinic), [
            'name' => $official->fullName(),
            'email' => 'serving@example.org',
        ])->assertRedirect();

        expect($clinic->registrations()->first()->technical_official_id)
            ->toBe($official->getKey());
    });

    it('is open by default, with no cap', function (): void {
        $clinic = OfficialTraining::factory()->create([
            'organization_id' => $this->province->getKey(),
        ]);

        OfficialTrainingRegistration::factory()->count(30)->create([
            'official_training_id' => $clinic->getKey(),
        ]);

        expect($clinic->isOpenForRegistration())->toBeTrue();
        expect($clinic->placesLeft())->toBeNull();
    });

    it('turns somebody away once a capped session is full', function (): void {
        $clinic = OfficialTraining::factory()->create([
            'organization_id' => $this->province->getKey(),
            'capacity' => 2,
        ]);

        OfficialTrainingRegistration::factory()->count(2)->create([
            'official_training_id' => $clinic->getKey(),
        ]);

        $this->post(route('officials.training.register', $clinic), [
            'name' => 'Too Late',
            'email' => 'late@example.org',
        ])->assertSessionHasErrors('email');

        expect($clinic->registrations()->count())->toBe(2);
    });

    it('still lets somebody already registered correct their details when full', function (): void {
        $clinic = OfficialTraining::factory()->create([
            'organization_id' => $this->province->getKey(),
            'capacity' => 1,
        ]);

        OfficialTrainingRegistration::factory()->create([
            'official_training_id' => $clinic->getKey(),
            'email' => 'rudo@example.org',
            'phone' => null,
        ]);

        // They already hold the place; a correction must not cost it.
        $this->post(route('officials.training.register', $clinic), [
            'name' => 'Rudo Chikafu',
            'email' => 'rudo@example.org',
            'phone' => '0772000444',
        ])->assertSessionHasNoErrors();

        expect($clinic->registrations()->first()->phone)->toBe('0772000444');
    });

    it('refuses a registration once the session has been held', function (): void {
        $clinic = OfficialTraining::factory()->past()->create([
            'organization_id' => $this->province->getKey(),
        ]);

        $this->post(route('officials.training.register', $clinic), [
            'name' => 'Too Late',
            'email' => 'late@example.org',
        ])->assertSessionHasErrors('email');
    });
});

describe('the admin', function (): void {
    it('schedules a session and shows who has registered', function (): void {
        $editor = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);

        $this->actingAs($editor)
            ->post(route('admin.training.store'), [
                'organization_id' => $this->province->getKey(),
                'title' => 'Judge of Stroke clinic',
                'kind' => TrainingKind::Clinic->value,
                'held_on' => now()->addWeeks(3)->toDateString(),
                'location' => 'Les Brown Pool',
                'is_published' => true,
            ])->assertRedirect();

        $clinic = OfficialTraining::where('title', 'Judge of Stroke clinic')->firstOrFail();

        $this->post(route('officials.training.register', $clinic), [
            'name' => 'Rudo Chikafu',
            'email' => 'rudo@example.org',
        ])->assertRedirect();

        $this->actingAs($editor)
            ->get(route('admin.training.show', $clinic))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('registrations.0.name', 'Rudo Chikafu')
                ->where('registrations.0.registration_number', null)
                ->etc());
    });

    it('keeps one province out of another\'s clinic', function (): void {
        $other = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

        $theirs = OfficialTraining::factory()->create([
            'organization_id' => $this->national->getKey(),
        ]);

        $this->actingAs($other)
            ->get(route('admin.training.edit', $theirs))
            ->assertForbidden();
    });

    it('hands the host an attendance list', function (): void {
        $editor = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);

        $clinic = OfficialTraining::factory()->create([
            'organization_id' => $this->province->getKey(),
        ]);

        OfficialTrainingRegistration::factory()->create([
            'official_training_id' => $clinic->getKey(),
            'name' => 'Rudo Chikafu',
        ]);

        $response = $this->actingAs($editor)->get(route('admin.training.export', $clinic));

        $response->assertOk();
        expect($response->streamedContent())->toContain('Rudo Chikafu');
    });
});

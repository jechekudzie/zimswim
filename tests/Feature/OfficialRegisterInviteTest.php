<?php

use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Models\OfficialAccreditation;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Notifications\OfficialRegistered;
use App\Notifications\OfficialWelcome;
use App\Support\Roles;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->registrar = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
});

function officialPayload(array $overrides = []): array
{
    return [
        'registration_number' => 'TO-9001',
        'organization_id' => test()->club->getKey(),
        'province_organization_id' => test()->province->getKey(),
        'first_name' => 'Rudo',
        'last_name' => 'Chikwava',
        'email' => 'rudo@example.com',
        'phone' => '+263 77 222 3333',
        'date_of_birth' => '1979-06-14',
        'status' => 'active',
        ...$overrides,
    ];
}

it('opens a login and welcomes an official added by hand', function (): void {
    Notification::fake();

    $this->actingAs($this->registrar)
        ->post(route('admin.officials.store'), officialPayload(['invite' => '1']))
        ->assertRedirect();

    $official = TechnicalOfficial::where('registration_number', 'TO-9001')->sole();
    $user = User::where('email', 'rudo@example.com')->sole();

    expect($official->user_id)->toBe($user->getKey())
        ->and($official->club->getKey())->toBe($this->club->getKey());

    // The role is given at the club they are registered through, not globally.
    setPermissionsTeamId($this->club->getKey());
    expect($user->fresh()->hasRole(Roles::GALA_OFFICIAL))->toBeTrue();
    setPermissionsTeamId(null);

    // A registrar's official is not a learner, so they get the register
    // welcome rather than the pathway one.
    Notification::assertSentTo($user, OfficialRegistered::class);
    Notification::assertNotSentTo($user, OfficialWelcome::class);
});

it('leaves a transcribed official alone when the registrar does not ask', function (): void {
    Notification::fake();

    $this->actingAs($this->registrar)
        ->post(route('admin.officials.store'), officialPayload())
        ->assertRedirect();

    $official = TechnicalOfficial::where('registration_number', 'TO-9001')->sole();

    // Somebody typing in a paper register of fifty names should not set off
    // fifty emails by doing nothing.
    expect($official->user_id)->toBeNull();
    Notification::assertNothingSent();
});

it('invites an official who was on the register before anybody could be', function (): void {
    Notification::fake();

    $official = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'email' => 'legacy@example.com',
        'user_id' => null,
    ]);

    $this->actingAs($this->registrar)
        ->post(route('admin.officials.invite', $official))
        ->assertRedirect()
        ->assertSessionHas('success');

    $user = User::where('email', 'legacy@example.com')->sole();

    expect($official->fresh()->user_id)->toBe($user->getKey());
    Notification::assertSentTo($user, OfficialRegistered::class);
});

it('refuses to invite somebody with no address to send to', function (): void {
    Notification::fake();

    $official = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'email' => null,
        'user_id' => null,
    ]);

    $this->actingAs($this->registrar)
        ->post(route('admin.officials.invite', $official))
        ->assertSessionHasErrors('email');

    expect($official->fresh()->user_id)->toBeNull();
    Notification::assertNothingSent();
});

it('keeps one login for an official who is already a parent or a coach', function (): void {
    Notification::fake();

    $existing = User::factory()->create(['email' => 'coach@example.com']);

    $official = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'email' => 'coach@example.com',
        'user_id' => null,
    ]);

    $this->actingAs($this->registrar)
        ->post(route('admin.officials.invite', $official))
        ->assertRedirect();

    expect(User::where('email', 'coach@example.com')->count())->toBe(1)
        ->and($official->fresh()->user_id)->toBe($existing->getKey());

    Notification::assertSentTo(
        $existing,
        OfficialRegistered::class,
        fn (OfficialRegistered $notification): bool => $notification->accountIsNew === false,
    );
});

it('keeps the register out of the hands of somebody who cannot manage it', function (): void {
    $swimmer = userWithRole(Roles::SWIMMER, $this->club);
    $official = TechnicalOfficial::factory()->create(['organization_id' => $this->club->getKey()]);

    $this->actingAs($swimmer)
        ->post(route('admin.officials.invite', $official))
        ->assertForbidden();
});

describe('the register welcome', function (): void {
    it('shows what the register holds and asks them to check it', function (): void {
        $official = TechnicalOfficial::factory()->create([
            'organization_id' => $this->club->getKey(),
            'province_organization_id' => $this->province->getKey(),
            'registration_number' => 'TO-0051',
            'first_name' => 'Rudo',
            'email' => 'rudo@example.com',
            'status' => 'active',
        ]);

        OfficialAccreditation::factory()->create([
            'technical_official_id' => $official->getKey(),
            'category' => OfficialCategory::Referee,
            'grade' => OfficialGrade::National,
            'expires_on' => '2028-12-31',
        ]);

        $user = User::factory()->create(['email' => 'rudo@example.com']);

        $html = (string) (new OfficialRegistered($official->fresh(), accountIsNew: true))
            ->toMail($user)
            ->render();

        expect($html)
            ->toContain('TO-0051')
            ->toContain('Rudo')
            ->toContain($this->club->name)
            ->toContain('Active')
            ->toContain('Referee')
            ->toContain('National')
            ->toContain('31 December 2028')
            ->toContain('Please check this is right')
            ->toContain('reset-password/')
            // Nothing about lessons, exams or learner sessions: this person
            // may have been refereeing for fifteen years.
            ->not->toContain('Sit the exam')
            ->not->toContain('sessions as a learner');
    });

    it('says the record is bare rather than showing an empty table', function (): void {
        $official = TechnicalOfficial::factory()->create([
            'organization_id' => $this->club->getKey(),
            'email' => 'new@example.com',
        ]);
        $user = User::factory()->create(['email' => 'new@example.com']);

        $notification = new OfficialRegistered($official, accountIsNew: false);

        expect($notification->accreditations())->toBe([]);

        expect((string) $notification->toMail($user)->render())
            ->toContain('Please check your record')
            ->toContain('Open your portal')
            ->not->toContain('reset-password/');
    });
});

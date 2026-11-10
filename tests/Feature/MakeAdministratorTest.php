<?php

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    seedRoles();
    $this->national = Organization::factory()->national()->create();
});

it('opens an account that can actually reach the backend', function (): void {
    $this->artisan('zimswim:make-admin', [
        'name' => 'Nigel Chekudzie',
        'email' => 'nigel@example.test',
    ])->assertSuccessful();

    $user = User::where('email', 'nigel@example.test')->sole();

    // All three have to be true together, and each has silently bitten
    // before: verified, attached, and holding the role inside that body.
    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->is_active)->toBeTrue()
        ->and($user->current_organization_id)->toBe($this->national->getKey())
        ->and($user->organizations()->whereKey($this->national->getKey())->exists())->toBeTrue();

    setPermissionsTeamId($this->national->getKey());
    expect($user->fresh()->hasRole(Roles::SYSTEM_ADMINISTRATOR))->toBeTrue();
    setPermissionsTeamId(null);
});

it('lands that account on the dashboard rather than the verify-email wall', function (): void {
    $this->artisan('zimswim:make-admin', ['name' => 'Nigel', 'email' => 'nigel@example.test']);

    $this->actingAs(User::where('email', 'nigel@example.test')->sole())
        ->get(route('dashboard'))
        ->assertOk();
});

it('promotes somebody who already has an account instead of failing', function (): void {
    $existing = User::factory()->create(['email' => 'coach@example.test']);

    $this->artisan('zimswim:make-admin', ['name' => 'Coach', 'email' => 'coach@example.test'])
        ->assertSuccessful();

    expect(User::where('email', 'coach@example.test')->count())->toBe(1);

    setPermissionsTeamId($this->national->getKey());
    expect($existing->fresh()->hasRole(Roles::SYSTEM_ADMINISTRATOR))->toBeTrue();
    setPermissionsTeamId(null);
});

it('refuses when the roles have not been seeded', function (): void {
    Role::query()->delete();

    $this->artisan('zimswim:make-admin', ['name' => 'Nigel', 'email' => 'nigel@example.test'])
        ->assertFailed();

    expect(User::where('email', 'nigel@example.test')->exists())->toBeFalse();
});

it('attaches them to a named organisation when one is given', function (): void {
    $province = Organization::factory()->province($this->national)->create();

    $this->artisan('zimswim:make-admin', [
        'name' => 'Provincial Lead',
        'email' => 'province@example.test',
        '--organization' => $province->slug,
    ])->assertSuccessful();

    expect(User::where('email', 'province@example.test')->sole()->current_organization_id)
        ->toBe($province->getKey());
});

it('refuses when there is no organisation to attach them to', function (): void {
    Organization::query()->forceDelete();

    $this->artisan('zimswim:make-admin', ['name' => 'Nigel', 'email' => 'nigel@example.test'])
        ->assertFailed();
});

it('does not make an account for a malformed address', function (): void {
    $this->artisan('zimswim:make-admin', ['name' => 'Nigel', 'email' => 'not-an-address'])
        ->assertFailed();

    expect(User::where('name', 'Nigel')->exists())->toBeFalse();
});

it('only ever makes administrators in the national body by default', function (): void {
    Organization::factory()->province($this->national)->create();

    $this->artisan('zimswim:make-admin', ['name' => 'Nigel', 'email' => 'nigel@example.test']);

    expect(User::where('email', 'nigel@example.test')->sole()->current_organization_id)
        ->toBe($this->national->getKey())
        ->and($this->national->type)->toBe(OrganizationType::National);
});

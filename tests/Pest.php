<?php

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a user holding the given role inside one organisation, and make that
 * organisation the one they are acting in.
 */
function userWithRole(string $role, Organization $organization): User
{
    $user = User::factory()->create([
        'current_organization_id' => $organization->getKey(),
    ]);

    $user->organizations()->attach($organization->getKey(), ['is_default' => true]);

    setPermissionsTeamId($organization->getKey());
    $user->assignRole($role);
    setPermissionsTeamId(null);

    return $user->fresh();
}

/**
 * Seed the roles and permissions the platform ships with.
 */
function seedRoles(): void
{
    (new RolesAndPermissionsSeeder)->run();
}

/**
 * The national body with one province and one club beneath it.
 *
 * @return array{0: Organization, 1: Organization, 2: Organization}
 */
function organizationTree(): array
{
    $national = Organization::factory()->national()->create();
    $province = Organization::factory()->province($national)->create();
    $club = Organization::factory()->club($province)->create();

    return [$national, $province, $club];
}

<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One signed-in account per access level so every screen can be walked through
 * before real accounts are issued.
 *
 * There is deliberately more than one province and more than one club here.
 * A single provincial login shows the screens; a second one, in a different
 * province, is what shows that the scoping works — Bulawayo's administrator
 * opens the same member list and sees Bulawayo's swimmers only. The two Harare
 * clubs do the same thing one level down, within a single province.
 *
 * Every account shares the same development password, set below.
 */
class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $national = Organization::where('slug', 'zimbabwe-swimming')->firstOrFail();
        $harare = Organization::where('slug', 'harare')->firstOrFail();
        $bulawayo = Organization::where('slug', 'bulawayo')->firstOrFail();
        $lesBrown = Organization::where('slug', 'les-brown-swimming-club')->firstOrFail();
        $dolphins = Organization::where('slug', 'harare-dolphins')->firstOrFail();
        $barracudas = Organization::where('slug', 'bulawayo-barracudas')->firstOrFail();

        $accounts = [
            // Leading Digital support, attached to the national body so the role
            // reaches every province and club beneath it.
            ['nigel@leadingdigital.africa', 'Nigel', null, Roles::SYSTEM_ADMINISTRATOR, $national],
            ['admin@zimbabweswimming.co.zw', 'Valerie', 'Musere', Roles::SYSTEM_ADMINISTRATOR, $national],
            ['chair@zimbabweswimming.co.zw', 'Rumbidzai', 'Mahobele', Roles::BOARD_MEMBER, $national],
            ['technical@zimbabweswimming.co.zw', 'Angela', 'Mawere', Roles::TECHNICAL_COMMITTEE, $national],
            ['treasurer@zimbabweswimming.co.zw', 'Emmah', 'Mungoni', Roles::FINANCE, $national],
            ['harare@zimbabweswimming.co.zw', 'Amy', 'Wicks', Roles::PROVINCIAL_ADMINISTRATOR, $harare],
            // A second province, to show that one provincial administrator
            // cannot see another province's swimmers, clubs or entries.
            ['bulawayo@zimbabweswimming.co.zw', 'Thandiwe', 'Ndlovu', Roles::PROVINCIAL_ADMINISTRATOR, $bulawayo],
            ['club@zimbabweswimming.co.zw', 'Tariro', 'Chikwava', Roles::CLUB_ADMINISTRATOR, $lesBrown],
            // A second club in the same province as the one above, and a third
            // in another province, so club-level scoping can be shown too.
            ['dolphins@zimbabweswimming.co.zw', 'Kudakwashe', 'Moyo', Roles::CLUB_ADMINISTRATOR, $dolphins],
            ['barracudas@zimbabweswimming.co.zw', 'Sibusiso', 'Dube', Roles::CLUB_ADMINISTRATOR, $barracudas],
            ['coach@zimbabweswimming.co.zw', 'Brian', 'Ncube', Roles::COACH, $lesBrown],
            ['official@zimbabweswimming.co.zw', 'Craig', 'Jones', Roles::GALA_OFFICIAL, $harare],
            ['parent@zimbabweswimming.co.zw', 'Rudo', 'Moyo', Roles::PARENT_GUARDIAN, $lesBrown],
            ['swimmer@zimbabweswimming.co.zw', 'Peter', 'Wetzlar', Roles::SWIMMER, $lesBrown],
        ];

        foreach ($accounts as [$email, $firstName, $lastName, $role, $organization]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => trim($firstName.' '.($lastName ?? '')),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'current_organization_id' => $organization->getKey(),
                ],
            );

            $user->organizations()->syncWithoutDetaching([
                $organization->getKey() => ['is_default' => true],
            ]);

            // Roles are team scoped, so the organisation must be set first.
            setPermissionsTeamId($organization->getKey());
            $user->unsetRelation('roles');

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        setPermissionsTeamId(null);
    }
}

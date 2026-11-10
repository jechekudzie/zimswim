<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SeasonSeeder::class,
            OrganizationSeeder::class,
            CommitteeSeeder::class,
            VenueSeeder::class,
            CalendarSeeder::class,
            GalaProgrammeSeeder::class,
            ContentSeeder::class,
            AcademySeeder::class,
            DemoAccountSeeder::class,
            SampleRegistrySeeder::class,
            DemoGalaDaySeeder::class,
        ]);
    }
}

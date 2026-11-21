<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Everything the federation actually runs on, and nothing invented.
 *
 * This is the seeder to run on a live server. It writes the handbook: the
 * roles, the season and its fees, the national body and its provinces and
 * clubs, the executives, the national pools, the calendar, the gala
 * programmes, and the officials' courses. Every one of them is written with
 * updateOrCreate, so running this again updates rather than duplicates.
 *
 * It deliberately leaves out DemoAccountSeeder, SampleRegistrySeeder,
 * DemoGalaDaySeeder and OfficialJourneySeeder, which DatabaseSeeder calls.
 * Those invent swimmers, galas and results, and DemoAccountSeeder opens a
 * login at every level of access behind one development password — which is
 * why `php artisan db:seed` with no class must never be run on a server, and
 * why this exists as the thing to run instead.
 *
 * ContentSeeder is also left out. It writes sponsors and news as live
 * published content, and who sponsors the federation is a decision for the
 * federation, not for a seeder. Add those through the admin.
 */
class ReferenceDataSeeder extends Seeder
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
            AcademySeeder::class,
        ]);
    }
}

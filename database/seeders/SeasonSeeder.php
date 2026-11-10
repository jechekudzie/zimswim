<?php

namespace Database\Seeders;

use App\Models\FeeSchedule;
use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $season = Season::updateOrCreate(
            ['slug' => '2026-2027'],
            [
                'name' => '2026/2027 Season',
                'starts_on' => '2026-05-01',
                'ends_on' => '2027-04-30',
                // Entry times count from 1 May 2026 and age is taken at year end.
                'age_up_date' => '2026-12-31',
                'is_current' => true,
            ],
        );

        $fees = [
            ['code' => 'athlete-capitation', 'name' => 'Swimming Athlete Capitation', 'amount' => 20.00, 'unit' => 'per athlete'],
            ['code' => 'technical-official', 'name' => 'Technical Official', 'amount' => 10.00, 'unit' => 'per official'],
            ['code' => 'zimbabwe-aquatics', 'name' => 'Zimbabwe Aquatics', 'amount' => 20.00, 'unit' => 'per athlete'],
            ['code' => 'src-levy', 'name' => 'SRC Levy', 'amount' => null, 'unit' => null, 'notes' => 'To be advised'],
            ['code' => 'club-membership', 'name' => 'Club Membership', 'amount' => 20.00, 'unit' => 'per club'],
            ['code' => 'provincial-membership-10-30', 'name' => 'Provincial Membership (10 to 30 swimmers)', 'amount' => 10.00, 'unit' => 'per province'],
            ['code' => 'provincial-membership-31-50', 'name' => 'Provincial Membership (31 to 50 swimmers)', 'amount' => 20.00, 'unit' => 'per province'],
            ['code' => 'provincial-membership-51-100', 'name' => 'Provincial Membership (51 to 100 swimmers)', 'amount' => 30.00, 'unit' => 'per province'],
            ['code' => 'provincial-membership-51-plus', 'name' => 'Provincial Membership (51+ swimmers)', 'amount' => 50.00, 'unit' => 'per province'],
            ['code' => 'coach-registration', 'name' => 'Coach Registration', 'amount' => 20.00, 'unit' => 'per coach'],
            ['code' => 'national-sprint-entry', 'name' => 'National Sprint Entry', 'amount' => 20.00, 'unit' => 'per swimmer'],
            ['code' => 'junior-nationals-entry', 'name' => 'Junior Nationals Entry', 'amount' => 20.00, 'unit' => 'per swimmer'],
            ['code' => 'senior-nationals-entry', 'name' => 'Senior Nationals Entry', 'amount' => 20.00, 'unit' => 'per swimmer'],
            ['code' => 'development-entry', 'name' => 'Development Entry', 'amount' => 20.00, 'unit' => 'per swimmer'],
        ];

        foreach ($fees as $position => $fee) {
            FeeSchedule::updateOrCreate(
                ['season_id' => $season->getKey(), 'code' => $fee['code']],
                [
                    'name' => $fee['name'],
                    'amount' => $fee['amount'],
                    'unit' => $fee['unit'],
                    'notes' => $fee['notes'] ?? null,
                    'position' => $position,
                ],
            );
        }
    }
}

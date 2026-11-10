<?php

namespace Database\Seeders;

use App\Enums\Course;
use App\Enums\GalaLevel;
use App\Enums\GalaStatus;
use App\Models\Gala;
use App\Models\Organization;
use App\Models\Season;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * The provincial and national championship calendar for 2026/2027. Only meets
 * on this calendar are recognised by Zimbabwe Swimming.
 */
class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        $season = Season::where('slug', '2026-2027')->firstOrFail();
        $national = Organization::where('slug', 'zimbabwe-swimming')->firstOrFail();

        foreach ($this->galas() as $gala) {
            $host = Organization::where('slug', $gala['host'])->first();

            if ($host === null) {
                continue;
            }

            Gala::updateOrCreate(
                ['slug' => $gala['slug']],
                [
                    'name' => $gala['name'],
                    'season_id' => $season->getKey(),
                    'host_organization_id' => $host->getKey(),
                    'sanctioning_organization_id' => $national->getKey(),
                    'venue_id' => isset($gala['venue'])
                        ? Venue::where('slug', $gala['venue'])->value('id')
                        : null,
                    'level' => $gala['level'],
                    'course' => $gala['course'] ?? Course::LongCourse,
                    'status' => $gala['status'] ?? GalaStatus::Sanctioned,
                    'starts_on' => $gala['starts_on'],
                    'ends_on' => $gala['ends_on'],
                    'age_up_date' => $gala['age_up_date'] ?? null,
                    'entries_close_at' => $gala['entries_close_at'] ?? null,
                    'entry_corrections_deadline' => $gala['entry_corrections_deadline'] ?? null,
                    'entry_fee' => $gala['entry_fee'] ?? 20.00,
                    'late_entry_fee' => $gala['late_entry_fee'] ?? 0,
                    'late_entry_event_fee' => $gala['late_entry_event_fee'] ?? 0,
                    'no_show_fine' => $gala['no_show_fine'] ?? 0,
                    'appeal_deposit' => $gala['appeal_deposit'] ?? 50.00,
                    'eligible_ages' => $gala['eligible_ages'] ?? null,
                    'description' => $gala['description'] ?? null,
                    'entries_email' => $gala['entries_email'] ?? null,
                    'qualifying_times_apply' => $gala['qualifying_times_apply'] ?? false,
                    'is_published' => true,
                    'sanctioned_at' => now(),
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function galas(): array
    {
        return [
            [
                'name' => 'Manicaland Championships',
                'slug' => 'manicaland-championships-2026',
                'host' => 'manicaland',
                'level' => GalaLevel::Provincial,
                'starts_on' => '2026-09-25',
                'ends_on' => '2026-09-25',
                'description' => 'Provincial championships. Further rounds follow on 30 October and 27 to 28 November 2026.',
            ],
            [
                'name' => 'Zimbabwe Swimming National Sprint Gala',
                'slug' => 'national-sprint-gala-2026',
                'host' => 'manicaland',
                'venue' => 'mutare-municipal-city-pool',
                'level' => GalaLevel::National,
                'starts_on' => '2026-10-16',
                'ends_on' => '2026-10-17',
                'age_up_date' => '2026-12-31',
                'entries_close_at' => '2026-10-05 23:59:00',
                'entry_corrections_deadline' => '2026-10-12',
                'entry_fee' => 20.00,
                'late_entry_fee' => 20.00,
                'late_entry_event_fee' => 5.00,
                'no_show_fine' => 50.00,
                'appeal_deposit' => 50.00,
                'eligible_ages' => '8 years and above',
                'entries_email' => 'hasentries@gmail.com',
                'description' => 'Swum under World Aquatics rules as separate gender, timed finals. Age groups are 9-10, 11/12, 13/14 and 15 & Over for 50m events, and 12/U, 13/14 and 15 & Over for 100m events. No qualifying times apply.',
            ],
            [
                'name' => 'Mashonaland West Championships',
                'slug' => 'mashonaland-west-championships-2026',
                'host' => 'mashonaland-west',
                'venue' => 'lomagundi-college-pool',
                'level' => GalaLevel::Provincial,
                'starts_on' => '2026-10-24',
                'ends_on' => '2026-10-24',
                'eligible_ages' => '8 to 12 years',
            ],
            [
                'name' => 'Mashonaland East Championships',
                'slug' => 'mashonaland-east-championships-2026',
                'host' => 'mashonaland-east',
                'level' => GalaLevel::Provincial,
                'starts_on' => '2026-11-11',
                'ends_on' => '2026-11-11',
                'eligible_ages' => '8 to 12 years',
            ],
            [
                'name' => 'Harare Senior Championships',
                'slug' => 'harare-senior-championships-2026',
                'host' => 'harare',
                'venue' => 'les-brown-pool',
                'level' => GalaLevel::Provincial,
                'starts_on' => '2026-11-20',
                'ends_on' => '2026-11-22',
                'eligible_ages' => '11 years and above',
                'description' => 'Run over two weekends: 20 to 22 November and 27 to 29 November 2026.',
            ],
            [
                'name' => 'Midlands Championships',
                'slug' => 'midlands-championships-2026',
                'host' => 'midlands',
                'level' => GalaLevel::Provincial,
                'starts_on' => '2026-12-05',
                'ends_on' => '2026-12-06',
            ],
            [
                'name' => 'Matabeleland North Championships',
                'slug' => 'matabeleland-north-championships-2026',
                'host' => 'matabeleland-north',
                'level' => GalaLevel::Provincial,
                'starts_on' => '2026-12-11',
                'ends_on' => '2026-12-13',
            ],
            [
                'name' => 'Harare Junior Championships',
                'slug' => 'harare-junior-championships-2027',
                'host' => 'harare',
                'level' => GalaLevel::Provincial,
                'starts_on' => '2027-01-16',
                'ends_on' => '2027-01-17',
                'eligible_ages' => '8 to 12 years',
            ],
            [
                'name' => 'Zimbabwe Swimming Junior Nationals',
                'slug' => 'junior-nationals-2027',
                'host' => 'zimbabwe-swimming',
                'venue' => 'les-brown-pool',
                'level' => GalaLevel::National,
                'starts_on' => '2027-01-29',
                'ends_on' => '2027-01-31',
                'age_up_date' => '2027-12-31',
                'entries_close_at' => '2027-01-11 23:59:00',
                'entry_corrections_deadline' => '2027-01-18',
                'entry_fee' => 20.00,
                'late_entry_fee' => 30.00,
                'late_entry_event_fee' => 5.00,
                'no_show_fine' => 10.00,
                'appeal_deposit' => 50.00,
                'eligible_ages' => '8 to 12 years',
                'entries_email' => 'hasentries@gmail.com',
                'qualifying_times_apply' => true,
                'description' => 'Heats and finals on a separate gender basis. Swimmers aged 10 to 12 need a qualifying time; no qualifying times for 13 years and over. A swimmer must hold a 200m IM and a 200m freestyle time on the national database from 1 May 2026. All times are converted to long course.',
            ],
            [
                'name' => 'Zimbabwe Swimming Senior Nationals',
                'slug' => 'senior-nationals-2027',
                'host' => 'zimbabwe-swimming',
                'venue' => 'bulawayo-city-pool',
                'level' => GalaLevel::National,
                'starts_on' => '2027-02-18',
                'ends_on' => '2027-02-22',
                'age_up_date' => '2027-12-31',
                'entry_fee' => 20.00,
                'appeal_deposit' => 50.00,
                'eligible_ages' => '13 years and above',
                'qualifying_times_apply' => true,
            ],
            [
                'name' => 'Zimbabwe Swimming National Development',
                'slug' => 'national-development-2027',
                'host' => 'zimbabwe-swimming',
                'level' => GalaLevel::National,
                'starts_on' => '2027-03-12',
                'ends_on' => '2027-03-14',
                'entry_fee' => 20.00,
                'eligible_ages' => '8 years and above',
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AccreditationStatus;
use App\Enums\MemberStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Enums\OrganizationType;
use App\Models\Member;
use App\Models\MemberRegistration;
use App\Models\OfficialAccreditation;
use App\Models\Organization;
use App\Models\Season;
use App\Models\TechnicalOfficial;
use Illuminate\Database\Seeder;

/**
 * A small, realistic registry so the admin screens have something to show
 * before real data is loaded.
 */
class SampleRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $season = Season::where('slug', '2026-2027')->firstOrFail();
        $clubs = Organization::query()->ofType(OrganizationType::Club)->with('parent')->get();

        if ($clubs->isEmpty()) {
            return;
        }

        // Squads are listed per club rather than spread evenly, because a club
        // needs four swimmers of the same age band and gender before it can
        // field a relay team, and the relay screens are only worth anything
        // with squads that look like real ones.
        $squads = [
            'les-brown-swimming-club' => [
                ['Tanaka', 'Moyo', '2014-03-11', 'female'],
                ['Ruvarashe', 'Chikwava', '2012-07-02', 'female'],
                ['Anesu', 'Marimo', '2013-02-18', 'female'],
                ['Rufaro', 'Chirwa', '2013-04-21', 'female'],
                ['Mazvita', 'Banda', '2012-10-27', 'female'],
                ['Takudzwa', 'Ndlovu', '2011-11-23', 'male'],
                ['Tatenda', 'Gumbo', '2010-12-05', 'male'],
                ['Simbarashe', 'Nyoni', '2012-06-08', 'male'],
                ['Kudakwashe', 'Zhou', '2011-08-16', 'male'],
                ['Tinashe', 'Chasi', '2012-02-02', 'male'],
            ],
            'harare-dolphins' => [
                ['Chiedza', 'Mutsvangwa', '2009-05-14', 'female'],
                ['Nokutenda', 'Mapfumo', '2010-01-19', 'female'],
                ['Ropafadzo', 'Kativhu', '2009-09-30', 'female'],
                ['Shamiso', 'Nyathi', '2010-07-07', 'female'],
                ['Farai', 'Sibanda', '2008-09-09', 'male'],
                ['Tapiwa', 'Mandaza', '2009-03-25', 'male'],
                ['Nigel', 'Rwodzi', '2008-11-12', 'male'],
                ['Munyaradzi', 'Chigumba', '2009-06-04', 'male'],
            ],
            'bulawayo-barracudas' => [
                ['Thandiwe', 'Khumalo', '2011-04-15', 'female'],
                ['Sibongile', 'Mpofu', '2012-08-23', 'female'],
                ['Nompilo', 'Ncube', '2011-12-01', 'female'],
                ['Zanele', 'Dlodlo', '2012-05-18', 'female'],
                ['Bongani', 'Sithole', '2011-02-09', 'male'],
                ['Mthokozisi', 'Nkomo', '2012-10-14', 'male'],
                ['Thabo', 'Maphosa', '2011-07-21', 'male'],
                ['Sipho', 'Masuku', '2012-03-06', 'male'],
            ],
            'hillside-swimming-club' => [
                ['Nyasha', 'Dube', '2015-01-30', 'male'],
                ['Panashe', 'Tshuma', '2014-09-17', 'female'],
                ['Kundai', 'Mlilo', '2015-06-23', 'female'],
            ],
            'mufakose-swimming-club' => [
                ['Tadiwanashe', 'Chirume', '2013-08-04', 'male'],
                ['Vimbai', 'Muchemwa', '2013-11-28', 'female'],
            ],
            'mutare-swimming-club' => [
                ['Tendai', 'Makoni', '2010-10-10', 'male'],
                ['Rutendo', 'Zimuto', '2011-05-02', 'female'],
            ],
            'lomagundi-college-swimming' => [
                ['Natasha', 'Pote', '2009-12-19', 'female'],
                ['Brandon', 'Viljoen', '2010-04-08', 'male'],
            ],
        ];

        $clubsBySlug = $clubs->keyBy('slug');
        $index = 0;

        foreach ($squads as $slug => $squad) {
            $club = $clubsBySlug->get($slug);

            if ($club === null) {
                continue;
            }

            foreach ($squad as [$firstName, $lastName, $dateOfBirth, $gender]) {
                $member = Member::updateOrCreate(
                    ['membership_number' => sprintf('ZSW-%05d', 1001 + $index)],
                    [
                        'organization_id' => $club->getKey(),
                        'province_organization_id' => $club->parent_id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'date_of_birth' => $dateOfBirth,
                        'gender' => $gender,
                        'status' => MemberStatus::Active,
                        'consent_given_at' => now()->subMonths(2),
                        'consent_given_by' => 'Parent or guardian',
                        'joined_on' => now()->subMonths(2)->toDateString(),
                    ],
                );

                MemberRegistration::updateOrCreate(
                    ['member_id' => $member->getKey(), 'season_id' => $season->getKey()],
                    [
                        'organization_id' => $club->getKey(),
                        'status' => 'active',
                        'capitation_amount' => 20.00,
                        'capitation_paid_at' => now()->subMonth(),
                        'aquatics_amount' => 20.00,
                        'aquatics_paid_at' => now()->subMonth(),
                        'approved_at' => now()->subMonth(),
                    ],
                );

                $index++;
            }
        }

        $officials = [
            ['Craig', 'Jones', 'harare', OfficialCategory::Referee, OfficialGrade::National],
            ['Angela', 'Mawere', 'harare', OfficialCategory::Starter, OfficialGrade::National],
            ['Louise', 'Benade', 'mashonaland-west', OfficialCategory::JudgeOfStroke, OfficialGrade::Provincial],
            ['Heather', 'Savoury', 'harare', OfficialCategory::ChiefTimekeeper, OfficialGrade::Provincial],
            ['Watson', 'Mhlanga', 'bulawayo', OfficialCategory::Timekeeper, OfficialGrade::Provincial],
            ['Carmel', 'Murray', 'manicaland', OfficialCategory::InspectorOfTurns, OfficialGrade::Provincial],
        ];

        foreach ($officials as $index => [$firstName, $lastName, $provinceSlug, $category, $grade]) {
            $province = Organization::where('slug', $provinceSlug)->first();
            $club = $province?->clubs()->first() ?? $clubs->first();

            if ($province === null) {
                continue;
            }

            $official = TechnicalOfficial::updateOrCreate(
                ['registration_number' => sprintf('TO-%04d', 101 + $index)],
                [
                    'organization_id' => $club->getKey(),
                    'province_organization_id' => $province->getKey(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'status' => 'active',
                ],
            );

            OfficialAccreditation::updateOrCreate(
                [
                    'technical_official_id' => $official->getKey(),
                    'category' => $category,
                    'grade' => $grade,
                ],
                [
                    'status' => AccreditationStatus::Active,
                    'learner_sessions_completed' => 6,
                    'clinic_attended_on' => now()->subYear()->toDateString(),
                    'exam_passed_on' => now()->subYear()->toDateString(),
                    'attained_on' => now()->subYear()->toDateString(),
                    'expires_on' => now()->addYear()->toDateString(),
                    'ratified_at' => now()->subYear(),
                ],
            );
        }
    }
}

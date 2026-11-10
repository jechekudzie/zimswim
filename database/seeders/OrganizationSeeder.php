<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * The national body and the provincial structure from the handbook's lines of
 * communication, with the clubs named in the 2026/2027 calendar.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $national = Organization::updateOrCreate(
            ['slug' => 'zimbabwe-swimming'],
            [
                'name' => 'Zimbabwe Swimming',
                'short_name' => 'ZimSwim',
                'code' => 'ZSW',
                'type' => OrganizationType::National,
                'description' => 'The national governing body for swimming in Zimbabwe, affiliated to Zimbabwe Aquatics, Africa Aquatics and World Aquatics.',
                'email' => 'secretary@zimbabweswimming.co.zw',
                'city' => 'Harare',
                'affiliation_status' => 'affiliated',
                'is_active' => true,
            ],
        );

        foreach ($this->provinces() as $province) {
            $organization = Organization::updateOrCreate(
                ['slug' => $province['slug']],
                [
                    'parent_id' => $national->getKey(),
                    'name' => $province['name'],
                    'short_name' => $province['short_name'] ?? null,
                    'code' => $province['code'],
                    'type' => OrganizationType::Province,
                    'description' => $province['description'] ?? null,
                    'city' => $province['city'] ?? null,
                    'affiliation_status' => 'affiliated',
                    'is_active' => true,
                ],
            );

            foreach ($province['clubs'] ?? [] as $club) {
                Organization::updateOrCreate(
                    ['slug' => $club['slug']],
                    [
                        'parent_id' => $organization->getKey(),
                        'name' => $club['name'],
                        'code' => $club['code'],
                        'type' => OrganizationType::Club,
                        'city' => $club['city'] ?? $province['city'] ?? null,
                        'affiliation_status' => 'affiliated',
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function provinces(): array
    {
        return [
            [
                'name' => 'Bulawayo Swimming',
                'slug' => 'bulawayo',
                'code' => 'BYO',
                'city' => 'Bulawayo',
                'clubs' => [
                    ['name' => 'Bulawayo Barracudas', 'slug' => 'bulawayo-barracudas', 'code' => 'BYO-BAR'],
                    ['name' => 'Hillside Swimming Club', 'slug' => 'hillside-swimming-club', 'code' => 'BYO-HIL'],
                ],
            ],
            [
                'name' => 'Harare Amateur Swimming',
                'slug' => 'harare',
                'short_name' => 'HAS',
                'code' => 'HRE',
                'city' => 'Harare',
                'description' => 'The provincial body for Harare, running the Harare Senior and Junior Championships.',
                'clubs' => [
                    ['name' => 'Les Brown Swimming Club', 'slug' => 'les-brown-swimming-club', 'code' => 'HRE-LBS'],
                    ['name' => 'Mufakose Swimming Club', 'slug' => 'mufakose-swimming-club', 'code' => 'HRE-MUF'],
                    ['name' => 'Harare Dolphins', 'slug' => 'harare-dolphins', 'code' => 'HRE-DOL'],
                ],
            ],
            [
                'name' => 'Manicaland Swimming',
                'slug' => 'manicaland',
                'code' => 'MAN',
                'city' => 'Mutare',
                'description' => 'Host province for the Zimbabwe Swimming National Sprint Gala at Mutare City Pool.',
                'clubs' => [
                    ['name' => 'Mutare Swimming Club', 'slug' => 'mutare-swimming-club', 'code' => 'MAN-MUT'],
                ],
            ],
            [
                'name' => 'Mashonaland Central Swimming',
                'slug' => 'mashonaland-central',
                'code' => 'MSC',
                'city' => 'Bindura',
            ],
            [
                'name' => 'Mashonaland East Swimming',
                'slug' => 'mashonaland-east',
                'code' => 'MSE',
                'city' => 'Marondera',
            ],
            [
                'name' => 'Mashonaland West Swimming',
                'slug' => 'mashonaland-west',
                'code' => 'MSW',
                'city' => 'Chinhoyi',
                'clubs' => [
                    ['name' => 'Lomagundi College Swimming', 'slug' => 'lomagundi-college-swimming', 'code' => 'MSW-LOM', 'city' => 'Chinhoyi'],
                ],
            ],
            [
                'name' => 'Masvingo Swimming',
                'slug' => 'masvingo',
                'code' => 'MSV',
                'city' => 'Masvingo',
            ],
            [
                'name' => 'Matabeleland North Swimming',
                'slug' => 'matabeleland-north',
                'code' => 'MTN',
                'city' => 'Hwange',
            ],
            [
                'name' => 'Matabeleland South Swimming',
                'slug' => 'matabeleland-south',
                'code' => 'MTS',
                'city' => 'Gwanda',
            ],
            [
                'name' => 'Midlands Swimming',
                'slug' => 'midlands',
                'code' => 'MID',
                'city' => 'Gweru',
            ],
            [
                'name' => 'CROCS',
                'slug' => 'crocs',
                'code' => 'CRC',
                'description' => 'The club representing Zimbabwean swimmers based out of the country.',
            ],
        ];
    }
}

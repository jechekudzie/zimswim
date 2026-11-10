<?php

namespace Database\Seeders;

use App\Enums\VenueGrade;
use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->venues() as $venue) {
            $province = Organization::where('slug', $venue['province'])->first();

            Venue::updateOrCreate(
                ['slug' => $venue['slug']],
                [
                    'name' => $venue['name'],
                    'province_organization_id' => $province?->getKey(),
                    'owner_type' => $venue['owner_type'],
                    'owner_name' => $venue['owner_name'] ?? null,
                    'city' => $venue['city'],
                    'address' => $venue['address'] ?? null,
                    'pool_length' => $venue['pool_length'] ?? null,
                    'lanes' => $venue['lanes'] ?? null,
                    'starting_blocks' => $venue['starting_blocks'] ?? false,
                    'backstroke_flags' => $venue['backstroke_flags'] ?? false,
                    'electronic_timing' => $venue['electronic_timing'] ?? false,
                    'warmup_pool' => $venue['warmup_pool'] ?? false,
                    'grade' => $venue['grade'],
                    'condition_notes' => $venue['condition_notes'] ?? null,
                    'notes' => $venue['notes'] ?? null,
                    'is_active' => $venue['is_active'] ?? true,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function venues(): array
    {
        return [
            [
                'name' => 'Les Brown Pool',
                'slug' => 'les-brown-pool',
                'province' => 'harare',
                'city' => 'Harare',
                'owner_type' => 'council',
                'owner_name' => 'City of Harare',
                'pool_length' => 50,
                'lanes' => 8,
                'starting_blocks' => true,
                'backstroke_flags' => true,
                'grade' => VenueGrade::National,
                'notes' => 'Hosts most Harare national and provincial events, including the Junior Nationals and Harare Senior Championships.',
            ],
            [
                'name' => 'Mufakose Pool',
                'slug' => 'mufakose-pool',
                'province' => 'harare',
                'city' => 'Harare',
                'owner_type' => 'council',
                'owner_name' => 'City of Harare',
                'pool_length' => 50,
                'lanes' => 10,
                'starting_blocks' => true,
                'grade' => VenueGrade::Provincial,
                'condition_notes' => 'Under refurbishment by Harare Amateur Swimming with the City of Harare.',
                'notes' => '50m by 25m, 10 lanes and deeper than Les Brown.',
            ],
            [
                'name' => 'Bulawayo City Pool',
                'slug' => 'bulawayo-city-pool',
                'province' => 'bulawayo',
                'city' => 'Bulawayo',
                'owner_type' => 'council',
                'owner_name' => 'Bulawayo City Council',
                'pool_length' => 50,
                'lanes' => 8,
                'starting_blocks' => true,
                'grade' => VenueGrade::National,
                'condition_notes' => 'Has had maintenance problems; confirm condition before sanctioning.',
                'notes' => 'Hosts the Zimbabwe Swimming Senior Nationals and regional championships.',
            ],
            [
                'name' => 'Hillside Teachers College Pool',
                'slug' => 'hillside-teachers-college-pool',
                'province' => 'bulawayo',
                'city' => 'Bulawayo',
                'owner_type' => 'school',
                'owner_name' => 'Hillside Teachers College',
                'pool_length' => 25,
                'lanes' => 6,
                'grade' => VenueGrade::Provincial,
                'notes' => 'Used for junior galas.',
            ],
            [
                'name' => 'Mutare Municipal City Pool',
                'slug' => 'mutare-municipal-city-pool',
                'province' => 'manicaland',
                'city' => 'Mutare',
                'owner_type' => 'council',
                'owner_name' => 'Mutare City Council',
                'pool_length' => 50,
                'lanes' => 8,
                'starting_blocks' => true,
                'electronic_timing' => false,
                'grade' => VenueGrade::National,
                'condition_notes' => 'No electronic timing; the National Sprint Gala runs on manual timing.',
                'notes' => 'Known as the Main Bath and home of the National Sprint Gala.',
            ],
            [
                'name' => 'Lomagundi College Pool',
                'slug' => 'lomagundi-college-pool',
                'province' => 'mashonaland-west',
                'city' => 'Chinhoyi',
                'owner_type' => 'school',
                'owner_name' => 'Lomagundi College',
                'pool_length' => 25,
                'lanes' => 6,
                'starting_blocks' => true,
                'grade' => VenueGrade::Provincial,
                'notes' => 'Hosts the Mashonaland West Championships.',
            ],
        ];
    }
}

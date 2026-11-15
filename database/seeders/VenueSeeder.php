<?php

namespace Database\Seeders;

use App\Enums\VenueGrade;
use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * The pools galas are swum at.
 *
 * Only the national pools are loaded. The provincial list runs to schools and
 * municipal pools that the provinces are still compiling, and a half-entered
 * venue register is worse than an honest gap: a club secretary who sees three
 * pools and knows of ten stops trusting the page. The grade is already there
 * for them, so a provincial pool can be added without a deployment.
 */
class VenueSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, int> $known */
        $known = [];

        foreach ($this->venues() as $venue) {
            $province = Organization::where('slug', $venue['province'])->first();

            $known[] = Venue::updateOrCreate(
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
            )->getKey();
        }

        $this->retire($known);
    }

    /**
     * Take out pools that are no longer on the list.
     *
     * A venue a gala has already been set at is left alone and reported: the
     * gala would lose the place it is being swum.
     *
     * @param  array<int, int>  $known
     */
    private function retire(array $known): void
    {
        $stale = Venue::query()
            ->whereNotIn('id', $known)
            ->withCount('galas')
            ->get();

        foreach ($stale as $venue) {
            if ($venue->galas_count > 0) {
                $this->command?->warn(sprintf(
                    'Left %s in place: %d gala(s) are set there.',
                    $venue->name,
                    $venue->galas_count,
                ));

                continue;
            }

            $venue->delete();
            $this->command?->info('Retired '.$venue->name.', which is not on the national list.');
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
        ];
    }
}

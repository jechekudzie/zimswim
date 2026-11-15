<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * The affiliated structure as the National Handbook sets it out: the national
 * body, the nine provincial associations, CROCS, and the clubs affiliated to
 * each.
 *
 * Province and club names come from the handbook and from the provincial
 * secretaries, not from the competition calendar — a club can appear on a
 * calendar under a team name that is not its registered one.
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

        /** @var array<int, int> $known */
        $known = [$national->getKey()];

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

            $known[] = $organization->getKey();

            foreach ($province['clubs'] ?? [] as $club) {
                $known[] = Organization::updateOrCreate(
                    ['slug' => $club['slug']],
                    [
                        'parent_id' => $organization->getKey(),
                        'name' => $club['name'],
                        'short_name' => $club['short_name'] ?? null,
                        'code' => $club['code'],
                        'type' => OrganizationType::Club,
                        // Left unset rather than inherited from the province:
                        // a club is not necessarily in its provincial capital,
                        // and a wrong town is worse than none.
                        'city' => $club['city'] ?? null,
                        'affiliation_status' => 'affiliated',
                        'is_active' => true,
                    ],
                )->getKey();
            }
        }

        $this->retire($known);
    }

    /**
     * Take out bodies that are no longer in the handbook.
     *
     * Anything carrying swimmers is left alone and reported instead: removing
     * it would orphan a register, and that is a decision for the federation
     * rather than for a seeder.
     *
     * @param  array<int, int>  $known
     */
    private function retire(array $known): void
    {
        $stale = Organization::query()
            ->whereNotIn('id', $known)
            ->withCount(['members', 'children'])
            ->get();

        foreach ($stale as $organization) {
            if ($organization->members_count > 0 || $organization->children_count > 0) {
                $this->command?->warn(sprintf(
                    'Left %s in place: it still carries %d swimmer(s) and %d body/bodies beneath it.',
                    $organization->name,
                    $organization->members_count,
                    $organization->children_count,
                ));

                continue;
            }

            $organization->delete();
            $this->command?->info('Retired '.$organization->name.', which is not in the handbook.');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function provinces(): array
    {
        return [
            [
                'name' => 'Harare Amateur Swimming',
                'short_name' => 'HAS',
                'slug' => 'harare',
                'code' => 'HAS',
                'city' => 'Harare',
                'clubs' => [
                    ['name' => 'Spartans Swimming Club', 'slug' => 'spartans-swimming-club', 'code' => 'HAS-SPA'],
                    ['name' => 'Vikings Swimming Club', 'slug' => 'vikings-swimming-club', 'code' => 'HAS-VIK'],
                    ['name' => 'Highlands Swimming Club', 'slug' => 'highlands-swimming-club', 'code' => 'HAS-HIG'],
                    ['name' => 'Sharks Swimming Club', 'slug' => 'sharks-swimming-club', 'code' => 'HAS-SHA'],
                    ['name' => 'Pirates Swimming Club', 'slug' => 'pirates-swimming-club', 'code' => 'HAS-PIR'],
                    ['name' => 'Wild Orcas Swimming Club', 'slug' => 'wild-orcas-swimming-club', 'code' => 'HAS-WIL'],
                    ['name' => 'Otters Swimming Club', 'slug' => 'otters-swimming-club', 'code' => 'HAS-OTT'],
                ],
            ],
            [
                'name' => 'Bulawayo Amateur Swimming Association',
                'short_name' => 'BASA',
                'slug' => 'bulawayo',
                'code' => 'BASA',
                'city' => 'Bulawayo',
                'clubs' => [
                    ['name' => 'Stingrays Swimming Club', 'slug' => 'stingrays-swimming-club', 'code' => 'BAS-STI'],
                    ['name' => 'Penguins Swimming Club', 'slug' => 'penguins-swimming-club', 'code' => 'BAS-PEN'],
                    ['name' => 'School of Swimming', 'short_name' => 'SOS', 'slug' => 'school-of-swimming', 'code' => 'BAS-SOS'],
                    ['name' => 'Affiliated Crusaders Swimmers', 'slug' => 'affiliated-crusaders-swimmers', 'code' => 'BAS-ACS'],
                    ['name' => 'Black Marlins', 'slug' => 'black-marlins', 'code' => 'BAS-BLM'],
                ],
            ],
            [
                'name' => 'Manicaland Province Swimming',
                'slug' => 'manicaland',
                'code' => 'MAN',
                'city' => 'Mutare',
                'clubs' => [
                    ['name' => 'Border Dolphins', 'slug' => 'border-dolphins', 'code' => 'MAN-BOR'],
                ],
            ],
            [
                'name' => 'Mashonaland East Province Swimming',
                'slug' => 'mashonaland-east',
                'code' => 'MSE',
                'city' => 'Marondera',
                'clubs' => [
                    ['name' => 'Dorados East', 'slug' => 'dorados-east', 'code' => 'MSE-DOR'],
                ],
            ],
            [
                'name' => 'Mashonaland West Province Swimming',
                'slug' => 'mashonaland-west',
                'code' => 'MSW',
                'city' => 'Chinhoyi',
                'clubs' => [
                    ['name' => 'Dorados West', 'slug' => 'dorados-west', 'code' => 'MSW-DOR'],
                ],
            ],
            [
                'name' => 'Matabeleland North Province Swimming',
                'slug' => 'matabeleland-north',
                'code' => 'MTN',
                'clubs' => [
                    ['name' => 'Hwange Dolphins Swimming Club', 'slug' => 'hwange-dolphins-swimming-club', 'code' => 'MTN-HWA'],
                    ['name' => 'Zambezi Tigers Athletes', 'slug' => 'zambezi-tigers-athletes', 'code' => 'MTN-ZAM'],
                    ['name' => 'St James', 'slug' => 'st-james', 'code' => 'MTN-STJ'],
                ],
            ],
            [
                'name' => 'Matabeleland South Province Swimming',
                'slug' => 'matabeleland-south',
                'code' => 'MTS',
                'clubs' => [
                    ['name' => 'Falcon Swimming Club', 'slug' => 'falcon-swimming-club', 'code' => 'MTS-FAL'],
                ],
            ],
            [
                'name' => 'Midlands Province Swimming',
                'slug' => 'midlands',
                'code' => 'MID',
                'city' => 'Gweru',
                'clubs' => [
                    ['name' => 'Maple Leaf', 'slug' => 'maple-leaf', 'code' => 'MID-MAP'],
                    ['name' => 'Dashing Dolphins', 'slug' => 'dashing-dolphins', 'code' => 'MID-DAS'],
                ],
            ],
            [
                'name' => 'Masvingo Province Swimming',
                'slug' => 'masvingo',
                'code' => 'MSV',
                'city' => 'Masvingo',
                'clubs' => [
                    ['name' => 'Lowveld Kingfishers Swimming Club', 'slug' => 'lowveld-kingfishers-swimming-club', 'code' => 'MSV-LOW'],
                ],
            ],
            [
                /*
                 * CROCS sits alongside the provinces in the handbook, with its
                 * own executive board, and carries a club of the same name:
                 * a swimmer registers to a club, so without one the swimmers
                 * it exists to represent could not be registered at all.
                 */
                'name' => 'CROCS',
                'slug' => 'crocs',
                'code' => 'CRC',
                'description' => 'The body representing Zimbabwean swimmers based outside the country.',
                'clubs' => [
                    ['name' => 'CROCS', 'slug' => 'crocs-club', 'code' => 'CRC-CRC'],
                ],
            ],
        ];
    }
}

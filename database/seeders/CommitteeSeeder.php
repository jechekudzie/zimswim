<?php

namespace Database\Seeders;

use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Executive boards and standing committees as published in the Zimbabwe
 * Swimming National Handbook 2026/2027.
 */
class CommitteeSeeder extends Seeder
{
    public function run(): void
    {
        $national = Organization::where('slug', 'zimbabwe-swimming')->firstOrFail();

        $this->board($national, 'Zimbabwe Swimming Executive', 'executive', [
            ['Rumbidzai Mahobele', 'Chairperson'],
            ['Michael Roper', 'Vice Chairperson'],
            ['Valerie Musere', 'Secretary', 'secretary@zimbabweswimming.co.zw', '0773398978'],
            ['Emmah Mungoni', 'Treasurer'],
            ['Tendai Mazike', 'Marketing and Sponsorship'],
            ['Ndanaestai Nyamhunga', 'Results Convenor'],
        ]);

        $this->board($national, 'Athletes Committee', 'committee', [
            ['Paige van der Westhuizen', 'Chairperson'],
            ['Peter Wetzlar', 'Vice Chairperson'],
            ['Nomvula Mjimba', 'Secretary'],
            ['Cory Werrett', 'Committee Member'],
        ]);

        foreach ($this->standingCommittees() as $position => [$name, $chairperson]) {
            $committee = Committee::updateOrCreate(
                ['organization_id' => $national->getKey(), 'slug' => Str::slug($name)],
                ['name' => $name, 'kind' => 'committee', 'position' => $position + 10],
            );

            CommitteeMember::updateOrCreate(
                ['committee_id' => $committee->getKey(), 'person_name' => $chairperson],
                ['role' => 'Chairperson', 'position' => 0],
            );
        }

        foreach ($this->provincialBoards() as $slug => $officers) {
            $province = Organization::where('slug', $slug)->first();

            if ($province === null) {
                continue;
            }

            $this->board($province, $province->name.' Executive Board', 'executive', $officers);
        }
    }

    /**
     * @param  array<int, array<int, string>>  $officers
     */
    private function board(Organization $organization, string $name, string $kind, array $officers): void
    {
        $committee = Committee::updateOrCreate(
            ['organization_id' => $organization->getKey(), 'slug' => Str::slug($name)],
            ['name' => $name, 'kind' => $kind, 'position' => $kind === 'executive' ? 0 : 5],
        );

        foreach ($officers as $position => $officer) {
            CommitteeMember::updateOrCreate(
                ['committee_id' => $committee->getKey(), 'person_name' => $officer[0]],
                [
                    'role' => $officer[1],
                    'email' => $officer[2] ?? null,
                    'phone' => $officer[3] ?? null,
                    'position' => $position,
                ],
            );
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function standingCommittees(): array
    {
        return [
            ['Technical', 'Craig Jones'],
            ['Technical Officials', 'Angela Mawere'],
            ['Education and Training', 'Winnie Muvevi'],
            ['Sports and Medical', 'Solomon Mukungunugwa'],
            ['Events and Competition', 'Wilfred Kalipengule'],
            ['Tours', 'Louise Benade'],
            ['Sports Development', 'Paul Mwipikeni'],
            ['Legal, Governance and Ethics', 'Setmore Ndanga'],
            ['Elite Athlete Development', 'Lindsy Tudor Cole'],
            ['Selection Committee', 'Duncan Watson'],
            ['National Records', 'Louise Benade'],
        ];
    }

    /**
     * @return array<string, array<int, array<int, string>>>
     */
    private function provincialBoards(): array
    {
        return [
            'bulawayo' => [
                ['Philip Evans', 'Chairperson'],
                ['Nvusumuszi Ncube', 'Vice Chairperson'],
                ['Watson Mhlanga', 'Treasurer'],
                ['Charmaine Botch-Masangamise', 'Secretary'],
            ],
            'harare' => [
                ['Paul Mwipikeni', 'Chairperson'],
                ['Wilfred Kalipengule', 'Vice Chairperson'],
                ['Heather Savoury', 'Treasurer'],
                ['Amy Wicks', 'Secretary'],
            ],
            'manicaland' => [
                ['Dudzile Mthethwa', 'Chairperson'],
                ['Nyasha Mukonyora', 'Vice Chairperson'],
                ['Carmel Murray', 'Treasurer'],
                ['Tsitsi Nyanzanza', 'Secretary'],
            ],
            'mashonaland-east' => [
                ['Tara Morgan', 'Chairperson'],
                ['Maica MacMillian', 'Vice Chairperson'],
                ['Leanne Morland', 'Treasurer'],
            ],
            'mashonaland-west' => [
                ['Michael Roper', 'Chairperson'],
                ['Leanne Ottman', 'Vice Chairperson'],
                ['Rudo Lusiyano', 'Treasurer'],
                ['Louise Benade', 'Secretary'],
            ],
            'masvingo' => [
                ['Casey Nolan-Evans', 'Chairperson'],
            ],
            'matabeleland-north' => [
                ['Alison Steiner', 'Chairperson'],
                ['Charlotte Bean', 'Vice Chairperson'],
                ['Nosipo Moyo', 'Treasurer'],
                ['Sikhathele Mabena', 'Secretary'],
            ],
            'matabeleland-south' => [
                ['Shireen Chisungo', 'Chairperson'],
                ['Duncan Watson', 'Vice Chairperson'],
                ['Kelly Stanley', 'Treasurer'],
                ['Phephe Nnam', 'Secretary'],
            ],
            'midlands' => [
                ['Marc Levendale', 'Chairperson'],
                ['Clara Madzoke', 'Vice Chairperson'],
                ['Pateince Nkala', 'Treasurer'],
                ['Nazreen Rensburg', 'Secretary'],
            ],
            'crocs' => [
                ['Grant Chivandire', 'Chairperson'],
                ['Douglas van der Westhuizen', 'Treasurer'],
                ['Clearance Nyoni', 'Secretary'],
            ],
        ];
    }
}

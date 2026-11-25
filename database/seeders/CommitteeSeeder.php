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
    /**
     * Every member row this run wrote, as committee id => member ids.
     *
     * @var array<int, array<int, int>>
     */
    private array $seated = [];

    public function run(): void
    {
        $national = Organization::where('slug', 'zimbabwe-swimming')->firstOrFail();

        $this->board($national, 'Zimbabwe Swimming Executive', 'executive', [
            ['Rumbidzai Mahobele', 'Chairperson'],
            ['Michael Roper', 'Vice Chairperson'],
            ['Valerie Musere', 'Secretary', 'secretary@zimbabweswimming.co.zw', '0773398978'],
            ['Emmah Mungoni', 'Treasurer'],
            ['Tendai Mazike', 'Marketing and Sponsorship'],
            ['Ndanatsei Nyamhunga', 'Results Convenor'],
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

            $this->seated[$committee->getKey()][] = CommitteeMember::updateOrCreate(
                ['committee_id' => $committee->getKey(), 'role' => 'Chairperson'],
                ['person_name' => $chairperson, 'position' => 0],
            )->getKey();
        }

        foreach ($this->provincialBoards() as $slug => $officers) {
            $province = Organization::where('slug', $slug)->first();

            if ($province === null) {
                continue;
            }

            $this->board($province, $province->name.' Executive Board', 'executive', $officers);
        }

        $this->prune();
    }

    /**
     * Clear out what the old keying left behind.
     *
     * Members used to be keyed on the person's name and executive boards on a
     * slug built from their organisation's name, so a corrected spelling or a
     * renamed province left a second row standing beside the right one and
     * the about page showed both. Anything this run did not write into a seat
     * it did write is one of those, and goes.
     *
     * Only seats the handbook names are touched. A member added through the
     * admin holds an office the seeder has no opinion about, and is left
     * exactly where the federation put them.
     */
    private function prune(): void
    {
        foreach ($this->seated as $committeeId => $memberIds) {
            $roles = CommitteeMember::whereIn('id', $memberIds)->pluck('role');

            $duplicates = CommitteeMember::query()
                ->where('committee_id', $committeeId)
                ->whereIn('role', $roles)
                ->whereNotIn('id', $memberIds)
                ->get();

            foreach ($duplicates as $duplicate) {
                $duplicate->delete();
                $this->command?->info(sprintf(
                    'Removed a second %s on committee %d: %s.',
                    $duplicate->role,
                    $committeeId,
                    $duplicate->person_name,
                ));
            }
        }

        $boards = Committee::query()
            ->where('kind', 'executive')
            ->whereNotIn('id', array_keys($this->seated))
            ->whereIn('organization_id', Committee::query()
                ->where('kind', 'executive')
                ->whereIn('id', array_keys($this->seated))
                ->pluck('organization_id'))
            ->get();

        foreach ($boards as $board) {
            $board->members()->delete();
            $board->delete();
            $this->command?->info('Removed the stale '.$board->name.'; its body already has a board.');
        }
    }

    /**
     * Seat the officers of a board, keyed by the office rather than by the
     * name holding it.
     *
     * The federation sends spelling corrections — a chairperson's name typed
     * two ways between the handbook and the minutes. Keyed on the name, a
     * correction seeded a second member and left the misspelt one on the
     * about page beside it. Keyed on the office, it is the correction it was
     * meant to be. Position is deliberately not the key: a member added
     * through the admin takes position 0 by default, and the seeder would
     * write the handbook's chairperson straight over them.
     *
     * @param  array<int, array<int, string>>  $officers
     */
    private function board(Organization $organization, string $name, string $kind, array $officers): void
    {
        /*
         * An executive board is found by the body it belongs to, not by its
         * slug. The slug is built from the organisation's name, so renaming a
         * province — Bulawayo Swimming to Bulawayo Amateur Swimming
         * Association — used to seed a second board rather than rename the
         * one that was there, and the about page showed both.
         */
        $committee = Committee::updateOrCreate(
            $kind === 'executive'
                ? ['organization_id' => $organization->getKey(), 'kind' => 'executive']
                : ['organization_id' => $organization->getKey(), 'slug' => Str::slug($name)],
            [
                'name' => $name,
                'slug' => Str::slug($name),
                'kind' => $kind,
                'position' => $kind === 'executive' ? 0 : 5,
            ],
        );

        foreach ($officers as $position => $officer) {
            $this->seated[$committee->getKey()][] = CommitteeMember::updateOrCreate(
                ['committee_id' => $committee->getKey(), 'role' => $officer[1]],
                [
                    'person_name' => $officer[0],
                    'email' => $officer[2] ?? null,
                    'phone' => $officer[3] ?? null,
                    'position' => $position,
                ],
            )->getKey();
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
                ['Vusumuzi Ncube', 'Vice Chairperson'],
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
                // The handbook has her holding both offices on this board.
                ['Tara Morgan', 'Secretary'],
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
                ['Shireen Ovidio', 'Chairperson'],
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
                ['Douglas Van Der Westhuizen', 'Treasurer'],
                ['Clearance Nyoni', 'Secretary'],
            ],
        ];
    }
}

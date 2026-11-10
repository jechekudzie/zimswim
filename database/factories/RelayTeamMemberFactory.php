<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\RelayTeam;
use App\Models\RelayTeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelayTeamMember>
 */
class RelayTeamMemberFactory extends Factory
{
    protected $model = RelayTeamMember::class;

    public function definition(): array
    {
        return [
            'relay_team_id' => RelayTeam::factory(),
            'member_id' => Member::factory(),
            'leg' => 1,
            'is_borrowed' => false,
        ];
    }
}

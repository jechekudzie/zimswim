<?php

namespace Database\Factories;

use App\Models\Committee;
use App\Models\CommitteeMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
{
    protected $model = CommitteeMember::class;

    public function definition(): array
    {
        return [
            'committee_id' => Committee::factory(),
            'person_name' => fake()->name(),
            'role' => fake()->randomElement(['Chairperson', 'Vice Chairperson', 'Secretary', 'Treasurer']),
            'position' => 0,
            'is_published' => true,
        ];
    }
}

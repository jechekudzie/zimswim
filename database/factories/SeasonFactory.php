<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'name' => '2026/2027 Season',
            'starts_on' => '2026-05-01',
            'ends_on' => '2027-04-30',
            'age_up_date' => '2026-12-31',
            'is_current' => true,
        ];
    }
}

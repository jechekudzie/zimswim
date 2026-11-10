<?php

namespace Database\Factories;

use App\Models\FeeSchedule;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeSchedule>
 */
class FeeScheduleFactory extends Factory
{
    protected $model = FeeSchedule::class;

    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'name' => 'Swimming Athlete Capitation',
            'code' => fake()->unique()->slug(2),
            'amount' => 20,
            'unit' => 'per athlete',
            'position' => 0,
        ];
    }
}

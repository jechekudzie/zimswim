<?php

namespace Database\Factories;

use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalaEvent>
 */
class GalaEventFactory extends Factory
{
    protected $model = GalaEvent::class;

    public function definition(): array
    {
        return [
            'gala_id' => Gala::factory(),
            'event_number' => fake()->unique()->numberBetween(101, 299),
            'gender' => fake()->randomElement(['girls', 'boys']),
            'age_group' => '11/12',
            'min_age' => 11,
            'max_age' => 12,
            'stroke' => Stroke::Freestyle,
            'distance' => 50,
            'round' => 'timed_final',
            'is_relay' => false,
        ];
    }
}

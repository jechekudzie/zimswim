<?php

namespace Database\Factories;

use App\Models\OfficialTraining;
use App\Models\OfficialTrainingRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialTrainingRegistration>
 */
class OfficialTrainingRegistrationFactory extends Factory
{
    protected $model = OfficialTrainingRegistration::class;

    public function definition(): array
    {
        return [
            'official_training_id' => OfficialTraining::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('07########'),
            'registered_at' => now(),
        ];
    }
}

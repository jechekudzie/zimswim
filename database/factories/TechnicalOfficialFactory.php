<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TechnicalOfficial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicalOfficial>
 */
class TechnicalOfficialFactory extends Factory
{
    protected $model = TechnicalOfficial::class;

    public function definition(): array
    {
        return [
            'registration_number' => 'TO-'.fake()->unique()->numerify('####'),
            'organization_id' => Organization::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => now()->subYears(35)->toDateString(),
            'status' => 'active',
        ];
    }
}

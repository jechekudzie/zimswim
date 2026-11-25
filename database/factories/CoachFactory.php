<?php

namespace Database\Factories;

use App\Models\Coach;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coach>
 */
class CoachFactory extends Factory
{
    protected $model = Coach::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'registration_number' => 'CO-'.fake()->unique()->numerify('####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
        ];
    }

    /**
     * The coach a club puts forward as the person to speak to.
     */
    public function head(): static
    {
        return $this->state(fn (): array => ['is_head_coach' => true]);
    }
}

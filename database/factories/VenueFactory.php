<?php

namespace Database\Factories;

use App\Enums\VenueGrade;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city().' Pool',
            'owner_type' => 'council',
            'city' => fake()->city(),
            'pool_length' => 50,
            'lanes' => 8,
            'starting_blocks' => true,
            'grade' => VenueGrade::National,
            'is_active' => true,
        ];
    }

    public function graded(VenueGrade $grade): static
    {
        return $this->state(fn (): array => ['grade' => $grade]);
    }
}

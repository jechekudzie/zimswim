<?php

namespace Database\Factories;

use App\Enums\TrainingKind;
use App\Models\OfficialTraining;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialTraining>
 */
class OfficialTrainingFactory extends Factory
{
    protected $model = OfficialTraining::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => 'Timekeeper clinic '.fake()->unique()->numerify('####'),
            'kind' => TrainingKind::Clinic,
            'held_on' => now()->addMonth()->toDateString(),
            'location' => fake()->city(),
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function past(): static
    {
        return $this->state(fn (): array => [
            'held_on' => now()->subMonth()->toDateString(),
        ]);
    }
}

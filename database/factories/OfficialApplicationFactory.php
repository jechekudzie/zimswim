<?php

namespace Database\Factories;

use App\Enums\OfficialApplicationStatus;
use App\Enums\OfficialCategory;
use App\Models\OfficialApplication;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialApplication>
 */
class OfficialApplicationFactory extends Factory
{
    protected $model = OfficialApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => OfficialApplication::newReference(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+263 77'.fake()->numerify('# ### ###'),
            'date_of_birth' => fake()->dateTimeBetween('-55 years', '-20 years')->format('Y-m-d'),
            'category' => OfficialCategory::Timekeeper,
            'motivation' => fake()->sentence(12),
            'status' => OfficialApplicationStatus::Submitted,
        ];
    }

    public function forClub(Organization $club): static
    {
        return $this->state(fn (): array => [
            'organization_id' => $club->getKey(),
            'province_organization_id' => $club->parent_id,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => OfficialApplicationStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (): array => [
            'status' => OfficialApplicationStatus::Declined,
            'reviewed_at' => now(),
            'review_notes' => 'Below the minimum age for the category applied for.',
        ]);
    }
}

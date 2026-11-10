<?php

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Swimming',
            'type' => OrganizationType::Club,
            'code' => strtoupper(fake()->unique()->bothify('???-###')),
            'city' => fake()->city(),
            'affiliation_status' => 'affiliated',
            'is_active' => true,
        ];
    }

    public function national(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Zimbabwe Swimming',
            'type' => OrganizationType::National,
            'parent_id' => null,
        ]);
    }

    public function province(?Organization $parent = null): static
    {
        return $this->state(fn (): array => [
            'name' => fake()->unique()->city().' Province Swimming',
            'type' => OrganizationType::Province,
            'parent_id' => $parent?->getKey(),
        ]);
    }

    public function club(?Organization $parent = null): static
    {
        return $this->state(fn (): array => [
            'type' => OrganizationType::Club,
            'parent_id' => $parent?->getKey(),
        ]);
    }
}

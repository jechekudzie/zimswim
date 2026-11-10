<?php

namespace Database\Factories;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'membership_number' => 'ZSW-'.fake()->unique()->numerify('#####'),
            'organization_id' => Organization::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-17 years', '-9 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'status' => MemberStatus::Active,
            'consent_given_at' => now(),
            'consent_given_by' => fake()->name(),
        ];
    }

    public function adult(): static
    {
        return $this->state(fn (): array => [
            'date_of_birth' => now()->subYears(22)->toDateString(),
            'consent_given_at' => null,
            'consent_given_by' => null,
        ]);
    }

    public function withoutConsent(): static
    {
        return $this->state(fn (): array => [
            'consent_given_at' => null,
            'consent_given_by' => null,
        ]);
    }
}

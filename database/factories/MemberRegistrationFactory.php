<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberRegistration;
use App\Models\Organization;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberRegistration>
 */
class MemberRegistrationFactory extends Factory
{
    protected $model = MemberRegistration::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'season_id' => Season::factory(),
            'organization_id' => Organization::factory(),
            'status' => 'active',
            'capitation_amount' => 20,
            'capitation_paid_at' => now(),
            'aquatics_amount' => 20,
            'aquatics_paid_at' => now(),
            'approved_at' => now(),
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn (): array => [
            'capitation_paid_at' => null,
            'aquatics_paid_at' => null,
            'status' => 'pending',
        ]);
    }
}

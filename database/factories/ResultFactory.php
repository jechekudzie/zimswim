<?php

namespace Database\Factories;

use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Result;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        return [
            'gala_id' => Gala::factory(),
            'gala_event_id' => GalaEvent::factory(),
            'member_id' => Member::factory(),
            'organization_id' => Organization::factory(),
            'time_ms' => fake()->numberBetween(28_000, 45_000),
            'place' => fake()->numberBetween(1, 8),
            'status' => 'official',
        ];
    }

    public function disqualified(string $ruleCode = '7.4'): static
    {
        return $this->state(fn (): array => [
            'status' => 'dq',
            'time_ms' => null,
            'place' => null,
            'dq_code' => $ruleCode,
        ]);
    }
}

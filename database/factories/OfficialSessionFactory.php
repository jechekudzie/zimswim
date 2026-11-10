<?php

namespace Database\Factories;

use App\Models\OfficialSession;
use App\Models\TechnicalOfficial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialSession>
 */
class OfficialSessionFactory extends Factory
{
    protected $model = OfficialSession::class;

    public function definition(): array
    {
        return [
            'technical_official_id' => TechnicalOfficial::factory(),
            'role' => 'timekeeper',
            'as_learner' => false,
            'worked_on' => now()->subMonths(2)->toDateString(),
            'signed_off_at' => now()->subMonths(2),
        ];
    }

    public function asLearner(): static
    {
        return $this->state(fn (): array => ['as_learner' => true]);
    }
}

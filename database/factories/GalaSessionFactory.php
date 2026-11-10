<?php

namespace Database\Factories;

use App\Models\Gala;
use App\Models\GalaSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalaSession>
 */
class GalaSessionFactory extends Factory
{
    protected $model = GalaSession::class;

    public function definition(): array
    {
        return [
            'gala_id' => Gala::factory(),
            'name' => 'Saturday AM',
            'position' => 1,
            'held_on' => now()->addMonth()->toDateString(),
            'warmup_at' => '06:30',
            'starts_at' => '08:30',
        ];
    }
}

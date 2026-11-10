<?php

namespace Database\Factories;

use App\Models\Gala;
use App\Models\GalaOfficial;
use App\Models\TechnicalOfficial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalaOfficial>
 */
class GalaOfficialFactory extends Factory
{
    protected $model = GalaOfficial::class;

    public function definition(): array
    {
        return [
            'gala_id' => Gala::factory(),
            'technical_official_id' => TechnicalOfficial::factory(),
            'role' => 'referee',
            'status' => 'confirmed',
        ];
    }
}

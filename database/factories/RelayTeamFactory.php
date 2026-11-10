<?php

namespace Database\Factories;

use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\Organization;
use App\Models\RelayTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelayTeam>
 */
class RelayTeamFactory extends Factory
{
    protected $model = RelayTeam::class;

    public function definition(): array
    {
        return [
            'gala_id' => Gala::factory(),
            'gala_event_id' => GalaEvent::factory(),
            'organization_id' => Organization::factory(),
            'name' => fake()->city().' A',
            'letter' => 'A',
            'status' => 'entered',
        ];
    }
}

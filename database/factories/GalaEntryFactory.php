<?php

namespace Database\Factories;

use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalaEntry>
 */
class GalaEntryFactory extends Factory
{
    protected $model = GalaEntry::class;

    public function definition(): array
    {
        return [
            'gala_id' => Gala::factory(),
            'gala_event_id' => GalaEvent::factory(),
            'member_id' => Member::factory(),
            'organization_id' => Organization::factory(),
            'entry_time_ms' => fake()->numberBetween(28_000, 45_000),
            'status' => 'entered',
            'is_late_entry' => false,
        ];
    }
}

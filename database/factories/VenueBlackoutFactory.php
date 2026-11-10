<?php

namespace Database\Factories;

use App\Models\Venue;
use App\Models\VenueBlackout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueBlackout>
 */
class VenueBlackoutFactory extends Factory
{
    protected $model = VenueBlackout::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'starts_on' => now()->addMonth()->toDateString(),
            'ends_on' => now()->addMonth()->addWeek()->toDateString(),
            'reason' => 'Maintenance',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\Course;
use App\Enums\GalaLevel;
use App\Enums\GalaStatus;
use App\Models\Gala;
use App\Models\Organization;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gala>
 */
class GalaFactory extends Factory
{
    protected $model = Gala::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'name' => fake()->unique()->city().' Championships',
            'season_id' => Season::factory(),
            'host_organization_id' => Organization::factory(),
            'level' => GalaLevel::Provincial,
            'course' => Course::ShortCourse,
            'status' => GalaStatus::Submitted,
            'starts_on' => $start->format('Y-m-d'),
            'ends_on' => $start->format('Y-m-d'),
            'entry_fee' => 20,
            'is_published' => false,
        ];
    }

    public function sanctioned(): static
    {
        return $this->state(fn (): array => [
            'status' => GalaStatus::Sanctioned,
            'sanctioned_at' => now(),
            'is_published' => true,
        ]);
    }
}

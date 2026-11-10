<?php

namespace Database\Factories;

use App\Enums\Course;
use App\Enums\RecordStatus;
use App\Enums\Stroke;
use App\Models\SwimRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SwimRecord>
 */
class SwimRecordFactory extends Factory
{
    protected $model = SwimRecord::class;

    public function definition(): array
    {
        return [
            'scope' => 'national',
            'course' => Course::LongCourse,
            'gender' => 'female',
            'age_group' => '11/12',
            'stroke' => Stroke::Freestyle,
            'distance' => 50,
            'time_ms' => 30_000,
            'holder_name' => fake()->name(),
            'status' => RecordStatus::Ratified,
            'set_on' => now()->subYear()->toDateString(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => RecordStatus::Pending]);
    }
}

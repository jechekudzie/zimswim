<?php

namespace Database\Factories;

use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'title' => 'Timekeeper '.fake()->unique()->word(),
            'official_category' => OfficialCategory::Timekeeper,
            'awards_grade' => OfficialGrade::Provincial,
            'summary' => fake()->sentence(),
            'pass_mark' => 80,
            'max_attempts' => 3,
            'exam_question_count' => 4,
            'exam_minutes' => 30,
            'practical_sessions_required' => 6,
            'minimum_age' => 13,
            'fee' => 0,
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}

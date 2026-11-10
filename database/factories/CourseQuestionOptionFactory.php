<?php

namespace Database\Factories;

use App\Models\CourseQuestion;
use App\Models\CourseQuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseQuestionOption>
 */
class CourseQuestionOptionFactory extends Factory
{
    protected $model = CourseQuestionOption::class;

    public function definition(): array
    {
        return [
            'course_question_id' => CourseQuestion::factory(),
            'label' => fake()->sentence(3),
            'is_correct' => false,
            'position' => 0,
        ];
    }
}

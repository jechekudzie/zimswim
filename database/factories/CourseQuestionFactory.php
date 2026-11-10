<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\CourseQuestion;
use App\Models\CourseQuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseQuestion>
 */
class CourseQuestionFactory extends Factory
{
    protected $model = CourseQuestion::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'prompt' => fake()->unique()->sentence().'?',
            'type' => QuestionType::Single,
            'in_exam_bank' => true,
            'is_active' => true,
        ];
    }

    /**
     * Give the question one correct option and three wrong ones.
     */
    public function withOptions(): static
    {
        return $this->afterCreating(function (CourseQuestion $question): void {
            foreach (range(0, 3) as $position) {
                CourseQuestionOption::create([
                    'course_question_id' => $question->getKey(),
                    'label' => 'Option '.($position + 1),
                    'is_correct' => $position === 0,
                    'position' => $position,
                ]);
            }
        });
    }
}

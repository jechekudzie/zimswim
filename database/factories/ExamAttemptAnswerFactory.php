<?php

namespace Database\Factories;

use App\Models\CourseQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttemptAnswer>
 */
class ExamAttemptAnswerFactory extends Factory
{
    protected $model = ExamAttemptAnswer::class;

    public function definition(): array
    {
        return [
            'exam_attempt_id' => ExamAttempt::factory(),
            'course_question_id' => CourseQuestion::factory(),
            'position' => 1,
            'is_correct' => false,
        ];
    }
}

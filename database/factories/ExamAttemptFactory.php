<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    public function definition(): array
    {
        return [
            'course_enrolment_id' => CourseEnrolment::factory(),
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'attempt_number' => 1,
            'status' => 'in_progress',
            'question_count' => 4,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ];
    }
}

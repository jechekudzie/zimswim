<?php

namespace Database\Factories;

use App\Enums\EnrolmentStatus;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseEnrolment>
 */
class CourseEnrolmentFactory extends Factory
{
    protected $model = CourseEnrolment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'status' => EnrolmentStatus::InProgress,
            'enrolled_at' => now(),
            'practical_sessions_completed' => 0,
        ];
    }
}

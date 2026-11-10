<?php

namespace Database\Factories;

use App\Models\CourseEnrolment;
use App\Models\CourseLesson;
use App\Models\LessonCompletion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonCompletion>
 */
class LessonCompletionFactory extends Factory
{
    protected $model = LessonCompletion::class;

    public function definition(): array
    {
        return [
            'course_enrolment_id' => CourseEnrolment::factory(),
            'course_lesson_id' => CourseLesson::factory(),
            'completed_at' => now(),
        ];
    }
}

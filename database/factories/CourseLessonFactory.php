<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseLesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CourseLesson>
 */
class CourseLessonFactory extends Factory
{
    protected $model = CourseLesson::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->word().' '.fake()->word().' lesson');

        return [
            'course_id' => Course::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'position' => 0,
            'summary' => fake()->sentence(),
            'content' => fake()->paragraphs(2, true),
            'is_published' => true,
        ];
    }
}

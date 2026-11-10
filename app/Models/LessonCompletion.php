<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LessonCompletionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $course_enrolment_id
 * @property int $course_lesson_id
 * @property CarbonImmutable $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class LessonCompletion extends Model
{
    /** @use HasFactory<LessonCompletionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    /** @return BelongsTo<CourseEnrolment, $this> */
    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrolment::class, 'course_enrolment_id');
    }

    /** @return BelongsTo<CourseLesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'course_lesson_id');
    }
}

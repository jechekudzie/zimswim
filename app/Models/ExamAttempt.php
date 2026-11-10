<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_enrolment_id
 * @property int $course_id
 * @property int $user_id
 * @property int $attempt_number
 * @property string $status
 * @property int $question_count
 * @property int $correct_count
 * @property int|null $score
 * @property bool $passed
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CourseEnrolment, $this> */
    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrolment::class, 'course_enrolment_id');
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<ExamAttemptAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class)->orderBy('position');
    }

    public function isOpen(): bool
    {
        return $this->status === 'in_progress' && ! $this->hasExpired();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function secondsRemaining(): int
    {
        if ($this->expires_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->expires_at, absolute: false));
    }
}

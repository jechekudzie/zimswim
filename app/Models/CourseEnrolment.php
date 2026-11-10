<?php

namespace App\Models;

use App\Enums\EnrolmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\CourseEnrolmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_id
 * @property int $user_id
 * @property int|null $technical_official_id
 * @property int|null $organization_id
 * @property EnrolmentStatus $status
 * @property CarbonImmutable $enrolled_at
 * @property CarbonImmutable|null $theory_passed_at
 * @property CarbonImmutable|null $completed_at
 * @property int $practical_sessions_completed
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class CourseEnrolment extends Model
{
    /** @use HasFactory<CourseEnrolmentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => EnrolmentStatus::class,
            'enrolled_at' => 'datetime',
            'theory_passed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<TechnicalOfficial, $this> */
    public function technicalOfficial(): BelongsTo
    {
        return $this->belongsTo(TechnicalOfficial::class);
    }

    /** @return HasMany<ExamAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class)->orderBy('attempt_number');
    }

    /** @return HasMany<LessonCompletion, $this> */
    public function lessonCompletions(): HasMany
    {
        return $this->hasMany(LessonCompletion::class);
    }

    public function hasPassedTheory(): bool
    {
        return $this->theory_passed_at !== null;
    }

    public function attemptsUsed(): int
    {
        return $this->attempts()->where('status', 'submitted')->count();
    }

    public function attemptsRemaining(): int
    {
        return max(0, $this->course->max_attempts - $this->attemptsUsed());
    }

    public function canAttemptExam(): bool
    {
        return ! $this->hasPassedTheory()
            && $this->attemptsRemaining() > 0
            && $this->hasCompletedAllLessons();
    }

    public function hasCompletedAllLessons(): bool
    {
        $published = $this->course->lessons()->where('is_published', true)->count();

        return $published > 0 && $this->lessonCompletions()->count() >= $published;
    }

    public function outstandingPracticalSessions(): int
    {
        return max(0, $this->course->practical_sessions_required - $this->practical_sessions_completed);
    }

    /**
     * Theory alone does not certify anyone; the poolside practicals must be
     * signed off before a certificate is issued.
     */
    public function isReadyForCertificate(): bool
    {
        return $this->hasPassedTheory() && $this->outstandingPracticalSessions() === 0;
    }

    public function progressPercent(): int
    {
        $lessons = $this->course->lessons()->where('is_published', true)->count();

        if ($lessons === 0) {
            return 0;
        }

        return (int) round(($this->lessonCompletions()->count() / $lessons) * 100);
    }
}

<?php

namespace App\Models;

use App\Enums\AccreditationStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use Carbon\CarbonImmutable;
use Database\Factories\OfficialAccreditationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $technical_official_id
 * @property OfficialCategory $category
 * @property OfficialGrade $grade
 * @property AccreditationStatus $status
 * @property int $learner_sessions_completed
 * @property CarbonImmutable|null $clinic_attended_on
 * @property CarbonImmutable|null $exam_passed_on
 * @property CarbonImmutable|null $attained_on
 * @property CarbonImmutable|null $expires_on
 * @property int|null $ratified_by
 * @property CarbonImmutable|null $ratified_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class OfficialAccreditation extends Model
{
    /** @use HasFactory<OfficialAccreditationFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'category' => OfficialCategory::class,
            'grade' => OfficialGrade::class,
            'status' => AccreditationStatus::class,
            'clinic_attended_on' => 'date',
            'exam_passed_on' => 'date',
            'attained_on' => 'date',
            'expires_on' => 'date',
            'ratified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<TechnicalOfficial, $this> */
    public function technicalOfficial(): BelongsTo
    {
        return $this->belongsTo(TechnicalOfficial::class);
    }

    /** @return BelongsTo<User, $this> */
    public function ratifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratified_by');
    }

    public function hasLapsed(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }

    /**
     * Learner sessions still outstanding before the exam may be attempted.
     */
    public function outstandingLearnerSessions(): int
    {
        return max(0, $this->category->learnerSessionsRequired() - $this->learner_sessions_completed);
    }

    public function isReadyForExam(): bool
    {
        return $this->outstandingLearnerSessions() === 0
            && $this->clinic_attended_on !== null;
    }
}

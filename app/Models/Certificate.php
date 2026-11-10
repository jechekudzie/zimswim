<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A digital certificate. The verification code is printed as a QR code so
 * anyone on a pool deck can confirm the level and expiry.
 *
 * @property int $id
 * @property string $verification_code
 * @property int $user_id
 * @property int|null $course_id
 * @property int|null $course_enrolment_id
 * @property int|null $technical_official_id
 * @property int|null $official_accreditation_id
 * @property string $holder_name
 * @property string $title
 * @property string|null $category
 * @property string|null $grade
 * @property CarbonImmutable $issued_on
 * @property CarbonImmutable|null $expires_on
 * @property string|null $issued_by
 * @property string $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_on' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'verification_code';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<TechnicalOfficial, $this> */
    public function technicalOfficial(): BelongsTo
    {
        return $this->belongsTo(TechnicalOfficial::class);
    }

    public function hasExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === 'valid' && ! $this->hasExpired();
    }

    /** @param Builder<self> $query */
    public function scopeValid(Builder $query): void
    {
        $query->where('status', 'valid');
    }
}

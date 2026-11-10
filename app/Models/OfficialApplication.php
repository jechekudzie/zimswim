<?php

namespace App\Models;

use App\Enums\OfficialApplicationStatus;
use App\Enums\OfficialCategory;
use Carbon\CarbonImmutable;
use Database\Factories\OfficialApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Somebody asking to be let onto the officials pathway.
 *
 * @property int $id
 * @property string $reference
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property CarbonImmutable $date_of_birth
 * @property OfficialCategory $category
 * @property string|null $motivation
 * @property int|null $organization_id
 * @property int|null $province_organization_id
 * @property string|null $club_name_given
 * @property string|null $membership_number
 * @property OfficialApplicationStatus $status
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $review_notes
 * @property int|null $user_id
 * @property int|null $technical_official_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class OfficialApplication extends Model
{
    /** @use HasFactory<OfficialApplicationFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'reviewed_at' => 'datetime',
            'category' => OfficialCategory::class,
            'status' => OfficialApplicationStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /** @return BelongsTo<Organization, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'province_organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Their age on the day they applied, which is the age the category's
     * minimum is checked against.
     */
    public function ageAtApplication(): int
    {
        return (int) $this->date_of_birth->diffInYears($this->created_at ?? now());
    }

    /**
     * The club they named, whether or not it is on the register.
     */
    public function clubLabel(): string
    {
        return $this->club->name ?? $this->club_name_given ?? 'No club yet';
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [
            OfficialApplicationStatus::Submitted,
            OfficialApplicationStatus::UnderReview,
        ]);
    }

    /**
     * A reference the applicant can quote on the phone.
     *
     * Year plus a short random tail rather than a running number: a sequence
     * would tell an outsider how many people have applied this year, and two
     * people submitting at once would race for the same value.
     */
    public static function newReference(): string
    {
        do {
            $reference = 'OFF-'.now()->format('Y').'-'.Str::upper(Str::random(5));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }
}

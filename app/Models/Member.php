<?php

namespace App\Models;

use App\Enums\MemberStatus;
use Carbon\CarbonImmutable;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A registered swimmer. Age group is never stored: it is derived from the date
 * of birth against the age-up date of the season or gala in question.
 *
 * @property int $id
 * @property string $membership_number
 * @property int $organization_id
 * @property int|null $province_organization_id
 * @property int|null $user_id
 * @property int|null $guardian_user_id
 * @property string $first_name
 * @property string $last_name
 * @property CarbonImmutable $date_of_birth
 * @property string $gender
 * @property MemberStatus $status
 * @property string|null $school
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $medical_notes
 * @property CarbonImmutable|null $consent_given_at
 * @property string|null $consent_given_by
 * @property bool $photo_consent
 * @property string|null $photo_path
 * @property CarbonImmutable|null $joined_on
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => MemberStatus::class,
            'date_of_birth' => 'date',
            'joined_on' => 'date',
            'consent_given_at' => 'datetime',
            'photo_consent' => 'boolean',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function sportingProvince(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'province_organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    /** @return HasMany<MemberRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(MemberRegistration::class);
    }

    /** @return HasMany<GalaEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(GalaEntry::class);
    }

    /** @return HasMany<Result, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Age as at the given date, which is how every gala decides an age group.
     */
    public function ageAt(Carbon|string $date): int
    {
        return (int) $this->date_of_birth->diffInYears(Carbon::parse($date));
    }

    /**
     * Competition age for a season: age as at the season's age-up date.
     */
    public function competitionAge(Season $season): int
    {
        return $this->ageAt($season->age_up_date);
    }

    public function isMinor(): bool
    {
        return $this->date_of_birth->age < 18;
    }

    /**
     * A minor may not be registered without a recorded guardian consent, as
     * required by the Cyber and Data Protection Act.
     */
    public function hasRequiredConsent(): bool
    {
        return ! $this->isMinor() || $this->consent_given_at !== null;
    }

    public function isRegisteredFor(Season $season): bool
    {
        return $this->registrations()
            ->where('season_id', $season->getKey())
            ->where('status', 'active')
            ->exists();
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', MemberStatus::Active);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<int, int>  $organizationIds
     */
    public function scopeForOrganizations(Builder $query, array $organizationIds): void
    {
        $query->whereIn('organization_id', $organizationIds);
    }

    /** @param Builder<self> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('first_name', 'ilike', $like)
                ->orWhere('last_name', 'ilike', $like)
                ->orWhere('membership_number', 'ilike', $like);
        });
    }
}

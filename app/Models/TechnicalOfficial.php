<?php

namespace App\Models;

use App\Enums\AccreditationStatus;
use App\Enums\GalaLevel;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use Carbon\CarbonImmutable;
use Database\Factories\TechnicalOfficialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A registered swimming technical official. Officials must be members of a
 * registered club and the province in their place of domicile.
 *
 * @property int $id
 * @property string $registration_number
 * @property int|null $user_id
 * @property int $organization_id
 * @property int|null $province_organization_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property CarbonImmutable|null $date_of_birth
 * @property string $status
 * @property CarbonImmutable|null $police_clearance_on
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class TechnicalOfficial extends Model
{
    /** @use HasFactory<TechnicalOfficialFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'police_clearance_on' => 'date',
        ];
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OfficialAccreditation, $this> */
    public function accreditations(): HasMany
    {
        return $this->hasMany(OfficialAccreditation::class);
    }

    /** @return HasMany<OfficialSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(OfficialSession::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * The highest active grade held in a category, if any.
     */
    public function gradeFor(OfficialCategory $category): ?OfficialGrade
    {
        return $this->accreditations
            ->where('category', $category)
            ->where('status', AccreditationStatus::Active)
            ->sortByDesc(fn (OfficialAccreditation $accreditation): int => $accreditation->grade->rank())
            ->first()?->grade;
    }

    public function isAccreditedFor(OfficialCategory $category, GalaLevel $level): bool
    {
        $held = $this->gradeFor($category);

        return $held !== null && $held->satisfies(OfficialGrade::requiredFor($level));
    }

    /**
     * Categories the official may still begin, given the prerequisites in the
     * grading standards and the one-new-category-per-season limit.
     *
     * @return array<int, OfficialCategory>
     */
    public function availableCategories(): array
    {
        return array_values(array_filter(
            OfficialCategory::cases(),
            function (OfficialCategory $category): bool {
                if ($this->gradeFor($category) !== null) {
                    return false;
                }

                foreach ($category->prerequisites() as $prerequisite) {
                    if ($this->gradeFor($prerequisite) === null) {
                        return false;
                    }
                }

                return true;
            },
        ));
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
                ->orWhere('registration_number', 'ilike', $like);
        });
    }
}

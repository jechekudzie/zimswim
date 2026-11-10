<?php

namespace App\Models;

use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use Carbon\CarbonImmutable;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * An Officials Academy course: online theory, a quiz per lesson, a timed exam
 * drawn from a question bank, and poolside practicals signed off at galas.
 *
 * @property int $id
 * @property int|null $organization_id
 * @property string $title
 * @property string $slug
 * @property OfficialCategory|null $official_category
 * @property OfficialGrade|null $awards_grade
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $cover_image_path
 * @property int $pass_mark
 * @property int $max_attempts
 * @property int $exam_question_count
 * @property int $exam_minutes
 * @property int $practical_sessions_required
 * @property int|null $minimum_age
 * @property string $fee
 * @property bool $free_for_registered_parents
 * @property bool $is_published
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'official_category' => OfficialCategory::class,
            'awards_grade' => OfficialGrade::class,
            'fee' => 'decimal:2',
            'free_for_registered_parents' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<CourseLesson, $this> */
    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class)->orderBy('position');
    }

    /** @return HasMany<CourseQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(CourseQuestion::class);
    }

    /** @return HasMany<CourseEnrolment, $this> */
    public function enrolments(): HasMany
    {
        return $this->hasMany(CourseEnrolment::class);
    }

    /**
     * The bank the exam draws from. No two exams are identical, so the bank
     * must be larger than the number of questions asked.
     *
     * @return HasMany<CourseQuestion, $this>
     */
    public function examBank(): HasMany
    {
        return $this->questions()->where('in_exam_bank', true)->where('is_active', true);
    }

    public function hasEnoughQuestions(): bool
    {
        return $this->examBank()->count() >= $this->exam_question_count;
    }

    public function isFreeFor(?User $user): bool
    {
        return (float) $this->fee === 0.0 || $this->free_for_registered_parents;
    }

    /** @param Builder<self> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}

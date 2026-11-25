<?php

namespace App\Models;

use App\Casts\AsRichText;
use App\Enums\OfficialCategory;
use App\Enums\TrainingKind;
use Carbon\CarbonImmutable;
use Database\Factories\OfficialTrainingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * A clinic, exam sitting or refresher on the officials' calendar.
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $venue_id
 * @property string $title
 * @property string $slug
 * @property TrainingKind $kind
 * @property OfficialCategory|null $category
 * @property string|null $description
 * @property CarbonImmutable $held_on
 * @property CarbonImmutable|null $ends_on
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property string|null $location
 * @property bool $is_online
 * @property int|null $capacity
 * @property CarbonImmutable|null $registration_closes_at
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property bool $is_published
 * @property-read Organization $host
 * @property-read Venue|null $venue A session may be somewhere the venue register has no row for.
 * @property-read int $registrations_count Loaded with withCount
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class OfficialTraining extends Model
{
    /** @use HasFactory<OfficialTrainingFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'kind' => TrainingKind::class,
            'category' => OfficialCategory::class,
            'description' => AsRichText::class,
            'held_on' => 'date',
            'ends_on' => 'date',
            'is_online' => 'boolean',
            'is_published' => 'boolean',
            'registration_closes_at' => 'datetime',
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
    public function host(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /** @return BelongsTo<Venue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** @return HasMany<OfficialTrainingRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(OfficialTrainingRegistration::class);
    }

    /**
     * The last day this runs, which is the day it starts unless it spans more.
     */
    public function finishesOn(): CarbonImmutable
    {
        return $this->ends_on ?? $this->held_on;
    }

    public function hasFinished(): bool
    {
        return $this->finishesOn()->isBefore(today());
    }

    /**
     * Whether somebody can still put their name down.
     *
     * Three things close a session: the date passing, a closing date the host
     * set, and a cap being reached. Most sessions have none of the last two,
     * which is the intended default — a clinic exists to be attended.
     */
    public function isOpenForRegistration(): bool
    {
        if ($this->hasFinished()) {
            return false;
        }

        if ($this->registration_closes_at !== null && $this->registration_closes_at->isPast()) {
            return false;
        }

        return ! $this->isFull();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null
            && $this->registrations()->count() >= $this->capacity;
    }

    public function placesLeft(): ?int
    {
        return $this->capacity === null
            ? null
            : max(0, $this->capacity - $this->registrations()->count());
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Sessions that have not happened yet, soonest first.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query
            ->where(fn (Builder $inner) => $inner
                ->whereDate('held_on', '>=', today())
                ->orWhereDate('ends_on', '>=', today()))
            ->orderBy('held_on');
    }
}

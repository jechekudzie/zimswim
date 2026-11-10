<?php

namespace App\Models;

use App\Enums\Course;
use App\Enums\GalaLevel;
use App\Enums\GalaStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\GalaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * A gala, from request through sanction to ratified results.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $season_id
 * @property int $host_organization_id
 * @property int|null $sanctioning_organization_id
 * @property int|null $venue_id
 * @property-read Venue|null $venue
 * @property-read Organization|null $sanctioningBody
 * @property-read User|null $sanctioner
 * @property GalaLevel $level
 * @property Course $course
 * @property GalaStatus $status
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property CarbonImmutable|null $age_up_date
 * @property CarbonImmutable|null $entries_open_at
 * @property CarbonImmutable|null $entries_close_at
 * @property CarbonImmutable|null $entry_corrections_deadline
 * @property string $entry_fee
 * @property string $late_entry_fee
 * @property string $late_entry_event_fee
 * @property string $no_show_fine
 * @property string $appeal_deposit
 * @property string|null $eligible_ages
 * @property string|null $description
 * @property string|null $entry_instructions
 * @property string|null $entries_email
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property bool $qualifying_times_apply
 * @property bool $is_published
 * @property int|null $requested_by
 * @property int|null $sanctioned_by
 * @property CarbonImmutable|null $sanctioned_at
 * @property string|null $sanction_notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Gala extends Model
{
    /** @use HasFactory<GalaFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'level' => GalaLevel::class,
            'status' => GalaStatus::class,
            'course' => Course::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'age_up_date' => 'date',
            'entry_corrections_deadline' => 'date',
            'entries_open_at' => 'datetime',
            'entries_close_at' => 'datetime',
            'sanctioned_at' => 'datetime',
            'entry_fee' => 'decimal:2',
            'late_entry_fee' => 'decimal:2',
            'late_entry_event_fee' => 'decimal:2',
            'no_show_fine' => 'decimal:2',
            'appeal_deposit' => 'decimal:2',
            'qualifying_times_apply' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function host(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'host_organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function sanctioningBody(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'sanctioning_organization_id');
    }

    /** @return BelongsTo<Venue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** @return HasMany<GalaSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(GalaSession::class)->orderBy('position');
    }

    /** @return HasMany<GalaEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(GalaEvent::class)->orderBy('event_number');
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

    /** @return HasMany<RelayTeam, $this> */
    public function relayTeams(): HasMany
    {
        return $this->hasMany(RelayTeam::class);
    }

    /** @return HasMany<GalaOfficial, $this> */
    public function officials(): HasMany
    {
        return $this->hasMany(GalaOfficial::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sanctioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sanctioned_by');
    }

    /**
     * The date a swimmer's age is taken at, falling back to the season rule.
     */
    public function effectiveAgeUpDate(): CarbonInterface
    {
        return $this->age_up_date ?? $this->season->age_up_date;
    }

    /**
     * The booked venue, or null while none is booked.
     *
     * A gala may be requested before a pool is secured, so everything that
     * reads the venue goes through here rather than assuming one exists.
     */
    public function bookedVenue(): ?Venue
    {
        return $this->venue_id === null ? null : $this->venue;
    }

    /**
     * Lanes available for seeding, falling back to eight when no venue is
     * booked or the venue's lane count is unknown.
     */
    public function laneCount(): int
    {
        $venue = $this->bookedVenue();

        return $venue === null ? 8 : ($venue->lanes ?? 8);
    }

    /**
     * The booked venue's name, or null while none is booked.
     */
    public function venueName(): ?string
    {
        return $this->bookedVenue()?->name;
    }

    /**
     * The sanctioning body, or null while the gala is still a request.
     */
    public function sanctioningBodyName(): ?string
    {
        return $this->sanctioning_organization_id === null
            ? null
            : $this->sanctioningBody->name;
    }

    public function isAcceptingEntries(): bool
    {
        if (! $this->status->acceptsEntries()) {
            return false;
        }

        return $this->entries_close_at === null || $this->entries_close_at->isFuture();
    }

    public function hasSuitableVenue(): bool
    {
        return $this->venue !== null && $this->venue->canHost($this->level);
    }

    /**
     * Other sanctioned galas that overlap these dates, which the board is
     * warned about before sanctioning.
     *
     * @return Collection<int, self>
     */
    public function clashes(): Collection
    {
        return self::query()
            ->whereKeyNot($this->getKey())
            ->where('season_id', $this->season_id)
            ->whereIn('status', ['sanctioned', 'entries_open', 'entries_closed', 'in_progress'])
            ->where('starts_on', '<=', $this->ends_on)
            ->where('ends_on', '>=', $this->starts_on)
            ->get();
    }

    /** @param Builder<self> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param Builder<self> $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('ends_on', '>=', now()->toDateString())->orderBy('starts_on');
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<int, int>  $organizationIds
     */
    public function scopeForOrganizations(Builder $query, array $organizationIds): void
    {
        $query->where(function (Builder $query) use ($organizationIds): void {
            $query->whereIn('host_organization_id', $organizationIds)
                ->orWhereIn('sanctioning_organization_id', $organizationIds);
        });
    }
}

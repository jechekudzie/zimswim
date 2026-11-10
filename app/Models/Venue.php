<?php

namespace App\Models;

use App\Enums\GalaLevel;
use App\Enums\VenueGrade;
use Carbon\CarbonImmutable;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $province_organization_id
 * @property int|null $managed_by_organization_id
 * @property string $owner_type
 * @property string|null $owner_name
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int|null $pool_length
 * @property int|null $lanes
 * @property string|null $depth_shallow
 * @property string|null $depth_deep
 * @property bool $starting_blocks
 * @property bool $backstroke_ledges
 * @property bool $backstroke_flags
 * @property bool $electronic_timing
 * @property bool $scoreboard
 * @property bool $warmup_pool
 * @property int|null $seating_capacity
 * @property bool $changing_rooms
 * @property bool $parking
 * @property bool $power_backup
 * @property VenueGrade $grade
 * @property string|null $water_quality
 * @property CarbonImmutable|null $last_inspected_on
 * @property CarbonImmutable|null $season_opens_on
 * @property CarbonImmutable|null $season_closes_on
 * @property string|null $condition_notes
 * @property string|null $notes
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'grade' => VenueGrade::class,
            'starting_blocks' => 'boolean',
            'backstroke_ledges' => 'boolean',
            'backstroke_flags' => 'boolean',
            'electronic_timing' => 'boolean',
            'scoreboard' => 'boolean',
            'warmup_pool' => 'boolean',
            'changing_rooms' => 'boolean',
            'parking' => 'boolean',
            'power_backup' => 'boolean',
            'is_active' => 'boolean',
            'last_inspected_on' => 'date',
            'season_opens_on' => 'date',
            'season_closes_on' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'depth_shallow' => 'decimal:2',
            'depth_deep' => 'decimal:2',
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

    /** @return BelongsTo<Organization, $this> */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'province_organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'managed_by_organization_id');
    }

    /** @return HasMany<Gala, $this> */
    public function galas(): HasMany
    {
        return $this->hasMany(Gala::class);
    }

    /** @return HasMany<VenueBlackout, $this> */
    public function blackouts(): HasMany
    {
        return $this->hasMany(VenueBlackout::class);
    }

    public function canHost(GalaLevel $level): bool
    {
        return $this->grade->satisfies($level->minimumVenueGrade());
    }

    /**
     * True when nothing blocks the pool and no sanctioned gala already claims it.
     */
    public function isAvailableBetween(Carbon|string $from, Carbon|string $to, ?int $ignoreGalaId = null): bool
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        $blocked = $this->blackouts()
            ->where('starts_on', '<=', $to)
            ->where('ends_on', '>=', $from)
            ->exists();

        if ($blocked) {
            return false;
        }

        return ! $this->galas()
            ->whereIn('status', ['sanctioned', 'entries_open', 'entries_closed', 'in_progress'])
            ->when($ignoreGalaId, fn (Builder $query) => $query->whereKeyNot($ignoreGalaId))
            ->where('starts_on', '<=', $to)
            ->where('ends_on', '>=', $from)
            ->exists();
    }

    public function courseLabel(): string
    {
        return $this->pool_length !== null ? $this->pool_length.'m' : 'Unknown';
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeGraded(Builder $query, VenueGrade $grade): void
    {
        $query->whereIn('grade', array_map(
            fn (VenueGrade $candidate): string => $candidate->value,
            array_filter(VenueGrade::cases(), fn (VenueGrade $candidate): bool => $candidate->satisfies($grade)),
        ));
    }
}

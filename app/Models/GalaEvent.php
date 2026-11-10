<?php

namespace App\Models;

use App\Enums\Stroke;
use Carbon\CarbonImmutable;
use Database\Factories\GalaEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gala_id
 * @property int|null $gala_session_id
 * @property-read GalaSession|null $session
 * @property int $event_number
 * @property string $gender
 * @property string $age_group
 * @property int|null $min_age
 * @property int|null $max_age
 * @property Stroke $stroke
 * @property int $distance
 * @property string $round
 * @property bool $is_relay
 * @property int|null $relay_legs
 * @property int|null $qualifying_time_ms
 * @property int|null $cutoff_time_ms
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property int|null $parent_event_id
 * @property int|null $qualifiers
 * @property CarbonImmutable|null $seeded_at
 * @property CarbonImmutable|null $results_published_at
 * @property-read Gala $gala
 * @property-read GalaEvent|null $parent
 */
class GalaEvent extends Model
{
    /** @use HasFactory<GalaEventFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'stroke' => Stroke::class,
            'is_relay' => 'boolean',
            'seeded_at' => 'datetime',
            'results_published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Gala, $this> */
    public function gala(): BelongsTo
    {
        return $this->belongsTo(Gala::class);
    }

    /** @return BelongsTo<GalaSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(GalaSession::class, 'gala_session_id');
    }

    /**
     * The heats this event is swum from, when this event is a final.
     *
     * @return BelongsTo<GalaEvent, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(GalaEvent::class, 'parent_event_id');
    }

    /**
     * The finals swum from this event's heats.
     *
     * @return HasMany<GalaEvent, $this>
     */
    public function finals(): HasMany
    {
        return $this->hasMany(GalaEvent::class, 'parent_event_id')->orderBy('event_number');
    }

    /** @return HasMany<RelayTeam, $this> */
    public function relayTeams(): HasMany
    {
        return $this->hasMany(RelayTeam::class, 'gala_event_id');
    }

    /** @return HasMany<GalaEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(GalaEntry::class);
    }

    /** @return HasMany<Result, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class)->orderBy('place');
    }

    /**
     * The gender this event is open to, written the way a swimmer's record
     * writes it: 'female', 'male' or 'mixed'.
     *
     * The column holds the sport's own wording — "girls", "boys" — so
     * everything that compares an event against a swimmer comes through here
     * rather than matching on the raw string.
     */
    public function genderRestriction(): string
    {
        return match ($this->gender) {
            'girls', 'female' => 'female',
            'boys', 'male' => 'male',
            default => 'mixed',
        };
    }

    public function title(): string
    {
        return sprintf('%s %dm %s', ucfirst($this->gender), $this->distance, $this->stroke->label());
    }

    /**
     * Whether a swimmer of this date of birth falls inside the event's band,
     * measured at the gala's age-up date.
     */
    public function acceptsAge(Carbon|string $dateOfBirth, Carbon|string $ageUpDate): bool
    {
        // Whole years only: a swimmer who turns 12 during the year is 12.
        $age = (int) Carbon::parse($dateOfBirth)->diffInYears(Carbon::parse($ageUpDate));

        if ($this->min_age !== null && $age < $this->min_age) {
            return false;
        }

        return ! ($this->max_age !== null && $age > $this->max_age);
    }
}

<?php

namespace App\Models;

use App\Support\SwimTime;
use Carbon\CarbonImmutable;
use Database\Factories\RelayTeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $gala_id
 * @property int $gala_event_id
 * @property int $organization_id
 * @property string $name
 * @property string $letter
 * @property int|null $entry_time_ms
 * @property int|null $heat
 * @property int|null $lane
 * @property string $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RelayTeam extends Model
{
    /** @use HasFactory<RelayTeamFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /** @return BelongsTo<Gala, $this> */
    public function gala(): BelongsTo
    {
        return $this->belongsTo(Gala::class);
    }

    /** @return BelongsTo<GalaEvent, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(GalaEvent::class, 'gala_event_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /** @return HasMany<RelayTeamMember, $this> */
    public function swimmers(): HasMany
    {
        return $this->hasMany(RelayTeamMember::class)->orderBy('leg');
    }

    /**
     * How the team is named on a heat sheet or a results board. The letter
     * matters: a club may field two teams in one event.
     */
    public function displayName(): string
    {
        return trim($this->name.' '.$this->letter);
    }

    public function entryTime(): string
    {
        return $this->entry_time_ms === null ? 'NT' : SwimTime::format($this->entry_time_ms);
    }

    /**
     * A borrowed swimmer may win a medal but scores no points, so a team that
     * borrows anyone is not eligible for the points table.
     */
    public function scoresPoints(): bool
    {
        return ! $this->swimmers->contains('is_borrowed', true);
    }

    /**
     * Mixed relays must be made up of two boys and two girls.
     */
    public function hasValidMixedComposition(): bool
    {
        $genders = $this->swimmers->load('member')->pluck('member.gender');

        return $genders->filter(fn (?string $g): bool => $g === 'male')->count() === 2
            && $genders->filter(fn (?string $g): bool => $g === 'female')->count() === 2;
    }
}

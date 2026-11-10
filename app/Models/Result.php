<?php

namespace App\Models;

use App\Support\SwimTime;
use Carbon\CarbonImmutable;
use Database\Factories\ResultFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $gala_id
 * @property int $gala_event_id
 * @property int|null $gala_entry_id
 * @property-read GalaEntry|null $entry
 * @property int|null $relay_team_id
 * @property-read RelayTeam|null $relayTeam
 * @property-read Member|null $member
 * @property int|null $member_id
 * @property int $organization_id
 * @property int|null $time_ms
 * @property int|null $place
 * @property int|null $points
 * @property int|null $heat
 * @property int|null $lane
 * @property string $status
 * @property string|null $dq_code
 * @property string|null $dq_reason
 * @property bool $is_personal_best
 * @property bool $record_flagged
 * @property int|null $recorded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Result extends Model
{
    /** @use HasFactory<ResultFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_personal_best' => 'boolean',
            'record_flagged' => 'boolean',
        ];
    }

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

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<RelayTeam, $this> */
    public function relayTeam(): BelongsTo
    {
        return $this->belongsTo(RelayTeam::class);
    }

    /**
     * Who swam: the relay team's name for a relay, the swimmer's name
     * otherwise.
     */
    public function competitorName(): string
    {
        if ($this->relay_team_id !== null) {
            return $this->relayTeam === null
                ? 'Relay team'
                : $this->relayTeam->displayName();
        }

        return $this->member_id === null
            ? 'Unknown swimmer'
            : $this->member->fullName();
    }

    /** @return BelongsTo<Organization, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function formattedTime(): string
    {
        if ($this->status === 'dq') {
            return 'DQ';
        }

        return $this->time_ms === null ? '—' : SwimTime::format($this->time_ms);
    }

    public function isCountable(): bool
    {
        return $this->status === 'official' && $this->time_ms !== null;
    }

    /** @param Builder<self> $query */
    public function scopeOfficial(Builder $query): void
    {
        $query->where('status', 'official');
    }
}

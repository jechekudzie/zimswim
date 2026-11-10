<?php

namespace App\Models;

use App\Support\SwimTime;
use Carbon\CarbonImmutable;
use Database\Factories\GalaEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $gala_id
 * @property int $gala_event_id
 * @property int $member_id
 * @property int $organization_id
 * @property int|null $entry_time_ms
 * @property int|null $heat
 * @property int|null $lane
 * @property string $status
 * @property bool $is_late_entry
 * @property int|null $entered_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class GalaEntry extends Model
{
    /** @use HasFactory<GalaEntryFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_late_entry' => 'boolean',
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

    /** @return BelongsTo<Organization, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function entryTime(): string
    {
        return $this->entry_time_ms === null ? 'NT' : SwimTime::format($this->entry_time_ms);
    }
}

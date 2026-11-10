<?php

namespace App\Models;

use App\Enums\Course;
use App\Enums\RecordStatus;
use App\Enums\Stroke;
use App\Support\SwimTime;
use Carbon\CarbonImmutable;
use Database\Factories\SwimRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A national or provincial record. The holder's name is stored as text so the
 * historic lists in the handbook survive without a matching member row.
 *
 * @property int $id
 * @property string $scope
 * @property int|null $organization_id
 * @property Course $course
 * @property string $gender
 * @property string $age_group
 * @property Stroke $stroke
 * @property int $distance
 * @property bool $is_relay
 * @property int $time_ms
 * @property string $holder_name
 * @property int|null $member_id
 * @property int|null $club_organization_id
 * @property string|null $venue_name
 * @property CarbonImmutable|null $set_on
 * @property int|null $gala_id
 * @property int|null $result_id
 * @property RecordStatus $status
 * @property int|null $ratified_by
 * @property CarbonImmutable|null $ratified_at
 * @property string|null $ratifying_body
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class SwimRecord extends Model
{
    /** @use HasFactory<SwimRecordFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'course' => Course::class,
            'stroke' => Stroke::class,
            'status' => RecordStatus::class,
            'is_relay' => 'boolean',
            'set_on' => 'date',
            'ratified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'club_organization_id');
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<Gala, $this> */
    public function gala(): BelongsTo
    {
        return $this->belongsTo(Gala::class);
    }

    /** @return BelongsTo<User, $this> */
    public function ratifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratified_by');
    }

    public function formattedTime(): string
    {
        return SwimTime::format($this->time_ms);
    }

    public function eventTitle(): string
    {
        return sprintf('%dm %s', $this->distance, $this->stroke->label());
    }

    /** @param Builder<self> $query */
    public function scopeRatified(Builder $query): void
    {
        $query->where('status', RecordStatus::Ratified);
    }

    /** @param Builder<self> $query */
    public function scopeAwaitingRatification(Builder $query): void
    {
        $query->where('status', RecordStatus::Pending);
    }
}

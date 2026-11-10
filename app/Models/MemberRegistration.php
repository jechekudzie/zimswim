<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MemberRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $member_id
 * @property int $season_id
 * @property int $organization_id
 * @property string $status
 * @property string $capitation_amount
 * @property CarbonImmutable|null $capitation_paid_at
 * @property string $aquatics_amount
 * @property CarbonImmutable|null $aquatics_paid_at
 * @property CarbonImmutable|null $approved_at
 * @property int|null $approved_by
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class MemberRegistration extends Model
{
    /** @use HasFactory<MemberRegistrationFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'capitation_amount' => 'decimal:2',
            'aquatics_amount' => 'decimal:2',
            'capitation_paid_at' => 'datetime',
            'aquatics_paid_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * A swimmer may only be entered once both capitation fees are settled.
     */
    public function isFullyCapitated(): bool
    {
        return $this->capitation_paid_at !== null && $this->aquatics_paid_at !== null;
    }
}

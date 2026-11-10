<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CoachFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $organization_id
 * @property string|null $registration_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $qualification
 * @property string $status
 * @property CarbonImmutable|null $police_clearance_on
 * @property CarbonImmutable|null $safeguarding_checked_on
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Coach extends Model
{
    /** @use HasFactory<CoachFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'police_clearance_on' => 'date',
            'safeguarding_checked_on' => 'date',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function hasCurrentSafeguarding(): bool
    {
        return $this->police_clearance_on !== null
            && $this->police_clearance_on->greaterThan(now()->subYears(3));
    }
}

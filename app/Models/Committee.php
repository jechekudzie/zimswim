<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CommitteeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $slug
 * @property string $kind
 * @property string|null $description
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Committee extends Model
{
    /** @use HasFactory<CommitteeFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<CommitteeMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(CommitteeMember::class)->orderBy('position');
    }

    public function isExecutive(): bool
    {
        return $this->kind === 'executive';
    }
}

<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\GalaSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $gala_id
 * @property string $name
 * @property int $position
 * @property CarbonImmutable $held_on
 * @property string|null $warmup_at
 * @property string|null $starts_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class GalaSession extends Model
{
    /** @use HasFactory<GalaSessionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'warmup_at' => 'string',
            'starts_at' => 'string',
        ];
    }

    /** @return BelongsTo<Gala, $this> */
    public function gala(): BelongsTo
    {
        return $this->belongsTo(Gala::class);
    }

    /** @return HasMany<GalaEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(GalaEvent::class)->orderBy('event_number');
    }
}

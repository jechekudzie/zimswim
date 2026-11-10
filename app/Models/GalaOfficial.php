<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\GalaOfficialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $gala_id
 * @property int $technical_official_id
 * @property string $role
 * @property string $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class GalaOfficial extends Model
{
    /** @use HasFactory<GalaOfficialFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /** @return BelongsTo<Gala, $this> */
    public function gala(): BelongsTo
    {
        return $this->belongsTo(Gala::class);
    }

    /** @return BelongsTo<TechnicalOfficial, $this> */
    public function technicalOfficial(): BelongsTo
    {
        return $this->belongsTo(TechnicalOfficial::class);
    }
}

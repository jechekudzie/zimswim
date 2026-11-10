<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FeeScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $season_id
 * @property string $name
 * @property string $code
 * @property string|null $amount
 * @property string|null $unit
 * @property string|null $notes
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class FeeSchedule extends Model
{
    /** @use HasFactory<FeeScheduleFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function displayAmount(): string
    {
        return $this->amount === null ? 'TBA' : 'USD '.number_format((float) $this->amount, 2);
    }
}

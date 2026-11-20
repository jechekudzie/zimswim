<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One piece of wording or one picture the federation has changed.
 *
 * Rows exist only where something has been changed from what the page ships
 * with, so this table is normally much smaller than the catalogue.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string|null $image_path
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class ContentBlock extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

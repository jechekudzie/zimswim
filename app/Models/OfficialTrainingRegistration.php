<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OfficialTrainingRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Somebody who has put their name down for a clinic or an exam.
 *
 * @property int $id
 * @property int $official_training_id
 * @property int|null $user_id
 * @property int|null $technical_official_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $club_name
 * @property string|null $notes
 * @property CarbonImmutable $registered_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class OfficialTrainingRegistration extends Model
{
    /** @use HasFactory<OfficialTrainingRegistrationFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OfficialTraining, $this> */
    public function training(): BelongsTo
    {
        return $this->belongsTo(OfficialTraining::class, 'official_training_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<TechnicalOfficial, $this> */
    public function official(): BelongsTo
    {
        return $this->belongsTo(TechnicalOfficial::class, 'technical_official_id');
    }
}

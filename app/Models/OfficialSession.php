<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OfficialSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One signed-off duty worked at a gala session. This is the logbook the
 * grading standards count against, so it is never derived.
 *
 * @property int $id
 * @property int $technical_official_id
 * @property int|null $gala_id
 * @property int|null $gala_session_id
 * @property string $role
 * @property string|null $grade_worked
 * @property bool $as_learner
 * @property CarbonImmutable $worked_on
 * @property int|null $signed_off_by
 * @property CarbonImmutable|null $signed_off_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class OfficialSession extends Model
{
    /** @use HasFactory<OfficialSessionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'as_learner' => 'boolean',
            'worked_on' => 'date',
            'signed_off_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<TechnicalOfficial, $this> */
    public function technicalOfficial(): BelongsTo
    {
        return $this->belongsTo(TechnicalOfficial::class);
    }

    /** @return BelongsTo<Gala, $this> */
    public function gala(): BelongsTo
    {
        return $this->belongsTo(Gala::class);
    }

    /** @return BelongsTo<User, $this> */
    public function signatory(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_off_by');
    }

    public function isSignedOff(): bool
    {
        return $this->signed_off_at !== null;
    }
}

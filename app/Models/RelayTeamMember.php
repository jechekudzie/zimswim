<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RelayTeamMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $relay_team_id
 * @property int $member_id
 * @property int $leg
 * @property string|null $stroke
 * @property bool $is_borrowed
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RelayTeamMember extends Model
{
    /** @use HasFactory<RelayTeamMemberFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_borrowed' => 'boolean'];
    }

    /** @return BelongsTo<RelayTeam, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(RelayTeam::class, 'relay_team_id');
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

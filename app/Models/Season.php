<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property CarbonImmutable $age_up_date
 * @property bool $is_current
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory, HasSlug;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'age_up_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<Gala, $this> */
    public function galas(): HasMany
    {
        return $this->hasMany(Gala::class);
    }

    /** @return HasMany<MemberRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(MemberRegistration::class);
    }

    /** @return HasMany<FeeSchedule, $this> */
    public function fees(): HasMany
    {
        return $this->hasMany(FeeSchedule::class)->orderBy('position');
    }

    /** @param Builder<self> $query */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('is_current', true);
    }

    public static function current(): ?self
    {
        return self::query()->current()->first();
    }
}

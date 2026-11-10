<?php

namespace App\Models;

use App\Enums\AffiliationStatus;
use App\Enums\OrganizationType;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * An organisation in the Zimbabwe Swimming structure: the national body, a
 * province, or a club. The tree mirrors the handbook's lines of communication
 * and the id is the team key for roles and permissions.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $short_name
 * @property string $slug
 * @property string|null $code
 * @property OrganizationType $type
 * @property string|null $description
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $logo_path
 * @property string|null $address
 * @property string|null $city
 * @property string|null $latitude
 * @property string|null $longitude
 * @property AffiliationStatus $affiliation_status
 * @property CarbonImmutable|null $affiliated_on
 * @property CarbonImmutable|null $affiliation_expires_on
 * @property bool $is_active
 * @property array<string, mixed>|null $settings
 * @property-read int $swimmers_count Active swimmers, loaded with withCount
 * @property-read int $children_count Organisations directly beneath this one, loaded with withCount
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'affiliation_status' => AffiliationStatus::class,
            'affiliated_on' => 'date',
            'affiliation_expires_on' => 'date',
            'is_active' => 'boolean',
            'settings' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function clubs(): HasMany
    {
        return $this->children()->where('type', OrganizationType::Club);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['title', 'is_default'])
            ->withTimestamps();
    }

    /** @return HasMany<Member, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** @return HasMany<Coach, $this> */
    public function coaches(): HasMany
    {
        return $this->hasMany(Coach::class);
    }

    /** @return HasMany<TechnicalOfficial, $this> */
    public function technicalOfficials(): HasMany
    {
        return $this->hasMany(TechnicalOfficial::class);
    }

    /** @return HasMany<Gala, $this> */
    public function hostedGalas(): HasMany
    {
        return $this->hasMany(Gala::class, 'host_organization_id');
    }

    /** @return HasMany<Committee, $this> */
    public function committees(): HasMany
    {
        return $this->hasMany(Committee::class)->orderBy('position');
    }

    /** @return HasMany<Venue, $this> */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'province_organization_id');
    }

    /** @param Builder<self> $query */
    public function scopeOfType(Builder $query, OrganizationType $type): void
    {
        $query->where('type', $type);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isNational(): bool
    {
        return $this->type === OrganizationType::National;
    }

    public function isProvince(): bool
    {
        return $this->type === OrganizationType::Province;
    }

    public function isClub(): bool
    {
        return $this->type === OrganizationType::Club;
    }

    /**
     * This organisation and everything beneath it, which is the set of
     * organisations a user scoped here is allowed to reach.
     *
     * @return Collection<int, self>
     */
    public function descendantsAndSelf(): Collection
    {
        /** @var Collection<int, self> $collected */
        $collected = new Collection([$this]);
        $frontier = new Collection([$this->getKey()]);

        while ($frontier->isNotEmpty()) {
            $next = self::query()->whereIn('parent_id', $frontier)->get();

            if ($next->isEmpty()) {
                break;
            }

            $collected = $collected->concat($next);
            $frontier = $next->pluck('id');
        }

        return $collected;
    }

    /**
     * @return array<int, int>
     */
    public function descendantAndSelfIds(): array
    {
        return $this->descendantsAndSelf()->pluck('id')->all();
    }

    /**
     * This organisation and every organisation above it, nearest first.
     *
     * @return Collection<int, self>
     */
    public function ancestorsAndSelf(): Collection
    {
        /** @var Collection<int, self> $chain */
        $chain = new Collection([$this]);
        $current = $this;

        while ($current->parent_id !== null) {
            $parent = $current->parent;

            if ($parent === null) {
                break;
            }

            $chain->push($parent);
            $current = $parent;
        }

        return $chain;
    }

    /**
     * @return array<int, int>
     */
    public function ancestorAndSelfIds(): array
    {
        return $this->ancestorsAndSelf()->pluck('id')->all();
    }

    /**
     * Is this organisation in good standing for the season?
     *
     * An expiry date that has passed counts as lapsed whatever the stored
     * status says, so a province that was affiliated last season does not
     * carry that standing into this one on the strength of a stale row.
     */
    public function isAffiliated(): bool
    {
        if (! $this->affiliation_status->permitsCompetition()) {
            return false;
        }

        return $this->affiliation_expires_on === null
            || ! $this->affiliation_expires_on->isPast();
    }

    /**
     * The contact number in the form wa.me expects: digits only, carrying the
     * country code and no leading zero.
     *
     * Returns null when nothing is on record, which is what keeps the
     * WhatsApp button off the page rather than linking it nowhere.
     */
    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\\D/', '', (string) $this->phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        // A Zimbabwean number written locally as 0772 123 456 dials as
        // +263 772 123 456.
        if (str_starts_with($digits, '0')) {
            $digits = '263'.ltrim($digits, '0');
        }

        return strlen($digits) < 9 ? null : $digits;
    }

    /**
     * The province this organisation sits under, or itself when it is one.
     */
    public function province(): ?self
    {
        if ($this->isProvince()) {
            return $this;
        }

        return $this->parent?->isProvince() ? $this->parent : null;
    }
}

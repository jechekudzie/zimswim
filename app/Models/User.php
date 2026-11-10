<?php

namespace App\Models;

use App\Enums\OrganizationType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property int|null $current_organization_id
 * @property string $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $avatar_path
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization|null $currentOrganization
 */
#[Fillable([
    'name', 'first_name', 'last_name', 'email', 'password', 'phone',
    'date_of_birth', 'gender', 'avatar_path', 'is_active', 'current_organization_id',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'date_of_birth' => 'date',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    /** @return BelongsToMany<Organization, $this> */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['title', 'is_default'])
            ->withTimestamps();
    }

    /** @return HasOne<TechnicalOfficial, $this> */
    public function technicalOfficial(): HasOne
    {
        return $this->hasOne(TechnicalOfficial::class);
    }

    /** @return HasOne<Member, $this> */
    public function memberProfile(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    /**
     * Swimmers this user is the parent or guardian of.
     *
     * @return HasMany<Member, $this>
     */
    public function dependants(): HasMany
    {
        return $this->hasMany(Member::class, 'guardian_user_id');
    }

    /** @return HasMany<Certificate, $this> */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /** @return HasMany<CourseEnrolment, $this> */
    public function enrolments(): HasMany
    {
        return $this->hasMany(CourseEnrolment::class);
    }

    /**
     * Where a text message to this user goes. Returning null — no number, or
     * SMS switched off entirely — makes the SMS channel skip them, and email
     * carries the notice instead.
     */
    public function routeNotificationForSms(): ?string
    {
        if (! config('sms.enabled')) {
            return null;
        }

        return $this->phone;
    }

    public function displayName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : $this->name;
    }

    /**
     * The organisation the user is acting within for this request. Falls back to
     * their default membership so a session always has a team context.
     */
    public function activeOrganization(): ?Organization
    {
        if ($this->current_organization_id !== null) {
            return $this->currentOrganization;
        }

        return $this->organizations()
            ->orderByDesc('organization_user.is_default')
            ->first();
    }

    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->organizations()
            ->whereKey($organization->getKey())
            ->exists();
    }

    /**
     * Every organisation the user may act in: those they are attached to, plus
     * everything beneath them in the tree.
     *
     * @return Collection<int, Organization>
     */
    public function reachableOrganizations(): Collection
    {
        return $this->organizations
            ->flatMap(fn (Organization $organization): Collection => $organization->descendantsAndSelf())
            ->unique('id')
            ->sortBy([
                fn (Organization $a, Organization $b): int => $this->typeRank($a) <=> $this->typeRank($b),
                fn (Organization $a, Organization $b): int => strcmp($a->name, $b->name),
            ])
            ->values();
    }

    private function typeRank(Organization $organization): int
    {
        return match ($organization->type) {
            OrganizationType::National => 0,
            OrganizationType::Province => 1,
            OrganizationType::Club => 2,
        };
    }
}

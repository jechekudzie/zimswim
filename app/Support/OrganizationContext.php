<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Holds the organisation a request is acting within.
 *
 * Two different questions are answered here and they are deliberately kept
 * apart. What a user may *do* comes from the roles they hold at the current
 * organisation and at every organisation above it, so a board member keeps
 * board powers while looking at one province. What a user may *see* comes from
 * the current organisation and everything below it, so that same board member
 * looking at Harare sees only Harare's clubs.
 */
class OrganizationContext
{
    private ?Organization $current = null;

    /** @var array<int, array<int, string>> */
    private array $permissionCache = [];

    public function set(?Organization $organization): void
    {
        $this->current = $organization;
        $this->permissionCache = [];
    }

    public function current(): ?Organization
    {
        return $this->current;
    }

    public function currentId(): ?int
    {
        return $this->current?->getKey();
    }

    public function has(): bool
    {
        return $this->current !== null;
    }

    /**
     * Organisation ids whose rows the current context may read: this
     * organisation and everything beneath it.
     *
     * @return array<int, int>
     */
    public function scopeIds(): array
    {
        return $this->current?->descendantAndSelfIds() ?? [];
    }

    /**
     * Permissions the user holds in this context, gathered from the current
     * organisation and its ancestors.
     *
     * @return array<int, string>
     */
    public function permissionsFor(User $user): array
    {
        if ($this->current === null) {
            return [];
        }

        $cacheKey = $user->getKey();

        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        $organizationIds = $this->current->ancestorAndSelfIds();

        $permissions = DB::table('model_has_roles')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->getKey())
            ->whereIn('model_has_roles.organization_id', $organizationIds)
            ->distinct()
            ->pluck('permissions.name')
            ->all();

        return $this->permissionCache[$cacheKey] = $permissions;
    }

    public function permits(User $user, string $permission): bool
    {
        return in_array($permission, $this->permissionsFor($user), true);
    }

    /**
     * Role names the user holds at or above the current organisation.
     *
     * @return array<int, string>
     */
    public function rolesFor(User $user): array
    {
        if ($this->current === null) {
            return [];
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->getKey())
            ->whereIn('model_has_roles.organization_id', $this->current->ancestorAndSelfIds())
            ->distinct()
            ->pluck('roles.name')
            ->all();
    }

    /**
     * Organisations the user may switch into.
     *
     * @return Collection<int, Organization>
     */
    public function switchableFor(User $user): Collection
    {
        return $user->reachableOrganizations();
    }
}

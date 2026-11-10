<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Works out who should be told about something.
 *
 * Permissions deliberately inherit upwards — a national administrator can act
 * on any club — but a notice must not. If every club's entry deadline emailed
 * the national office, the national office would stop reading them. So a
 * notice goes to people who hold the relevant role *at* the organisation
 * concerned, and only climbs to the parent when nobody there can act on it.
 */
final class NoticeRecipients
{
    /**
     * Active users who hold a role granting this permission at the given
     * organisation.
     *
     * @return Collection<int, User>
     */
    public static function at(Organization $organization, string $permission): Collection
    {
        return self::query([$organization->getKey()], $permission);
    }

    /**
     * The same, but falling back up the tree until somebody is found. Used for
     * notices that must reach a person — a club with no administrator yet
     * should not mean a silent deadline.
     *
     * @return Collection<int, User>
     */
    public static function atOrAbove(Organization $organization, string $permission): Collection
    {
        // ancestorsAndSelf() is already nearest first, so the organisation's own
        // people are asked before the notice climbs to its parent.
        foreach ($organization->ancestorsAndSelf() as $candidate) {
            $users = self::query([$candidate->getKey()], $permission);

            if ($users->isNotEmpty()) {
                return $users;
            }
        }

        return new Collection;
    }

    /**
     * @param  array<int, int>  $organizationIds
     * @return Collection<int, User>
     */
    private static function query(array $organizationIds, string $permission): Collection
    {
        $userIds = DB::table('model_has_roles')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->whereIn('model_has_roles.organization_id', $organizationIds)
            ->where('permissions.name', $permission)
            ->distinct()
            ->pluck('model_has_roles.model_id');

        return User::query()
            ->whereKey($userIds)
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();
    }
}

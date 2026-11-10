<?php

namespace App\Policies;

use App\Models\GalaEntry;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

class GalaEntryPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_ENTRIES);
    }

    public function view(User $user, GalaEntry $entry): bool
    {
        return $user->can(Permissions::VIEW_ENTRIES) && $this->inScope($entry->organization_id);
    }

    public function manage(User $user): bool
    {
        return $user->can(Permissions::MANAGE_ENTRIES);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_ENTRIES);
    }

    public function update(User $user, GalaEntry $entry): bool
    {
        return $user->can(Permissions::MANAGE_ENTRIES) && $this->inScope($entry->organization_id);
    }

    /**
     * Only the club that lodged an entry may withdraw it, and only before the
     * gala has been swum.
     */
    public function delete(User $user, GalaEntry $entry): bool
    {
        return $this->update($user, $entry)
            && ! in_array($entry->status, ['confirmed', 'no_show'], true);
    }
}

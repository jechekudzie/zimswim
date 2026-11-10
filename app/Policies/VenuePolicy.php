<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

class VenuePolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_VENUES);
    }

    public function view(User $user, Venue $venue): bool
    {
        return $user->can(Permissions::VIEW_VENUES);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_VENUES);
    }

    public function update(User $user, Venue $venue): bool
    {
        if (! $user->can(Permissions::MANAGE_VENUES)) {
            return false;
        }

        // A venue with no province yet is national housekeeping.
        return $venue->province_organization_id === null
            || $this->inScope($venue->province_organization_id);
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $this->update($user, $venue);
    }
}

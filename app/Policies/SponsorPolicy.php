<?php

namespace App\Policies;

use App\Models\Sponsor;
use App\Models\User;
use App\Support\Permissions;

/**
 * Who may change the sponsor board.
 *
 * The same permission as the rest of the website, and national only: a
 * sponsor named on the front page is the federation's own arrangement, not a
 * province's.
 */
class SponsorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function view(User $user, Sponsor $sponsor): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function update(User $user, Sponsor $sponsor): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function delete(User $user, Sponsor $sponsor): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_USERS);
    }

    public function view(User $user, User $subject): bool
    {
        return $user->can(Permissions::VIEW_USERS) || $user->is($subject);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_USERS);
    }

    public function update(User $user, User $subject): bool
    {
        return $user->can(Permissions::MANAGE_USERS);
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->can(Permissions::MANAGE_USERS) && ! $user->is($subject);
    }

    public function manageRoles(User $user): bool
    {
        return $user->can(Permissions::MANAGE_ROLES);
    }
}

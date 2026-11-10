<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Support\Permissions;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function view(User $user, Post $post): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }
}

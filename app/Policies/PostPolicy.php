<?php

namespace App\Policies;

use App\Enums\OrganizationType;
use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

/**
 * Who may write for the site.
 *
 * The same permission that governs the website's wording, because an article
 * on the news page is the federation speaking in the same voice. Provinces
 * hold it too, so an article belongs to whoever wrote it: a province may
 * write and edit its own, and only the national office may touch the ones
 * published in the federation's own name.
 */
class PostPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function view(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function update(User $user, Post $post): bool
    {
        if (! $user->can(Permissions::MANAGE_CONTENT)) {
            return false;
        }

        /*
         * Every article written here is stamped with the organisation it was
         * written in, so scope answers it. An older article carrying none is
         * the federation's own, and only somebody acting nationally may
         * change that.
         */
        return $post->organization_id === null
            ? $this->context()->current()?->type === OrganizationType::National
            : $this->inScope($post->organization_id);
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }
}

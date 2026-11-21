<?php

namespace App\Policies;

use App\Models\Committee;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

/**
 * Who may change the list of office bearers.
 *
 * A committee belongs to the body that elected it, so each body keeps its
 * own: a province may name its executive, and only the national office may
 * change the federation's.
 */
class CommitteePolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function view(User $user, Committee $committee): bool
    {
        return $this->update($user, $committee);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function update(User $user, Committee $committee): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT)
            && $this->inScope($committee->organization_id);
    }

    public function delete(User $user, Committee $committee): bool
    {
        return $this->update($user, $committee);
    }
}

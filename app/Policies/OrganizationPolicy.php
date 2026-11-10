<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

class OrganizationPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_ORGANIZATIONS);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->can(Permissions::VIEW_ORGANIZATIONS)
            && $this->inScope($organization->getKey());
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_ORGANIZATIONS);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->can(Permissions::MANAGE_ORGANIZATIONS)
            && $this->inScope($organization->getKey());
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->can(Permissions::MANAGE_ORGANIZATIONS)
            && ! $organization->isNational()
            && $this->inScope($organization->getKey());
    }

    public function approveAffiliation(User $user, Organization $organization): bool
    {
        return $user->can(Permissions::APPROVE_AFFILIATIONS);
    }
}

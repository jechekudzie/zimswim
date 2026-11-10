<?php

namespace App\Policies;

use App\Models\Gala;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

class GalaPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_GALAS);
    }

    public function view(User $user, Gala $gala): bool
    {
        return $user->can(Permissions::VIEW_GALAS);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_GALAS);
    }

    public function update(User $user, Gala $gala): bool
    {
        if (! $user->can(Permissions::MANAGE_GALAS)) {
            return false;
        }

        if ($gala->status->isSanctioned() && ! $user->can(Permissions::SANCTION_GALAS)) {
            return false;
        }

        return $this->inScope($gala->host_organization_id);
    }

    public function delete(User $user, Gala $gala): bool
    {
        return $user->can(Permissions::MANAGE_GALAS)
            && ! $gala->status->isSanctioned()
            && $this->inScope($gala->host_organization_id);
    }

    /**
     * Sanctioning is a board decision and is never granted by hosting the gala.
     */
    public function sanction(User $user, Gala $gala): bool
    {
        return $user->can(Permissions::SANCTION_GALAS);
    }

    public function appointOfficials(User $user, Gala $gala): bool
    {
        return $user->can(Permissions::APPOINT_GALA_OFFICIALS);
    }

    public function recordResults(User $user, Gala $gala): bool
    {
        return $user->can(Permissions::RECORD_RESULTS);
    }
}

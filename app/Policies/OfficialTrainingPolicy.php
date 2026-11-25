<?php

namespace App\Policies;

use App\Models\OfficialTraining;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

/**
 * Scheduling a clinic is part of developing officials, so it is carried by
 * the officials permissions rather than a set of its own. A province runs its
 * own clinics; it does not edit another province's.
 */
class OfficialTrainingPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_OFFICIALS);
    }

    public function view(User $user, OfficialTraining $training): bool
    {
        return $user->can(Permissions::VIEW_OFFICIALS)
            && $this->inScope($training->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_OFFICIALS);
    }

    public function update(User $user, OfficialTraining $training): bool
    {
        return $user->can(Permissions::MANAGE_OFFICIALS)
            && $this->inScope($training->organization_id);
    }

    public function delete(User $user, OfficialTraining $training): bool
    {
        return $this->update($user, $training);
    }
}

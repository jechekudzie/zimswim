<?php

namespace App\Policies;

use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

class TechnicalOfficialPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_OFFICIALS);
    }

    public function view(User $user, TechnicalOfficial $official): bool
    {
        if ($official->user_id === $user->getKey()) {
            return true;
        }

        return $user->can(Permissions::VIEW_OFFICIALS) && $this->inScope($official->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_OFFICIALS);
    }

    public function update(User $user, TechnicalOfficial $official): bool
    {
        return $user->can(Permissions::MANAGE_OFFICIALS) && $this->inScope($official->organization_id);
    }

    public function delete(User $user, TechnicalOfficial $official): bool
    {
        return $this->update($user, $official);
    }

    public function ratify(User $user, TechnicalOfficial $official): bool
    {
        return $user->can(Permissions::RATIFY_OFFICIAL_GRADES);
    }

    public function signOffSessions(User $user): bool
    {
        return $user->can(Permissions::SIGN_OFF_OFFICIAL_SESSIONS);
    }
}

<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

class MemberPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_MEMBERS);
    }

    public function view(User $user, Member $member): bool
    {
        if ($this->isOwnFamily($user, $member)) {
            return true;
        }

        return $user->can(Permissions::VIEW_MEMBERS) && $this->inScope($member->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_MEMBERS);
    }

    public function update(User $user, Member $member): bool
    {
        return $user->can(Permissions::MANAGE_MEMBERS) && $this->inScope($member->organization_id);
    }

    public function delete(User $user, Member $member): bool
    {
        return $this->update($user, $member);
    }

    public function approve(User $user, Member $member): bool
    {
        return $user->can(Permissions::APPROVE_MEMBERS) && $this->inScope($member->organization_id);
    }

    public function transfer(User $user, Member $member): bool
    {
        return $user->can(Permissions::TRANSFER_MEMBERS) && $this->inScope($member->organization_id);
    }

    /**
     * A parent always reaches their own children, and a senior swimmer their
     * own profile, whatever organisation they are looking at.
     */
    private function isOwnFamily(User $user, Member $member): bool
    {
        return $member->guardian_user_id === $user->getKey()
            || $member->user_id === $user->getKey();
    }
}

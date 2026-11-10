<?php

namespace App\Policies;

use App\Enums\RecordStatus;
use App\Models\SwimRecord;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

class SwimRecordPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_RECORDS);
    }

    public function view(User $user, SwimRecord $record): bool
    {
        return $user->can(Permissions::VIEW_RECORDS);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::RATIFY_RECORDS);
    }

    public function update(User $user, SwimRecord $record): bool
    {
        return $user->can(Permissions::RATIFY_RECORDS);
    }

    public function delete(User $user, SwimRecord $record): bool
    {
        return $user->can(Permissions::RATIFY_RECORDS);
    }

    /**
     * A record changes only after the authorised body approves it.
     */
    public function ratify(User $user, SwimRecord $record): bool
    {
        return $user->can(Permissions::RATIFY_RECORDS)
            && $record->status === RecordStatus::Pending;
    }
}

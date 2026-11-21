<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ScopesToOrganization;
use App\Support\Permissions;

/**
 * Who may add to the document library.
 *
 * A document belongs to the body that issued it, so a province keeps its own
 * minutes and forms, and the handbook stays with the national office. A
 * results file is governed by its gala instead, and never reaches here.
 */
class DocumentPolicy
{
    use ScopesToOrganization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function view(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT)
            && $this->inScope($document->organization_id);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}

<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use App\Support\Permissions;

/**
 * Academy courses are published by the technical committee; anyone signed in
 * may enrol on a published one.
 */
class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        return $course->is_published || $this->manage($user);
    }

    public function enrol(User $user, Course $course): bool
    {
        return $course->is_published;
    }

    public function manage(User $user): bool
    {
        return $user->can(Permissions::MANAGE_OFFICIALS);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->manage($user);
    }

    /**
     * Issuing a certificate is a technical committee act, like ratifying a grade.
     */
    public function certify(User $user): bool
    {
        return $user->can(Permissions::RATIFY_OFFICIAL_GRADES);
    }
}

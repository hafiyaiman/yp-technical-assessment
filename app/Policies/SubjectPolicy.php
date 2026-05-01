<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['manage-subjects', 'view-assigned-classes']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage-subjects');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasPermission('manage-subjects');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasPermission('manage-subjects');
    }
}

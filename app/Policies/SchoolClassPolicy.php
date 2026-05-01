<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;

class SchoolClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-classes');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage-classes');
    }

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        return $user->hasPermission('manage-classes');
    }

    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        return $user->hasPermission('manage-classes');
    }
}

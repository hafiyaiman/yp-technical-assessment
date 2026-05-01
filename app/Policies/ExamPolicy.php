<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['manage-exams', 'take-exams']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage-exams');
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->hasPermission('manage-exams');
    }

    public function publish(User $user, Exam $exam): bool
    {
        return $user->hasPermission('manage-exams');
    }

    public function manageSubmissions(User $user, Exam $exam): bool
    {
        return $user->hasPermission('manage-exams');
    }

    public function take(User $user, Exam $exam): bool
    {
        return $user->hasPermission('take-exams') && $exam->canBeTakenBy($user);
    }
}

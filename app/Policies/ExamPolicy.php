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
        return $user->hasPermission('manage-exams')
            && $exam->teachingAssignment?->lecturer_id === $user->id;
    }

    public function publish(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }

    public function manageSubmissions(User $user, Exam $exam): bool
    {
        return $user->hasAnyPermission(['grade-exams', 'view-exam-results'])
            && $exam->teachingAssignment?->lecturer_id === $user->id;
    }

    public function take(User $user, Exam $exam): bool
    {
        return $user->hasPermission('take-exams') && $exam->canBeTakenBy($user);
    }
}

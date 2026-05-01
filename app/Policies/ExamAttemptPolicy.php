<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;

class ExamAttemptPolicy
{
    public function view(User $user, ExamAttempt $attempt): bool
    {
        if ($user->hasPermission('manage-exams')) {
            return true;
        }

        return $user->hasPermission('view-own-results') && $attempt->student_id === $user->id;
    }

    public function submit(User $user, ExamAttempt $attempt): bool
    {
        return $user->hasPermission('take-exams') && $attempt->student_id === $user->id;
    }

    public function grade(User $user, ExamAttempt $attempt): bool
    {
        return $user->hasPermission('manage-exams');
    }
}

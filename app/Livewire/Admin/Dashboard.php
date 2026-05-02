<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('system-admin'), 403);
    }

    public function stats(): array
    {
        $studentRoleId = Role::query()->where('slug', 'student')->value('id');
        $lecturerRoleId = Role::query()->where('slug', 'lecturer')->value('id');

        $students = User::query()->whereHas('roles', fn ($query) => $query->whereKey($studentRoleId))->count();
        $lecturers = User::query()->whereHas('roles', fn ($query) => $query->whereKey($lecturerRoleId))->count();

        return [
            'users' => User::query()->count(),
            'students' => $students,
            'lecturers' => $lecturers,
            'classes' => SchoolClass::query()->count(),
            'subjects' => Subject::query()->count(),
            'assignments' => TeachingAssignment::query()->count(),
            'exams' => Exam::query()->count(),
            'unassignedStudents' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereKey($studentRoleId))
                ->whereNull('school_class_id')
                ->count(),
            'unassignedLecturers' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereKey($lecturerRoleId))
                ->whereDoesntHave('teachingAssignments')
                ->count(),
            'classesWithoutSubjects' => SchoolClass::query()->whereDoesntHave('subjects')->count(),
            'subjectsWithoutClasses' => Subject::query()->whereDoesntHave('classes')->count(),
        ];
    }

    public function setupChecks(): array
    {
        $stats = $this->stats();

        return [
            [
                'title' => 'Students waiting for class',
                'count' => $stats['unassignedStudents'],
                'description' => 'Assign these students before they can see exams.',
                'href' => route('admin.users.index'),
                'tone' => $stats['unassignedStudents'] > 0 ? 'yellow' : 'green',
            ],
            [
                'title' => 'Lecturers without teaching',
                'count' => $stats['unassignedLecturers'],
                'description' => 'Lecturers need class-subject assignments before creating exams.',
                'href' => route('admin.users.index'),
                'tone' => $stats['unassignedLecturers'] > 0 ? 'yellow' : 'green',
            ],
            [
                'title' => 'Classes without subjects',
                'count' => $stats['classesWithoutSubjects'],
                'description' => 'Attach at least one subject to make a class usable.',
                'href' => route('admin.classes.index'),
                'tone' => $stats['classesWithoutSubjects'] > 0 ? 'red' : 'green',
            ],
            [
                'title' => 'Subjects not used',
                'count' => $stats['subjectsWithoutClasses'],
                'description' => 'Reusable subjects should be linked to classes.',
                'href' => route('admin.subjects.index'),
                'tone' => $stats['subjectsWithoutClasses'] > 0 ? 'yellow' : 'green',
            ],
        ];
    }

    public function recentAuditLogs()
    {
        return AuditLog::query()
            ->with('actor')
            ->latest()
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}

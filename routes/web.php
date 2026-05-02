<?php

use App\Livewire\Admin\AuditLogs\Index as AdminAuditLogsIndex;
use App\Livewire\Admin\Classes\Index as AdminClassesIndex;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Subjects\Index as AdminSubjectsIndex;
use App\Livewire\Admin\TeachingAssignments\Index as AdminTeachingAssignmentsIndex;
use App\Livewire\Admin\Users\Index as AdminUsersIndex;
use App\Livewire\Lecturer\Exams\Activity as LecturerExamActivity;
use App\Livewire\Lecturer\Exams\Builder as LecturerExamBuilder;
use App\Livewire\Lecturer\Exams\Index as LecturerExamsIndex;
use App\Livewire\Lecturer\Exams\Results as LecturerExamResults;
use App\Livewire\Lecturer\Exams\Show as LecturerExamShow;
use App\Livewire\Lecturer\Exams\Submissions as LecturerExamSubmissions;
use App\Livewire\Lecturer\Teaching\Index as LecturerTeachingIndex;
use App\Livewire\Lecturer\Teaching\Show as LecturerTeachingShow;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::get('dashboard', function () {
    $user = auth()->user();

    if ($user?->hasRole('system-admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user?->hasRole('lecturer')) {
        return redirect()->route('lecturer.dashboard');
    }

    if ($user?->hasRole('student')) {
        return redirect()->route('student.home');
    }

    return view('dashboard');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::middleware('role:system-admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('dashboard', AdminDashboard::class)->name('dashboard');
        Route::get('users', AdminUsersIndex::class)->name('users.index');
        Route::get('classes', AdminClassesIndex::class)->name('classes.index');
        Route::get('subjects', AdminSubjectsIndex::class)->name('subjects.index');
        Route::get('audit-logs', AdminAuditLogsIndex::class)->name('audit-logs.index');
        Route::get('teaching-assignments', AdminTeachingAssignmentsIndex::class)->name('teaching-assignments.index');
    });

    Route::view('lecturer/dashboard', 'dashboard')
        ->middleware('role:lecturer')
        ->name('lecturer.dashboard');

    Route::middleware('role:lecturer')->prefix('lecturer')->name('lecturer.')->group(function (): void {
        Route::get('my-classes', LecturerTeachingIndex::class)->name('teaching.index');
        Route::get('my-classes/{assignment}', LecturerTeachingShow::class)->name('teaching.show');
        Route::get('exams', LecturerExamsIndex::class)->name('exams.index');
        Route::get('exams/{exam}', LecturerExamShow::class)->name('exams.show');
        Route::get('teaching/{assignment}/exams/create', LecturerExamBuilder::class)->name('teaching.exams.create');
        Route::get('exams/{exam}/edit', LecturerExamBuilder::class)->name('exams.edit');
        Route::get('exams/{exam}/submissions', LecturerExamSubmissions::class)->name('exams.submissions');
        Route::get('exams/{exam}/results', LecturerExamResults::class)->name('exams.results');
        Route::get('exams/{exam}/activity', LecturerExamActivity::class)->name('exams.activity');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function (): void {
        Volt::route('home', 'student.home')->name('home');
        Volt::route('exams', 'student.exams.index')->name('exams.index');
        Volt::route('exams/{exam}', 'student.exams.show')->name('exams.show');
        Volt::route('attempts/{attempt}', 'student.attempts.show')->name('attempts.show');
        Volt::route('attempts/{attempt}/review', 'student.attempts.review')->name('attempts.review');
        Volt::route('attempts/{attempt}/submitted', 'student.attempts.submitted')->name('attempts.submitted');
        Volt::route('results', 'student.results.index')->name('results.index');
        Volt::route('results/{attempt}', 'student.results.show')->name('results.show');
    });
});

require __DIR__.'/auth.php';

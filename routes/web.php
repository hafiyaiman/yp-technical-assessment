<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::middleware('role:system-admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Volt::route('users', 'admin.users.index')->name('users.index');
        Volt::route('classes', 'admin.classes.index')->name('classes.index');
        Volt::route('subjects', 'admin.subjects.index')->name('subjects.index');
        Volt::route('teaching-assignments', 'admin.teaching-assignments.index')->name('teaching-assignments.index');
    });

    Route::view('lecturer/dashboard', 'dashboard')
        ->middleware('role:lecturer')
        ->name('lecturer.dashboard');

    Route::middleware('role:lecturer')->prefix('lecturer')->name('lecturer.')->group(function (): void {
        Volt::route('my-classes', 'lecturer.teaching.index')->name('teaching.index');
        Volt::route('exams', 'lecturer.exams.index')->name('exams.index');
        Volt::route('teaching/{assignment}/exams/create', 'lecturer.exams.builder')->name('teaching.exams.create');
        Volt::route('exams/{exam}/edit', 'lecturer.exams.builder')->name('exams.edit');
        Volt::route('exams/{exam}/submissions', 'lecturer.exams.submissions')->name('exams.submissions');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function (): void {
        Volt::route('dashboard', 'student.home')->name('dashboard');
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

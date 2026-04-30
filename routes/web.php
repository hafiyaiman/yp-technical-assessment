<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::view('lecturer/dashboard', 'dashboard')
        ->middleware('role:lecturer')
        ->name('lecturer.dashboard');

    Route::view('student/dashboard', 'dashboard')
        ->middleware('role:student')
        ->name('student.dashboard');
});

require __DIR__.'/auth.php';

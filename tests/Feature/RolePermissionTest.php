<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('seeded roles have expected permissions', function (): void {
    $admin = Role::query()->where('slug', 'system-admin')->firstOrFail();
    $lecturer = Role::query()->where('slug', 'lecturer')->firstOrFail();
    $student = Role::query()->where('slug', 'student')->firstOrFail();

    expect($admin->permissions->pluck('slug')->all())->toContain('manage-users', 'manage-classes', 'manage-teaching-assignments');
    expect($lecturer->permissions->pluck('slug')->all())->toContain('view-assigned-classes', 'manage-exams', 'grade-exams');
    expect($lecturer->permissions->pluck('slug')->all())->not->toContain('manage-subjects');
    expect($student->permissions->pluck('slug')->all())->toContain('take-exams', 'view-own-results');
});

test('users can be assigned roles and checked for permissions', function (): void {
    $user = User::factory()->create();

    $user->assignRole('lecturer');

    expect($user->fresh()->hasRole('lecturer'))->toBeTrue();
    expect($user->fresh()->hasPermission('manage-exams'))->toBeTrue();
    expect($user->fresh()->hasPermission('take-exams'))->toBeFalse();
});

test('role middleware allows only matching users', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $lecturer = User::factory()->lecturer()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($lecturer)->get(route('lecturer.dashboard'))->assertOk();
    $this->actingAs($lecturer)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($student)->get(route('lecturer.dashboard'))->assertForbidden();
});

test('new registrations receive the student role', function (): void {
    $this->get('/register')->assertOk();

    Livewire\Volt\Volt::test('pages.auth.register')
        ->set('name', 'New Student')
        ->set('email', 'new-student@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('dashboard', absolute: false));

    expect(User::query()->where('email', 'new-student@example.com')->firstOrFail()->hasRole('student'))->toBeTrue();
});

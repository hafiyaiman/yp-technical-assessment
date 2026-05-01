<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            ['name' => 'Manage users', 'slug' => 'manage-users'],
            ['name' => 'Manage lecturers', 'slug' => 'manage-lecturers'],
            ['name' => 'Manage students', 'slug' => 'manage-students'],
            ['name' => 'Manage classes', 'slug' => 'manage-classes'],
            ['name' => 'Manage subjects', 'slug' => 'manage-subjects'],
            ['name' => 'Manage teaching assignments', 'slug' => 'manage-teaching-assignments'],
            ['name' => 'Enroll students', 'slug' => 'enroll-students'],
            ['name' => 'View assigned classes', 'slug' => 'view-assigned-classes'],
            ['name' => 'Manage exams', 'slug' => 'manage-exams'],
            ['name' => 'Grade exams', 'slug' => 'grade-exams'],
            ['name' => 'View exam results', 'slug' => 'view-exam-results'],
            ['name' => 'Take exams', 'slug' => 'take-exams'],
            ['name' => 'View own results', 'slug' => 'view-own-results'],
        ])->mapWithKeys(fn (array $permission): array => [
            $permission['slug'] => Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission,
            ),
        ]);

        $admin = Role::query()->updateOrCreate(
            ['slug' => 'system-admin'],
            ['name' => 'System Admin', 'description' => 'Owns users, academic setup, enrollment, and teaching assignments.'],
        );

        $lecturer = Role::query()->updateOrCreate(
            ['slug' => 'lecturer'],
            ['name' => 'Lecturer', 'description' => 'Creates exams, grades submissions, and views assigned class results.'],
        );

        $student = Role::query()->updateOrCreate(
            ['slug' => 'student'],
            ['name' => 'Student', 'description' => 'Takes assigned exams and views own results.'],
        );

        $admin->permissions()->sync($permissions
            ->only([
                'manage-users',
                'manage-lecturers',
                'manage-students',
                'manage-classes',
                'manage-subjects',
                'manage-teaching-assignments',
                'enroll-students',
            ])
            ->pluck('id'));

        $lecturer->permissions()->sync($permissions
            ->only(['view-assigned-classes', 'manage-exams', 'grade-exams', 'view-exam-results'])
            ->pluck('id'));

        $student->permissions()->sync($permissions
            ->only(['take-exams', 'view-own-results'])
            ->pluck('id'));
    }
}

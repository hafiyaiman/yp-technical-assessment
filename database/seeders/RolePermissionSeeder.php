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
            ['name' => 'Manage students', 'slug' => 'manage-students'],
            ['name' => 'Manage classes', 'slug' => 'manage-classes'],
            ['name' => 'Manage subjects', 'slug' => 'manage-subjects'],
            ['name' => 'Manage exams', 'slug' => 'manage-exams'],
            ['name' => 'Take exams', 'slug' => 'take-exams'],
            ['name' => 'View own results', 'slug' => 'view-own-results'],
        ])->mapWithKeys(fn (array $permission): array => [
            $permission['slug'] => Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission,
            ),
        ]);

        $lecturer = Role::query()->updateOrCreate(
            ['slug' => 'lecturer'],
            ['name' => 'Lecturer', 'description' => 'Creates exams and manages academic setup.'],
        );

        $student = Role::query()->updateOrCreate(
            ['slug' => 'student'],
            ['name' => 'Student', 'description' => 'Takes assigned exams and views own results.'],
        );

        $lecturer->permissions()->sync($permissions
            ->only(['manage-students', 'manage-classes', 'manage-subjects', 'manage-exams'])
            ->pluck('id'));

        $student->permissions()->sync($permissions
            ->only(['take-exams', 'view-own-results'])
            ->pluck('id'));
    }
}

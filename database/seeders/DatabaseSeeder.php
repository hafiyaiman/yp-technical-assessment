<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        User::factory()->create([
            'name' => 'Lecturer User',
            'email' => 'lecturer@example.com',
        ])->assignRole('lecturer');

        User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@example.com',
        ])->assignRole('student');
    }
}

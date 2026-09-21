<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table with an admin, instructors, and students.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lms.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        $instructors = [
            ['name' => 'Ahmed Abdelrahman', 'email' => 'ahmed.abdelrahman@lms.com'],
            ['name' => 'Mohammed Al-Shami', 'email' => 'mohammed.alshami@lms.com'],
            ['name' => 'Sara Khaled', 'email' => 'sara.khaled@lms.com'],
            ['name' => 'Omar Al-Husseini', 'email' => 'omar.alhusseini@lms.com'],
            ['name' => 'Layla Mansour', 'email' => 'layla.mansour@lms.com'],
        ];

        foreach ($instructors as $instructor) {
            User::updateOrCreate(
                ['email' => $instructor['email']],
                [
                    'name' => $instructor['name'],
                    'password' => 'password',
                    'role' => 'instructor',
                    'email_verified_at' => now(),
                ],
            );
        }

        User::factory()
            ->count(20)
            ->create();
    }
}

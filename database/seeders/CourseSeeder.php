<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{

    public function run(): void
    {
        $titles = [
            'Laravel 11 Masterclass',
            'ASP.NET Core Web API',
            'React & Next.js Pro',
            'Vue.js Essentials',
            'Docker & DevOps for PHP',
            'Microservices Architecture',
            'Database Optimization & Indexing',
            'Redis & Caching Strategies',
            'Clean Code & Design Patterns',
            'Building Scalable APIs',
        ];

        $instructorIds = User::where('role', 'instructor')->pluck('id');

        if ($instructorIds->isEmpty()) {
            return;
        }

        foreach ($titles as $title) {
            Course::factory()->create([
                'title' => $title,
                'instructor_id' => $instructorIds->random(),
            ]);
        }
    }
}

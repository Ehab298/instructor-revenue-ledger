<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
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
            'TypeScript in Depth',
            'GraphQL Fundamentals',
            'Livewire & Alpine.js',
            'Message Queues with RabbitMQ',
            'Testing with Pest & PHPUnit',
        ];

        return [
            'title' => fake()->unique()->randomElement($titles),
        ];
    }
}

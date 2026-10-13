<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => 'Kuis '.fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'time_limit_minutes' => fake()->randomElement([null, 15, 20, 30]),
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDays(fake()->numberBetween(3, 14)),
            'max_attempts' => fake()->numberBetween(1, 3),
            'show_results' => fake()->randomElement(['immediately', 'after_deadline', 'never']),
            'pass_score' => 70,
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 7)),
            'created_by' => User::factory()->teacher(),
        ];
    }
}

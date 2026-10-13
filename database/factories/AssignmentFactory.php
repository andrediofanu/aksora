<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(4),
            'instructions' => fake()->paragraphs(2, true),
            'submission_type' => fake()->randomElement(['text', 'file', 'both']),
            'due_at' => now()->addDays(fake()->numberBetween(1, 21)),
            'allow_late' => fake()->boolean(20),
            'max_score' => 100,
            'weight' => 1,
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 10)),
            'created_by' => User::factory()->teacher(),
        ];
    }
}

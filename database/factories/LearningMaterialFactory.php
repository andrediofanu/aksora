<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearningMaterial>
 */
class LearningMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(5),
            'topic' => 'Pertemuan '.fake()->numberBetween(1, 12),
            'description' => fake()->paragraph(2),
            'content' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            'sort_order' => fake()->numberBetween(0, 20),
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 20)),
            'created_by' => User::factory()->teacher(),
        ];
    }
}

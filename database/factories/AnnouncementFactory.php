<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(2, true),
            'is_pinned' => fake()->boolean(20),
            'published_at' => now()->subDays(fake()->numberBetween(0, 10)),
            'created_by' => User::factory()->teacher(),
        ];
    }
}

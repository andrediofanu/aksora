<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        $year = now()->year;

        return [
            'name' => "{$year}/".($year + 1),
            'starts_on' => now()->subMonths(3)->startOfMonth()->toDateString(),
            'ends_on' => now()->addMonths(9)->endOfMonth()->toDateString(),
            'is_active' => false,
            'description' => fake()->sentence(6),
        ];
    }
}

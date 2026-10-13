<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'Fisika',
            'Kimia', 'Biologi', 'Sejarah', 'Geografi', 'Informatika',
            'Ekonomi', 'Sosiologi', 'Seni Budaya',
        ]);

        return [
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4)).fake()->unique()->numberBetween(10, 99),
            'name' => $name,
            'description' => fake()->sentence(8),
            'is_active' => true,
        ];
    }
}

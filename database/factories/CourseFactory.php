<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Matematika Kelas X IPA 1',
                'Bahasa Indonesia Kelas XI IPS 2',
                'Informatika Kelas XII',
                'Fisika Kelas XI IPA 3',
            ]).' '.fake()->unique()->numberBetween(1, 99),
            'code' => strtoupper(Str::random(7)),
            'description' => fake()->paragraph(3),
            'subject_id' => Subject::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'teacher_id' => User::factory()->teacher(),
            'grade_level' => fake()->randomElement(['Kelas X', 'Kelas XI', 'Kelas XII']),
            'room' => 'R-'.fake()->numberBetween(1, 20),
            'status' => 'active',
            'max_members' => 40,
            'allow_student_private_message' => true,
        ];
    }
}

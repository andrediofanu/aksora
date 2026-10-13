<?php

namespace Database\Factories;

use App\Support\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::STUDENT,
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::ADMIN]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::TEACHER,
            'nip' => '19'.fake()->unique()->numberBetween(10000000, 99999999),
            'title' => fake()->randomElement(['S.Pd.', 'M.Pd.', 'S.Kom.', 'M.T.']),
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::STUDENT,
            'nis' => '20'.fake()->unique()->numberBetween(100000, 999999),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }
}

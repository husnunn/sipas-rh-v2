<?php

namespace Database\Factories;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherProfile>
 */
class TeacherProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->teacher(),
            'nip' => fake()->unique()->numerify('##################'),
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement(['male', 'female']),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'photo' => null,
        ];
    }
}

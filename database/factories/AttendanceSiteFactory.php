<?php

namespace Database\Factories;

use App\Models\AttendanceSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSite>
 */
class AttendanceSiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sekolah '.fake()->city(),
            'latitude' => fake()->latitude(-7, -6),
            'longitude' => fake()->longitude(106, 108),
            'radius_m' => fake()->numberBetween(50, 200),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

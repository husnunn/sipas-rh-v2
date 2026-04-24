<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassRoom>
 */
class ClassRoomFactory extends Factory
{
    public function definition(): array
    {
        $level = fake()->randomElement([7, 8, 9]);
        $suffix = fake()->randomElement(['A', 'B', 'C']);

        return [
            'school_year_id' => SchoolYear::factory(),
            'name' => "{$level}{$suffix}",
            'level' => $level,
            'homeroom_teacher_id' => null,
            'is_active' => true,
        ];
    }
}

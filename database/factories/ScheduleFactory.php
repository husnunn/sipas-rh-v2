<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        $startHour = fake()->numberBetween(7, 14);
        $startTime = sprintf('%02d:00', $startHour);
        $endTime = sprintf('%02d:30', $startHour + 1);

        return [
            'school_year_id' => SchoolYear::factory(),
            'semester' => fake()->randomElement([1, 2]),
            'class_id' => ClassRoom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_profile_id' => TeacherProfile::factory(),
            'day_of_week' => fake()->numberBetween(1, 6),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room' => 'Ruang ' . fake()->numberBetween(101, 310),
            'notes' => null,
            'is_active' => true,
        ];
    }
}

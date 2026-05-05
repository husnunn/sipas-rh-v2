<?php

namespace Database\Factories;

use App\Models\AttendanceDayOverride;
use App\Models\AttendanceSite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceDayOverride>
 */
class AttendanceDayOverrideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->toDateString(),
            'name' => fake()->sentence(4),
            'event_type' => fake()->randomElement([
                'early_dismissal',
                'teacher_meeting',
                'special_event',
                'holiday_override',
                'attendance_closed',
                'custom',
            ]),
            'is_active' => true,
            'attendance_site_id' => AttendanceSite::factory(),
            'override_attendance_policy' => true,
            'override_schedule' => false,
            'allow_check_in' => true,
            'allow_check_out' => true,
            'waive_check_out' => false,
            'dismiss_students_early' => false,
            'check_in_open_at' => '06:00:00',
            'check_in_on_time_until' => '07:00:00',
            'check_in_close_at' => '09:00:00',
            'check_out_open_at' => '12:00:00',
            'check_out_close_at' => '18:00:00',
            'notes' => null,
            'created_by' => User::factory()->admin(),
            'updated_by' => null,
        ];
    }
}

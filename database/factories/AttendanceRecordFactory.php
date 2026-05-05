<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'attendance_site_id' => AttendanceSite::factory(),
            'schedule_id' => null,
            'attendance_type' => fake()->randomElement(['check_in', 'check_out']),
            'status' => fake()->randomElement(['approved', 'rejected']),
            'attendance_at' => now(),
            'client_time' => now(),
            'reason_code' => null,
            'reason_detail' => null,
            'distance_m' => fake()->randomFloat(2, 5, 60),
            'network_payload' => ['ssid' => 'SCHOOL-WIFI'],
            'location_payload' => ['latitude' => -6.2, 'longitude' => 106.8],
        ];
    }
}

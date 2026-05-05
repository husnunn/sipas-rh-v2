<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\AttendanceValidationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceValidationLog>
 */
class AttendanceValidationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['approved', 'rejected']),
            'reason_code' => null,
            'details' => ['message' => 'Validation trace'],
        ];
    }
}

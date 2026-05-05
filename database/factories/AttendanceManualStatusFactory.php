<?php

namespace Database\Factories;

use App\Enums\AttendanceManualRecordStatus;
use App\Enums\AttendanceManualType;
use App\Models\AttendanceManualStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceManualStatus>
 */
class AttendanceManualStatusFactory extends Factory
{
    protected $model = AttendanceManualStatus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $studentProfile = StudentProfile::factory()->create();
        $admin = User::factory()->admin()->create();

        return [
            'user_id' => $studentProfile->user_id,
            'student_profile_id' => $studentProfile->id,
            'attendance_site_id' => null,
            'date' => now()->toDateString(),
            'type' => AttendanceManualType::Excused,
            'reason' => fake()->sentence(),
            'notes' => null,
            'status' => AttendanceManualRecordStatus::Approved,
            'created_by' => $admin->id,
            'updated_by' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\DailyAttendancePhysicalStatus;
use App\Models\AttendanceSite;
use App\Models\DailyAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyAttendance>
 */
class DailyAttendanceFactory extends Factory
{
    protected $model = DailyAttendance::class;

    public function configure(): static
    {
        return $this->afterMaking(function (DailyAttendance $model): void {
            if ($model->student_profile_id !== null) {
                return;
            }

            $user = User::query()->with('studentProfile')->find($model->user_id);
            if (! $user) {
                return;
            }

            if (! $user->studentProfile) {
                StudentProfile::factory()->create(['user_id' => $user->id]);
                $user->load('studentProfile');
            }

            $model->student_profile_id = $user->studentProfile->id;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'student_profile_id' => null,
            'attendance_site_id' => AttendanceSite::factory(),
            'date' => now()->toDateString(),
            'check_in_at' => now(),
            'check_out_at' => null,
            'status' => DailyAttendancePhysicalStatus::Present,
            'late_minutes' => 0,
            'check_in_reason_code' => null,
            'check_in_reason_detail' => null,
            'network_payload' => null,
            'location_payload' => null,
            'device_payload' => null,
        ];
    }
}

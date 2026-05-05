<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceManualRecordStatus;
use App\Enums\AttendanceManualType;
use App\Models\AttendanceManualStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAttendanceManualStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_store_manual_status(): void
    {
        $studentUser = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create(['user_id' => $studentUser->id]);

        $response = $this->actingAs($studentUser)->post(
            route('admin.students.attendance-manual-statuses.store', $profile),
            [
                'date' => '2026-05-01',
                'type' => AttendanceManualType::Excused->value,
                'reason' => 'Acara keluarga',
            ],
        );

        $response->assertForbidden();
    }

    public function test_admin_can_store_manual_status(): void
    {
        $admin = User::factory()->admin()->create();
        $studentUser = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create(['user_id' => $studentUser->id]);

        $response = $this->actingAs($admin)->post(
            route('admin.students.attendance-manual-statuses.store', $profile),
            [
                'date' => '2026-05-01',
                'type' => AttendanceManualType::Excused->value,
                'reason' => 'Acara keluarga',
                'notes' => 'Disampaikan oleh wali',
            ],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_manual_statuses', [
            'student_profile_id' => $profile->id,
            'type' => AttendanceManualType::Excused->value,
            'status' => AttendanceManualRecordStatus::Approved->value,
            'created_by' => $admin->id,
        ]);
        $stored = AttendanceManualStatus::query()->where('student_profile_id', $profile->id)->first();
        $this->assertNotNull($stored);
        $this->assertSame('2026-05-01', $stored->date->toDateString());
    }

    public function test_admin_can_cancel_manual_status(): void
    {
        $admin = User::factory()->admin()->create();
        $studentUser = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create(['user_id' => $studentUser->id]);
        $manual = AttendanceManualStatus::factory()->create([
            'user_id' => $studentUser->id,
            'student_profile_id' => $profile->id,
            'date' => '2026-05-02',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.students.attendance-manual-statuses.cancel', [$profile, $manual]),
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_manual_statuses', [
            'id' => $manual->id,
            'status' => AttendanceManualRecordStatus::Cancelled->value,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicCalendarEvent;
use App\Models\AttendanceSite;
use App\Models\AttendanceSiteWifiRule;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_check_in_when_all_validations_pass(): void
    {
        [$student] = $this->prepareStudentSchedule();
        $site = $this->createAttendanceSite();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/attendance/check-in', $this->payload($site->id));

        $response->assertOk()
            ->assertJsonPath('status', 'approved');
    }

    public function test_student_attendance_rejected_when_no_active_schedule(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::factory()->create(['user_id' => $student->id]);
        $site = $this->createAttendanceSite();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/attendance/check-in', $this->payload($site->id));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('reason_code', 'NO_ACTIVE_CLASS');
    }

    public function test_student_attendance_rejected_by_academic_calendar_event(): void
    {
        [$student] = $this->prepareStudentSchedule();
        $site = $this->createAttendanceSite();
        AcademicCalendarEvent::factory()->create([
            'name' => 'Libur Nasional',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'allow_attendance' => false,
            'override_schedule' => true,
            'is_active' => true,
        ]);
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/attendance/check-in', $this->payload($site->id));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('reason_code', 'ACADEMIC_EVENT_BLOCK');
    }

    /**
     * @return array{0: User}
     */
    private function prepareStudentSchedule(): array
    {
        $schoolYear = SchoolYear::factory()->active()->create();
        $classRoom = ClassRoom::factory()->create(['school_year_id' => $schoolYear->id]);
        $teacherProfile = TeacherProfile::factory()->create();
        $subject = Subject::factory()->create();

        $student = User::factory()->student()->create();
        $studentProfile = StudentProfile::factory()->create(['user_id' => $student->id]);
        $studentProfile->classes()->attach($classRoom->id, [
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);

        Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'class_id' => $classRoom->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'day_of_week' => now()->isoWeekday() > 6 ? 6 : now()->isoWeekday(),
            'start_time' => now()->subMinutes(20)->format('H:i:s'),
            'end_time' => now()->addMinutes(20)->format('H:i:s'),
            'is_active' => true,
        ]);

        return [$student];
    }

    private function createAttendanceSite(): AttendanceSite
    {
        $site = AttendanceSite::factory()->create([
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'radius_m' => 300,
        ]);

        AttendanceSiteWifiRule::factory()->create([
            'attendance_site_id' => $site->id,
            'ssid' => 'SCHOOL-WIFI',
            'bssid' => 'AA:BB:CC:11:22:33',
            'ip_subnet' => '192.168.1.0/24',
        ]);

        return $site;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $siteId): array
    {
        return [
            'attendance_site_id' => $siteId,
            'attendance_type' => 'check_in',
            'client_time' => now()->toIso8601String(),
            'network' => [
                'ssid' => 'SCHOOL-WIFI',
                'bssid' => 'AA:BB:CC:11:22:33',
                'local_ip' => '192.168.1.25',
                'gateway_ip' => '192.168.1.1',
                'subnet_prefix' => 24,
                'transport' => 'WIFI',
            ],
            'location' => [
                'latitude' => -6.2000100,
                'longitude' => 106.8166800,
                'accuracy_m' => 10,
                'provider' => 'fused',
                'is_mock' => false,
                'captured_at' => now()->toIso8601String(),
            ],
        ];
    }
}

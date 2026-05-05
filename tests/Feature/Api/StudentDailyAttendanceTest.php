<?php

namespace Tests\Feature\Api;

use App\Enums\AttendanceManualRecordStatus;
use App\Enums\AttendanceManualType;
use App\Models\AcademicCalendarEvent;
use App\Models\AttendanceDayOverride;
use App\Models\AttendanceManualStatus;
use App\Models\AttendanceSite;
use App\Models\AttendanceSiteWifiRule;
use App\Models\ClassRoom;
use App\Models\DailyAttendance;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentDailyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.school_timezone' => 'Asia/Jakarta',
            'school_daily_attendance.check_in.open' => '06:00',
            'school_daily_attendance.check_in.on_time_until' => '07:00',
            'school_daily_attendance.check_in.close' => '09:00',
            'school_daily_attendance.check_out.open' => '12:00',
            'school_daily_attendance.check_out.close' => '18:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_student_daily_check_in_on_time_is_present(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id));

        $response->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('attendance_status', 'present')
            ->assertJsonPath('late_minutes', 0);
    }

    public function test_student_daily_check_in_after_on_time_until_is_late_with_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 07:18:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id));

        $response->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('attendance_status', 'late')
            ->assertJsonPath('late_minutes', 18)
            ->assertJsonPath('reason_code', 'LATE_CHECK_IN');
    }

    public function test_daily_attendance_check_in_survives_db_roundtrip_in_school_timezone_iso(): void
    {
        config(['app.timezone' => 'UTC']);

        Carbon::setTestNow(Carbon::parse('2026-04-30 08:47:39', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id))->assertOk();

        $dailyRow = DailyAttendance::query()->where('user_id', $student->id)->firstOrFail();
        $this->assertSame(
            '2026-04-30 08:47:39',
            $dailyRow->check_in_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        );

        $today = $this->getJson('/api/v1/student/daily-attendance/today')->assertOk();
        $today->assertJsonPath('data.date', '2026-04-30');
        $today->assertJsonPath('data.source', 'daily_attendance');
        $today->assertJsonPath('data.status', 'late');
        $clockInSchoolTz = Carbon::parse($today->json('data.check_in_at'))->timezone('Asia/Jakarta');
        $this->assertSame('2026-04-30 08:47:39', $clockInSchoolTz->format('Y-m-d H:i:s'));
        $today->assertJsonPath('data.late_minutes', 107);
    }

    public function test_student_daily_check_in_rejected_before_open(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 05:30:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('reason_code', 'CHECK_IN_NOT_OPEN_YET');
    }

    public function test_student_daily_check_in_rejected_after_close(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 09:15:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('reason_code', 'CHECK_IN_CLOSED');
    }

    public function test_student_daily_check_in_rejected_when_academic_calendar_blocks(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        AcademicCalendarEvent::factory()->create([
            'name' => 'Libur Nasional',
            'start_date' => '2026-04-29',
            'end_date' => '2026-04-29',
            'allow_attendance' => false,
            'override_schedule' => true,
            'is_active' => true,
        ]);
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id));

        $response->assertStatus(422)
            ->assertJsonPath('reason_code', 'ACADEMIC_EVENT_BLOCK');
    }

    public function test_student_daily_check_in_rejected_when_manual_status_exists(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        $profile = $student->studentProfile;
        $this->assertNotNull($profile);
        $admin = User::factory()->admin()->create();
        AttendanceManualStatus::factory()->create([
            'user_id' => $student->id,
            'student_profile_id' => $profile->id,
            'date' => '2026-04-29',
            'type' => AttendanceManualType::Excused,
            'reason' => 'Izin ujian',
            'status' => AttendanceManualRecordStatus::Approved,
            'created_by' => $admin->id,
        ]);
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id));

        $response->assertStatus(422)
            ->assertJsonPath('reason_code', 'MANUAL_STATUS_EXISTS');
    }

    public function test_student_daily_today_returns_expected_shape(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id))->assertOk();

        $response = $this->getJson('/api/v1/student/daily-attendance/today');

        $response->assertOk()
            ->assertJsonPath('data.date', '2026-04-29')
            ->assertJsonPath('data.status', 'present')
            ->assertJsonPath('data.can_check_in', false)
            ->assertJsonPath('data.can_check_out', false)
            ->assertJsonPath('data.site.id', $site->id)
            ->assertJsonStructure([
                'data' => [
                    'can_check_in',
                    'can_check_out',
                    'label',
                    'source',
                    'site',
                    'effective_policy',
                ],
                'attendance_sites' => [
                    '*' => ['id', 'name', 'latitude', 'longitude', 'radius_m'],
                ],
            ])
            ->assertJsonPath('attendance_sites.0.id', $site->id);
    }

    public function test_student_daily_today_uses_site_policy_with_config_fallback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        $site->update([
            'check_in_open_at' => '05:30',
            'check_in_on_time_until' => null,
            'check_in_close_at' => null,
            'check_out_open_at' => null,
            'check_out_close_at' => null,
        ]);
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/v1/student/daily-attendance/today');

        $response->assertOk()
            ->assertJsonPath('data.site.id', $site->id)
            ->assertJsonPath('data.site.name', $site->name)
            ->assertJsonPath('data.effective_policy.check_in_open_at', '05:30')
            ->assertJsonPath('data.effective_policy.check_in_on_time_until', '07:00')
            ->assertJsonPath('data.effective_policy.check_out_close_at', '18:00');
    }

    public function test_student_daily_today_includes_attendance_sites_when_still_absent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/v1/student/daily-attendance/today');

        $response->assertOk()
            ->assertJsonPath('data.source', 'absent')
            ->assertJsonPath('attendance_sites.0.id', $site->id);
    }

    public function test_student_daily_check_out_uses_override_window_and_can_be_opened_early(): void
    {
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        AttendanceDayOverride::factory()->create([
            'date' => '2026-04-29',
            'event_type' => 'teacher_meeting',
            'override_attendance_policy' => true,
            'dismiss_students_early' => true,
            'check_in_open_at' => '06:00',
            'check_in_on_time_until' => '07:00',
            'check_in_close_at' => '12:00',
            'check_out_open_at' => '10:00',
            'check_out_close_at' => '13:00',
        ]);
        Sanctum::actingAs($student);

        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id))
            ->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-04-29 10:15:00', 'Asia/Jakarta'));
        $this->postJson('/api/v1/student/daily-attendance/check-out', $this->payload($site->id))
            ->assertOk()
            ->assertJsonPath('status', 'approved');
    }

    public function test_student_daily_check_out_rejected_after_override_close(): void
    {
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        AttendanceDayOverride::factory()->create([
            'date' => '2026-04-29',
            'event_type' => 'teacher_meeting',
            'override_attendance_policy' => true,
            'check_out_open_at' => '10:00',
            'check_out_close_at' => '13:00',
        ]);
        Sanctum::actingAs($student);

        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id))
            ->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-04-29 13:15:00', 'Asia/Jakarta'));
        $this->postJson('/api/v1/student/daily-attendance/check-out', $this->payload($site->id))
            ->assertStatus(422)
            ->assertJsonPath('reason_code', 'CHECK_OUT_CLOSED');
    }

    public function test_student_daily_today_includes_override_payload_and_waive_check_out(): void
    {
        [$student, $site] = $this->prepareStudentWithClassAndSite();
        AttendanceDayOverride::factory()->create([
            'date' => '2026-04-29',
            'event_type' => 'teacher_meeting',
            'override_attendance_policy' => true,
            'allow_check_out' => false,
            'waive_check_out' => true,
            'dismiss_students_early' => true,
        ]);
        Sanctum::actingAs($student);

        Carbon::setTestNow(Carbon::parse('2026-04-29 06:45:00', 'Asia/Jakarta'));
        $this->postJson('/api/v1/student/daily-attendance/check-in', $this->payload($site->id))
            ->assertOk();

        $response = $this->getJson('/api/v1/student/daily-attendance/today');
        $response->assertOk()
            ->assertJsonPath('data.override.active', true)
            ->assertJsonPath('data.override.event_type', 'teacher_meeting')
            ->assertJsonPath('data.override.waive_check_out', true)
            ->assertJsonPath('data.can_check_out', false);
    }

    /**
     * @return array{0: User, 1: AttendanceSite}
     */
    private function prepareStudentWithClassAndSite(): array
    {
        $schoolYear = SchoolYear::factory()->active()->create();
        $classRoom = ClassRoom::factory()->create(['school_year_id' => $schoolYear->id]);
        TeacherProfile::factory()->create();
        Subject::factory()->create();

        $student = User::factory()->student()->create();
        $studentProfile = StudentProfile::factory()->create(['user_id' => $student->id]);
        $studentProfile->classes()->attach($classRoom->id, [
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);

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

        return [$student, $site];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $siteId): array
    {
        $iso = Carbon::now('Asia/Jakarta')->toIso8601String();

        return [
            'attendance_site_id' => $siteId,
            'client_time' => $iso,
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
                'captured_at' => $iso,
            ],
        ];
    }
}

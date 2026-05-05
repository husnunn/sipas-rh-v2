<?php

namespace Tests\Feature;

use App\Models\AttendanceSite;
use App\Models\AttendanceSiteWifiRule;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_check_in_rejected_when_wifi_not_matching(): void
    {
        $teacher = $this->prepareTeacherSchedule();
        $site = $this->createAttendanceSite();
        Sanctum::actingAs($teacher);

        $payload = $this->payload($site->id);
        $payload['network']['bssid'] = '00:00:00:00:00:00';

        $response = $this->postJson('/api/v1/teacher/attendance/check-in', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('reason_code', 'WIFI_NOT_MATCHED');
    }

    public function test_teacher_check_in_rejected_when_out_of_radius(): void
    {
        $teacher = $this->prepareTeacherSchedule();
        $site = $this->createAttendanceSite();
        Sanctum::actingAs($teacher);

        $payload = $this->payload($site->id);
        $payload['location']['latitude'] = -6.250000;
        $payload['location']['longitude'] = 106.900000;

        $response = $this->postJson('/api/v1/teacher/attendance/check-in', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('reason_code', 'OUT_OF_RADIUS');
    }

    private function prepareTeacherSchedule(): User
    {
        $schoolYear = SchoolYear::factory()->active()->create();
        $classRoom = ClassRoom::factory()->create(['school_year_id' => $schoolYear->id]);
        $subject = Subject::factory()->create();
        $teacher = User::factory()->teacher()->create();
        $teacherProfile = TeacherProfile::factory()->create(['user_id' => $teacher->id]);

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

        return $teacher;
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

    public function test_teacher_me_includes_attendance_sites(): void
    {
        $teacher = $this->prepareTeacherSchedule();
        $site = $this->createAttendanceSite();
        Sanctum::actingAs($teacher);

        $response = $this->getJson('/api/v1/teacher/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'full_name'],
                'attendance_sites' => [
                    '*' => ['id', 'name', 'latitude', 'longitude', 'radius_m'],
                ],
            ])
            ->assertJsonPath('attendance_sites.0.id', $site->id);
    }

    public function test_teacher_attendance_today_includes_attendance_sites(): void
    {
        $teacher = $this->prepareTeacherSchedule();
        $site = $this->createAttendanceSite();
        Sanctum::actingAs($teacher);

        $response = $this->getJson('/api/v1/teacher/attendance/today');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'attendance_sites' => [
                    '*' => ['id', 'name', 'latitude', 'longitude', 'radius_m'],
                ],
            ])
            ->assertJsonPath('attendance_sites.0.id', $site->id);
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

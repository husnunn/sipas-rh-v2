<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceManualRecordStatus;
use App\Enums\AttendanceManualType;
use App\Models\AttendanceManualStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSite;
use App\Models\ClassRoom;
use App\Models\DailyAttendance;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AttendanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.attendance-records.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_monitoring(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('admin.attendance-records.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_unified_student_attendance_rows(): void
    {
        $site = AttendanceSite::factory()->create();
        $schoolYear = SchoolYear::factory()->active()->create([
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);
        $class = ClassRoom::factory()->create(['school_year_id' => $schoolYear->id]);
        $schedule = Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'class_id' => $class->id,
            'semester' => 1,
        ]);

        $student = User::factory()->student()->create(['name' => 'Ani Student']);
        $profile = StudentProfile::factory()->create(['user_id' => $student->id]);
        $profile->classes()->attach($class->id, [
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $student->id,
            'attendance_site_id' => $site->id,
            'schedule_id' => $schedule->id,
            'status' => 'approved',
            'attendance_at' => now(),
        ]);

        DailyAttendance::factory()->create([
            'user_id' => $student->id,
            'student_profile_id' => $profile->id,
            'attendance_site_id' => $site->id,
            'date' => now()->toDateString(),
            'check_in_at' => now(),
            'check_out_at' => null,
            'status' => 'present',
        ]);

        $admin = User::factory()->admin()->create();

        AttendanceManualStatus::query()->create([
            'user_id' => $student->id,
            'student_profile_id' => $profile->id,
            'attendance_site_id' => null,
            'date' => now()->toDateString(),
            'type' => AttendanceManualType::Excused,
            'reason' => 'Izin keluarga',
            'notes' => null,
            'status' => AttendanceManualRecordStatus::Approved,
            'created_by' => $admin->id,
            'updated_by' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance-records.index', [
                'school_year_id' => $schoolYear->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/AttendanceRecords/Index')
                ->has('records.data', 3)
                ->where('stats.total', 3)
                ->has('classes')
                ->has('school_years'));
    }

    public function test_admin_can_export_csv_with_unified_columns(): void
    {
        $site = AttendanceSite::factory()->create(['name' => 'Gerbang Utama']);
        $schoolYear = SchoolYear::factory()->active()->create([
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);
        $class = ClassRoom::factory()->create(['school_year_id' => $schoolYear->id]);
        $schedule = Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'class_id' => $class->id,
            'semester' => 1,
        ]);

        $student = User::factory()->student()->create(['name' => 'Xavier']);
        $profile = StudentProfile::factory()->create(['user_id' => $student->id]);
        $profile->classes()->attach($class->id, [
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $student->id,
            'attendance_site_id' => $site->id,
            'schedule_id' => $schedule->id,
            'status' => 'rejected',
            'reason_detail' => 'Jarak terlalu jauh',
            'attendance_at' => now(),
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance-records.export', [
                'school_year_id' => $schoolYear->id,
                'report' => 'monitoring',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('nama', strtolower($content));
        $this->assertStringContainsString('Xavier', $content);
        $this->assertStringContainsString('Jarak terlalu jauh', $content);
    }

    public function test_admin_monitoring_default_shows_all_school_year_history(): void
    {
        $site = AttendanceSite::factory()->create();
        $oldSchoolYear = SchoolYear::factory()->create([
            'is_active' => false,
            'name' => '2019/2020',
            'start_date' => now()->subYears(2)->startOfYear()->toDateString(),
            'end_date' => now()->subYears(2)->endOfYear()->toDateString(),
        ]);
        $currentSchoolYear = SchoolYear::factory()->active()->create([
            'name' => '2024/2025',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
        ]);

        $oldClass = ClassRoom::factory()->create(['school_year_id' => $oldSchoolYear->id]);
        $currentClass = ClassRoom::factory()->create(['school_year_id' => $currentSchoolYear->id]);

        $oldSchedule = Schedule::factory()->create([
            'school_year_id' => $oldSchoolYear->id,
            'class_id' => $oldClass->id,
            'semester' => 1,
        ]);
        $currentSchedule = Schedule::factory()->create([
            'school_year_id' => $currentSchoolYear->id,
            'class_id' => $currentClass->id,
            'semester' => 1,
        ]);

        $student = User::factory()->student()->create(['name' => 'Rina']);
        StudentProfile::factory()->create(['user_id' => $student->id]);

        AttendanceRecord::factory()->create([
            'user_id' => $student->id,
            'attendance_site_id' => $site->id,
            'schedule_id' => $oldSchedule->id,
            'attendance_at' => now()->subYears(2),
        ]);
        AttendanceRecord::factory()->create([
            'user_id' => $student->id,
            'attendance_site_id' => $site->id,
            'schedule_id' => $currentSchedule->id,
            'attendance_at' => now(),
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.attendance-records.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/AttendanceRecords/Index')
                ->where('stats.total', 2)
                ->has('records.data', 2));
    }

    public function test_export_per_class_requires_class_id(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.attendance-records.export', [
                'report' => 'class',
                'school_year_id' => SchoolYear::factory()->create()->id,
            ]))
            ->assertSessionHasErrors('class_id');
    }

    public function test_monitoring_attendance_time_keeps_school_wall_clock_without_double_shift(): void
    {
        config(['app.school_timezone' => 'Asia/Jakarta']);

        $site = AttendanceSite::factory()->create();
        $schoolYear = SchoolYear::factory()->active()->create([
            'name' => '2025/2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $class = ClassRoom::factory()->create(['school_year_id' => $schoolYear->id]);
        $schedule = Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'class_id' => $class->id,
            'semester' => 1,
        ]);

        $student = User::factory()->student()->create();
        StudentProfile::factory()->create(['user_id' => $student->id]);

        AttendanceRecord::factory()->create([
            'user_id' => $student->id,
            'attendance_site_id' => $site->id,
            'schedule_id' => $schedule->id,
            'attendance_at' => '2026-04-29 13:32:26',
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.attendance-records.index', ['school_year_id' => $schoolYear->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('records.data', 1)
                ->where('records.data.0.attendance_time', Carbon::parse('2026-04-29 13:32:26', 'Asia/Jakarta')->toIso8601String()));
    }
}

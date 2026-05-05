<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceDayOverride;
use App\Models\AttendanceSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDayOverrideManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_attendance_day_override(): void
    {
        $admin = User::factory()->admin()->create();
        $site = AttendanceSite::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.attendance-day-overrides.store'), [
                'name' => 'Rapat Guru Mendadak',
                'date' => '2026-05-02',
                'event_type' => 'teacher_meeting',
                'is_active' => true,
                'attendance_site_id' => $site->id,
                'override_attendance_policy' => true,
                'override_schedule' => true,
                'allow_check_in' => true,
                'allow_check_out' => true,
                'waive_check_out' => false,
                'dismiss_students_early' => true,
                'check_in_open_at' => '06:00',
                'check_in_on_time_until' => '07:00',
                'check_in_close_at' => '12:00',
                'check_out_open_at' => '10:00',
                'check_out_close_at' => '13:00',
                'notes' => 'Insidental',
            ])
            ->assertRedirect(route('admin.attendance-day-overrides.index'));

        $override = AttendanceDayOverride::query()->first();
        $this->assertNotNull($override);
        $this->assertSame('teacher_meeting', $override->event_type);
        $this->assertTrue($override->override_attendance_policy);
    }

    public function test_admin_can_create_override_when_policy_times_use_his_format(): void
    {
        $admin = User::factory()->admin()->create();
        $site = AttendanceSite::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.attendance-day-overrides.store'), [
                'name' => 'Demo',
                'date' => '2026-05-03',
                'event_type' => 'custom',
                'is_active' => true,
                'attendance_site_id' => $site->id,
                'override_attendance_policy' => true,
                'override_schedule' => false,
                'allow_check_in' => true,
                'allow_check_out' => true,
                'waive_check_out' => false,
                'dismiss_students_early' => false,
                'check_in_open_at' => '06:00:00',
                'check_in_on_time_until' => '07:00:00',
                'check_in_close_at' => '08:30:30',
                'check_out_open_at' => '10:00:00',
                'check_out_close_at' => '13:00:00',
                'notes' => null,
            ])
            ->assertRedirect(route('admin.attendance-day-overrides.index'));

        $this->assertNotNull(AttendanceDayOverride::query()->first());
    }

    public function test_admin_can_toggle_active_override(): void
    {
        $admin = User::factory()->admin()->create();
        $override = AttendanceDayOverride::factory()->create([
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.attendance-day-overrides.toggle-active', $override))
            ->assertRedirect();

        $this->assertFalse($override->fresh()->is_active);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicCalendarEvent;
use App\Models\AttendanceDayOverride;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSite;
use App\Models\PasswordResetAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class SipasPurgeOperationalDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_keeps_accounts_masters_locations_and_override_rules(): void
    {
        $admin = User::factory()->admin()->create();
        $studentUser = User::factory()->student()->create();

        $site = AttendanceSite::factory()->create(['name' => 'Titik Alfa']);

        AttendanceRecord::factory()->create([
            'user_id' => $studentUser->id,
            'attendance_site_id' => $site->id,
        ]);

        AcademicCalendarEvent::factory()->create();

        $override = AttendanceDayOverride::factory()->create([
            'attendance_site_id' => $site->id,
            'created_by' => $admin->id,
        ]);

        PasswordResetAudit::create([
            'user_id' => $studentUser->id,
            'reset_by_admin_id' => $admin->id,
            'reason' => null,
            'ip_address' => '127.0.0.1',
        ]);

        $this->artisan('sipas:purge-operational-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, AttendanceRecord::query()->count());
        $this->assertSame(0, AcademicCalendarEvent::query()->count());
        $this->assertSame(0, PasswordResetAudit::query()->count());

        $this->assertDatabaseHas('attendance_sites', ['id' => $site->id, 'name' => 'Titik Alfa']);
        $this->assertDatabaseHas('attendance_day_overrides', ['id' => $override->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $studentUser->id]);
    }

    public function test_purge_bails_when_user_declines_confirmation(): void
    {
        AcademicCalendarEvent::factory()->create();

        $this->artisan('sipas:purge-operational-data')
            ->expectsConfirmation('Hapus data operasional (riwayat absensi, dll.) sesuai daftar di atas?', 'no')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(1, AcademicCalendarEvent::query()->count());
    }

    public function test_purge_runs_when_user_accepts_confirmation(): void
    {
        AcademicCalendarEvent::factory()->create();

        AttendanceSite::factory()->create(['name' => 'Titik Gamma']);

        $this->artisan('sipas:purge-operational-data')
            ->expectsConfirmation('Hapus data operasional (riwayat absensi, dll.) sesuai daftar di atas?', 'yes')
            ->assertSuccessful();

        $this->assertSame(0, AcademicCalendarEvent::query()->count());
        $this->assertSame(1, AttendanceSite::query()->where('name', 'Titik Gamma')->count());
    }
}

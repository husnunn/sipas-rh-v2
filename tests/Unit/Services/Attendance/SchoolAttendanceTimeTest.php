<?php

namespace Tests\Unit\Services\Attendance;

use App\Services\Attendance\SchoolAttendanceTime;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchoolAttendanceTimeTest extends TestCase
{
    #[Test]
    public function test_resolve_attendance_at_converts_utc_instant_to_school_timezone(): void
    {
        config(['app.school_timezone' => 'Asia/Jakarta']);

        $resolved = SchoolAttendanceTime::resolveAttendanceAt('2026-04-28T17:00:00Z');

        $this->assertSame('Asia/Jakarta', $resolved->getTimezone()->getName());
        $this->assertSame('2026-04-29 00:00:00', $resolved->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function test_resolve_attendance_at_interprets_naive_string_as_school_wall_clock_not_app_tz(): void
    {
        config([
            'app.timezone' => 'UTC',
            'app.school_timezone' => 'Asia/Jakarta',
        ]);

        $resolved = SchoolAttendanceTime::resolveAttendanceAt('2026-04-30 08:47:39');

        $this->assertSame('Asia/Jakarta', $resolved->getTimezone()->getName());
        $this->assertSame('2026-04-30 08:47:39', $resolved->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function test_schedule_wall_datetime_uses_school_calendar_date(): void
    {
        config(['app.school_timezone' => 'Asia/Jakarta']);

        $attendanceAt = SchoolAttendanceTime::resolveAttendanceAt('2026-04-28T17:00:00Z');
        $start = SchoolAttendanceTime::scheduleWallDateTime($attendanceAt, '08:00:00');

        $this->assertSame('2026-04-29 08:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Jakarta', $start->getTimezone()->getName());
    }

    #[Test]
    public function test_parse_calendar_date_is_midnight_in_school_timezone(): void
    {
        config(['app.school_timezone' => 'Asia/Jakarta']);

        $date = SchoolAttendanceTime::parseCalendarDate('2026-05-01');

        $this->assertSame('2026-05-01 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Jakarta', $date->getTimezone()->getName());
    }
}

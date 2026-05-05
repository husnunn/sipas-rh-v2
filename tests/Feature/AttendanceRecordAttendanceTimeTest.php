<?php

namespace Tests\Feature;

use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AttendanceRecordAttendanceTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_time_accessor_matches_school_timezone_iso8601(): void
    {
        config(['app.school_timezone' => 'Asia/Jakarta']);

        $record = AttendanceRecord::factory()->create([
            'attendance_at' => '2026-04-28 10:00:00',
        ]);

        // Mirror AttendanceRecord::attendanceTime: naive DB datetime is wall clock in school TZ.
        $expected = Carbon::parse('2026-04-28 10:00:00', 'Asia/Jakarta')->toIso8601String();

        $this->assertSame($expected, $record->attendance_time);
    }

    public function test_attendance_record_resource_uses_attendance_time_accessor(): void
    {
        config(['app.school_timezone' => 'Asia/Jakarta']);

        $record = AttendanceRecord::factory()->create([
            'attendance_at' => '2026-04-28 10:00:00',
        ]);

        $payload = (new AttendanceRecordResource($record))->toArray(Request::create('/'));

        $this->assertSame($record->attendance_time, $payload['attendance_time']);
    }
}

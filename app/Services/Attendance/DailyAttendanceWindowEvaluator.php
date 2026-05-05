<?php

namespace App\Services\Attendance;

use App\Enums\DailyAttendancePhysicalStatus;
use Carbon\Carbon;

final class DailyAttendanceWindowEvaluator
{
    /**
     * @return array{
     *   allowed: bool,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   attendance_status: DailyAttendancePhysicalStatus|null,
     *   late_minutes: int|null
     * }
     */
    public function evaluateCheckIn(Carbon $attendanceAt, array $effectivePolicy): array
    {
        $tz = SchoolAttendanceTime::timezone();
        $local = $attendanceAt->copy()->timezone($tz);
        $dateYmd = $local->toDateString();
        $openClock = (string) ($effectivePolicy['check_in_open_at'] ?? config('school_daily_attendance.check_in.open'));
        $onTimeUntilClock = (string) ($effectivePolicy['check_in_on_time_until'] ?? config('school_daily_attendance.check_in.on_time_until'));
        $closeClock = (string) ($effectivePolicy['check_in_close_at'] ?? config('school_daily_attendance.check_in.close'));

        $open = $this->wallClock($dateYmd, (string) $openClock, $tz);
        $onTimeUntil = $this->wallClock($dateYmd, (string) $onTimeUntilClock, $tz);
        $close = $this->wallClock($dateYmd, (string) $closeClock, $tz);

        if ($local->lt($open)) {
            return [
                'allowed' => false,
                'reason_code' => 'CHECK_IN_NOT_OPEN_YET',
                'reason_detail' => 'Check-in belum dibuka.',
                'attendance_status' => null,
                'late_minutes' => null,
            ];
        }

        if ($local->gt($close)) {
            return [
                'allowed' => false,
                'reason_code' => 'CHECK_IN_CLOSED',
                'reason_detail' => 'Batas waktu check-in telah lewat.',
                'attendance_status' => null,
                'late_minutes' => null,
            ];
        }

        if ($local->lte($onTimeUntil)) {
            return [
                'allowed' => true,
                'reason_code' => null,
                'reason_detail' => null,
                'attendance_status' => DailyAttendancePhysicalStatus::Present,
                'late_minutes' => 0,
            ];
        }

        $lateMinutes = (int) $onTimeUntil->diffInMinutes($local, false);

        return [
            'allowed' => true,
            'reason_code' => 'LATE_CHECK_IN',
            'reason_detail' => 'Check-in setelah batas hadir tepat waktu.',
            'attendance_status' => DailyAttendancePhysicalStatus::Late,
            'late_minutes' => max(0, $lateMinutes),
        ];
    }

    /**
     * @return array{allowed: bool, reason_code: string|null, reason_detail: string|null}
     */
    public function evaluateCheckOut(Carbon $attendanceAt, array $effectivePolicy): array
    {
        $tz = SchoolAttendanceTime::timezone();
        $local = $attendanceAt->copy()->timezone($tz);
        $dateYmd = $local->toDateString();
        $openClock = (string) ($effectivePolicy['check_out_open_at'] ?? config('school_daily_attendance.check_out.open'));
        $closeClock = (string) ($effectivePolicy['check_out_close_at'] ?? config('school_daily_attendance.check_out.close'));

        $open = $this->wallClock($dateYmd, (string) $openClock, $tz);
        $close = $this->wallClock($dateYmd, (string) $closeClock, $tz);

        if ($local->lt($open)) {
            return [
                'allowed' => false,
                'reason_code' => 'CHECK_OUT_NOT_OPEN_YET',
                'reason_detail' => 'Check-out belum dibuka.',
            ];
        }

        if ($local->gt($close)) {
            return [
                'allowed' => false,
                'reason_code' => 'CHECK_OUT_CLOSED',
                'reason_detail' => 'Batas waktu check-out telah lewat.',
            ];
        }

        return [
            'allowed' => true,
            'reason_code' => null,
            'reason_detail' => null,
        ];
    }

    private function wallClock(string $dateYmd, string $time, string $tz): Carbon
    {
        $trimmed = trim($time);
        $normalized = strlen($trimmed) <= 5 ? $trimmed.':00' : $trimmed;

        return Carbon::createFromFormat('Y-m-d H:i:s', "{$dateYmd} {$normalized}", $tz);
    }
}

<?php

namespace App\Services\Attendance;

use Carbon\Carbon;

final class SchoolAttendanceTime
{
    public static function timezone(): string
    {
        return (string) config('app.school_timezone');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    /**
     * Interpret client_time (or server now) in the school timezone for eligibility, calendar, and windows.
     *
     * Strings without timezone (common from mobile clocks) are parsed in {@see self::timezone()},
     * not in {@see config('app.timezone')} (often UTC).
     */
    public static function resolveAttendanceAt(?string $clientTime): Carbon
    {
        if ($clientTime === null || $clientTime === '') {
            return self::now();
        }

        return Carbon::parse($clientTime, self::timezone())->timezone(self::timezone());
    }

    /**
     * Calendar day in the school timezone (e.g. query ?date=2026-05-01).
     */
    public static function parseCalendarDate(string $date): Carbon
    {
        return Carbon::parse($date, self::timezone())->startOfDay();
    }

    /**
     * Combine a schedule clock time (DB time column) with the attendance calendar date in the school timezone.
     */
    public static function scheduleWallDateTime(Carbon $attendanceAtSchoolTz, string $clockTime, ?string $timezone = null): Carbon
    {
        $tz = $timezone ?? self::timezone();
        $datePart = $attendanceAtSchoolTz->toDateString();
        $normalized = self::normalizeClockToHis($clockTime);

        return Carbon::createFromFormat('Y-m-d H:i:s', "{$datePart} {$normalized}", $tz);
    }

    private static function normalizeClockToHis(string $clockTime): string
    {
        $base = strstr($clockTime, '.', true) ?: $clockTime;
        $base = trim($base);
        $parts = explode(':', $base);
        $h = str_pad($parts[0] ?? '0', 2, '0', STR_PAD_LEFT);
        $i = str_pad($parts[1] ?? '0', 2, '0', STR_PAD_LEFT);
        $s = str_pad($parts[2] ?? '00', 2, '0', STR_PAD_LEFT);

        return "{$h}:{$i}:{$s}";
    }
}

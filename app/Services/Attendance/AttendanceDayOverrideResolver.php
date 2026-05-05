<?php

namespace App\Services\Attendance;

use App\Models\AttendanceDayOverride;
use Carbon\CarbonInterface;

final class AttendanceDayOverrideResolver
{
    public function resolveForDay(CarbonInterface $dayInSchoolTimezone): ?AttendanceDayOverride
    {
        return AttendanceDayOverride::query()
            ->active()
            ->forDate($dayInSchoolTimezone)
            ->orderByDesc('override_attendance_policy')
            ->orderByDesc('override_schedule')
            ->orderByDesc('dismiss_students_early')
            ->orderBy('id')
            ->first();
    }
}

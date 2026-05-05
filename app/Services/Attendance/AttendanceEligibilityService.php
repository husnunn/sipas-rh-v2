<?php

namespace App\Services\Attendance;

use App\Models\AcademicCalendarEvent;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\User;
use Carbon\Carbon;

class AttendanceEligibilityService
{
    /**
     * @return array{
     *   allowed: bool,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   schedule_id: int|null,
     *   matched_event_id: int|null
     * }
     */
    public function evaluate(User $user, Carbon $attendanceAt, string $attendanceType = 'check_in'): array
    {
        $schoolTz = SchoolAttendanceTime::timezone();
        $attendanceAt = $attendanceAt->copy()->timezone($schoolTz);

        $calendarEvent = AcademicCalendarEvent::query()
            ->active()
            ->overlapsDate($attendanceAt)
            ->orderByDesc('override_schedule')
            ->orderBy('start_date')
            ->first();

        if ($calendarEvent && (! $calendarEvent->allow_attendance || $calendarEvent->override_schedule)) {
            return [
                'allowed' => false,
                'reason_code' => 'ACADEMIC_EVENT_BLOCK',
                'reason_detail' => "Absensi tidak diizinkan karena event akademik: {$calendarEvent->name}.",
                'schedule_id' => null,
                'matched_event_id' => $calendarEvent->id,
            ];
        }

        $activeSchoolYear = SchoolYear::active()->first();
        $dayOfWeek = (int) $attendanceAt->isoWeekday();

        if ($dayOfWeek > 6) {
            return [
                'allowed' => false,
                'reason_code' => 'NO_ACTIVE_SCHEDULE',
                'reason_detail' => 'Tidak ada jadwal aktif pada hari ini.',
                'schedule_id' => null,
                'matched_event_id' => $calendarEvent?->id,
            ];
        }

        $scheduleQuery = Schedule::query()
            ->active()
            ->where('day_of_week', $dayOfWeek)
            ->when($activeSchoolYear, fn ($query) => $query->where('school_year_id', $activeSchoolYear->id));

        if ($user->hasRole('teacher')) {
            $teacherProfile = $user->teacherProfile;
            if (! $teacherProfile) {
                return [
                    'allowed' => false,
                    'reason_code' => 'PROFILE_NOT_FOUND',
                    'reason_detail' => 'Profil guru belum tersedia.',
                    'schedule_id' => null,
                    'matched_event_id' => $calendarEvent?->id,
                ];
            }

            $scheduleQuery->where('teacher_profile_id', $teacherProfile->id);
        }

        if ($user->hasRole('student')) {
            $studentProfile = $user->studentProfile;
            if (! $studentProfile) {
                return [
                    'allowed' => false,
                    'reason_code' => 'PROFILE_NOT_FOUND',
                    'reason_detail' => 'Profil siswa belum tersedia.',
                    'schedule_id' => null,
                    'matched_event_id' => $calendarEvent?->id,
                ];
            }

            $activeClassQuery = $studentProfile->activeClass();
            if ($activeSchoolYear) {
                $activeClassQuery->wherePivot('school_year_id', $activeSchoolYear->id);
            }

            $activeClass = $activeClassQuery->first();

            if (! $activeClass) {
                return [
                    'allowed' => false,
                    'reason_code' => 'NO_ACTIVE_CLASS',
                    'reason_detail' => 'Siswa belum memiliki kelas aktif.',
                    'schedule_id' => null,
                    'matched_event_id' => $calendarEvent?->id,
                ];
            }

            $scheduleQuery->where('class_id', $activeClass->id);
        }

        $schedules = $scheduleQuery->get();
        $schedule = $schedules->first(function (Schedule $schedule) use ($attendanceAt, $attendanceType, $schoolTz): bool {
            $startTime = SchoolAttendanceTime::scheduleWallDateTime($attendanceAt, (string) $schedule->start_time, $schoolTz);

            if ($attendanceType === 'check_in') {
                $windowStart = $startTime->copy()->subMinutes(15);
                $windowEnd = $startTime->copy()->addMinutes(20);

                return $attendanceAt->betweenIncluded($windowStart, $windowEnd);
            }

            $endTime = SchoolAttendanceTime::scheduleWallDateTime($attendanceAt, (string) $schedule->end_time, $schoolTz);

            return $attendanceAt->betweenIncluded($startTime, $endTime);
        });
        if (! $schedule) {
            return [
                'allowed' => false,
                'reason_code' => 'NO_ACTIVE_SCHEDULE',
                'reason_detail' => $attendanceType === 'check_in'
                    ? 'Tidak ada jadwal check-in aktif pada window absensi (H-15 menit s/d H+20 menit dari jam mulai).'
                    : 'Tidak ada jadwal aktif pada waktu absensi ini.',
                'schedule_id' => null,
                'matched_event_id' => $calendarEvent?->id,
            ];
        }

        return [
            'allowed' => true,
            'reason_code' => null,
            'reason_detail' => null,
            'schedule_id' => $schedule->id,
            'matched_event_id' => $calendarEvent?->id,
        ];
    }
}

<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceManualRecordStatus;
use App\Enums\DailyAttendancePhysicalStatus;
use App\Models\AcademicCalendarEvent;
use App\Models\AttendanceDayOverride;
use App\Models\AttendanceManualStatus;
use App\Models\AttendanceSite;
use App\Models\DailyAttendance;
use App\Models\SchoolYear;
use App\Models\User;
use Carbon\Carbon;

class DailyAttendanceEligibilityService
{
    public function __construct(
        private readonly DailyAttendanceWindowEvaluator $windowEvaluator,
        private readonly AttendanceDayOverrideResolver $overrideResolver,
        private readonly EffectiveDailyAttendancePolicyResolver $policyResolver,
    ) {}

    /**
     * @return array{
     *   allowed: bool,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   attendance_status: DailyAttendancePhysicalStatus|null,
     *   late_minutes: int|null,
     *   calendar_event_id: int|null,
     *   day_override: AttendanceDayOverride|null,
     *   effective_policy?: array<string, string>
     * }
     */
    public function evaluateCheckIn(User $user, Carbon $attendanceAt, int $attendanceSiteId): array
    {
        $common = $this->evaluateCommon($user, $attendanceAt, 'check_in', $attendanceSiteId);
        if (! $common['allowed']) {
            return $common;
        }

        $dayOverride = $common['day_override'] ?? null;
        if ($dayOverride && ! $dayOverride->allow_check_in) {
            return [
                'allowed' => false,
                'reason_code' => 'CHECK_IN_DISABLED_BY_OVERRIDE',
                'reason_detail' => 'Check-in dinonaktifkan oleh event override harian.',
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $common['calendar_event_id'],
                'day_override' => $dayOverride,
            ];
        }

        $window = $this->windowEvaluator->evaluateCheckIn($attendanceAt, $common['effective_policy']);
        if (! $window['allowed']) {
            return [
                'allowed' => false,
                'reason_code' => $window['reason_code'],
                'reason_detail' => $window['reason_detail'],
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $common['calendar_event_id'],
                'day_override' => $dayOverride,
            ];
        }

        return [
            'allowed' => true,
            'reason_code' => $window['reason_code'],
            'reason_detail' => $window['reason_detail'],
            'attendance_status' => $window['attendance_status'],
            'late_minutes' => $window['late_minutes'],
            'calendar_event_id' => $common['calendar_event_id'],
            'day_override' => $dayOverride,
        ];
    }

    /**
     * @return array{
     *   allowed: bool,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   calendar_event_id: int|null,
     *   day_override: AttendanceDayOverride|null,
     *   effective_policy?: array<string, string>
     * }
     */
    public function evaluateCheckOut(User $user, Carbon $attendanceAt, int $attendanceSiteId): array
    {
        $common = $this->evaluateCommon($user, $attendanceAt, 'check_out', $attendanceSiteId);
        if (! $common['allowed']) {
            return $common;
        }

        $dayOverride = $common['day_override'] ?? null;
        if ($dayOverride && ! $dayOverride->allow_check_out) {
            return [
                'allowed' => false,
                'reason_code' => 'CHECK_OUT_DISABLED_BY_OVERRIDE',
                'reason_detail' => 'Check-out dinonaktifkan oleh event override harian.',
                'calendar_event_id' => $common['calendar_event_id'],
                'day_override' => $dayOverride,
            ];
        }

        $window = $this->windowEvaluator->evaluateCheckOut($attendanceAt, $common['effective_policy']);
        if (! $window['allowed']) {
            return [
                'allowed' => false,
                'reason_code' => $window['reason_code'],
                'reason_detail' => $window['reason_detail'],
                'calendar_event_id' => $common['calendar_event_id'],
                'day_override' => $dayOverride,
            ];
        }

        return [
            'allowed' => true,
            'reason_code' => null,
            'reason_detail' => null,
            'calendar_event_id' => $common['calendar_event_id'],
            'day_override' => $dayOverride,
        ];
    }

    /**
     * @return array{
     *   allowed: bool,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   attendance_status: DailyAttendancePhysicalStatus|null,
     *   late_minutes: int|null,
     *   calendar_event_id: int|null,
     *   day_override: AttendanceDayOverride|null,
     *   effective_policy?: array<string, string>
     * }
     */
    private function evaluateCommon(User $user, Carbon $attendanceAt, string $phase, int $attendanceSiteId): array
    {
        $schoolTz = SchoolAttendanceTime::timezone();
        $attendanceAt = $attendanceAt->copy()->timezone($schoolTz);
        $dayOverride = $this->overrideResolver->resolveForDay($attendanceAt);

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
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $calendarEvent->id,
                'day_override' => $dayOverride,
            ];
        }

        if (! $user->hasRole('student')) {
            return [
                'allowed' => false,
                'reason_code' => 'PROFILE_NOT_FOUND',
                'reason_detail' => 'Hanya akun siswa yang dapat absensi harian.',
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $calendarEvent?->id,
                'day_override' => $dayOverride,
            ];
        }

        $studentProfile = $user->studentProfile;
        if (! $studentProfile) {
            return [
                'allowed' => false,
                'reason_code' => 'PROFILE_NOT_FOUND',
                'reason_detail' => 'Profil siswa belum tersedia.',
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $calendarEvent?->id,
                'day_override' => $dayOverride,
            ];
        }

        $activeSchoolYear = SchoolYear::active()->first();
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
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $calendarEvent?->id,
                'day_override' => $dayOverride,
            ];
        }

        $date = $attendanceAt->toDateString();
        $manualExists = AttendanceManualStatus::query()
            ->where('student_profile_id', $studentProfile->id)
            ->whereDate('date', $date)
            ->where('status', AttendanceManualRecordStatus::Approved)
            ->exists();

        if ($manualExists) {
            return [
                'allowed' => false,
                'reason_code' => 'MANUAL_STATUS_EXISTS',
                'reason_detail' => 'Sudah ada status manual untuk tanggal ini.',
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $calendarEvent?->id,
                'day_override' => $dayOverride,
            ];
        }

        $site = AttendanceSite::query()
            ->whereKey($attendanceSiteId)
            ->where('is_active', true)
            ->first();
        if (! $site) {
            return [
                'allowed' => false,
                'reason_code' => 'SITE_NOT_ACTIVE',
                'reason_detail' => 'Titik absensi tidak ditemukan atau sedang nonaktif.',
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $calendarEvent?->id,
                'day_override' => $dayOverride,
            ];
        }

        $resolvedPolicy = $this->policyResolver->resolveForSite($site, $dayOverride);

        $daily = DailyAttendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($phase === 'check_in' && $daily && $daily->check_in_at !== null) {
            return [
                'allowed' => false,
                'reason_code' => 'ALREADY_CHECKED_IN',
                'reason_detail' => 'Check-in untuk hari ini sudah tercatat.',
                'attendance_status' => null,
                'late_minutes' => null,
                'calendar_event_id' => $calendarEvent?->id,
                'day_override' => $dayOverride,
            ];
        }

        if ($phase === 'check_out') {
            if (! $daily || $daily->check_in_at === null) {
                return [
                    'allowed' => false,
                    'reason_code' => 'NO_CHECK_IN',
                    'reason_detail' => 'Belum ada check-in untuk hari ini.',
                    'calendar_event_id' => $calendarEvent?->id,
                    'day_override' => $dayOverride,
                ];
            }

            if ($daily->check_out_at !== null) {
                return [
                    'allowed' => false,
                    'reason_code' => 'ALREADY_CHECKED_OUT',
                    'reason_detail' => 'Check-out untuk hari ini sudah tercatat.',
                    'calendar_event_id' => $calendarEvent?->id,
                    'day_override' => $dayOverride,
                ];
            }
        }

        return [
            'allowed' => true,
            'reason_code' => null,
            'reason_detail' => null,
            'attendance_status' => null,
            'late_minutes' => null,
            'calendar_event_id' => $calendarEvent?->id,
            'day_override' => $dayOverride,
            'effective_policy' => $resolvedPolicy['policy'],
        ];
    }
}

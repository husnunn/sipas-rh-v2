<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceManualRecordStatus;
use App\Enums\AttendanceManualType;
use App\Enums\DailyAttendancePhysicalStatus;
use App\Enums\StudentDailyFinalStatus;
use App\Models\AcademicCalendarEvent;
use App\Models\AttendanceDayOverride;
use App\Models\AttendanceManualStatus;
use App\Models\AttendanceSite;
use App\Models\DailyAttendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class StudentDayFinalStatusService
{
    public function __construct(
        private readonly DailyAttendanceWindowEvaluator $windowEvaluator,
        private readonly AttendanceDayOverrideResolver $overrideResolver,
        private readonly EffectiveDailyAttendancePolicyResolver $policyResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildTodayPayload(User $user): array
    {
        $day = SchoolAttendanceTime::now()->copy()->startOfDay();

        return $this->buildPayloadForDay($user, $day, includeUiHints: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayloadForDay(User $user, Carbon $dayInSchoolTz, bool $includeUiHints = false): array
    {
        $day = $dayInSchoolTz->copy()->timezone(SchoolAttendanceTime::timezone())->startOfDay();
        $row = $this->finalRowForDay($user, $day);
        if (! $includeUiHints) {
            return $row;
        }

        $now = SchoolAttendanceTime::now();
        $dayOverride = $this->overrideResolver->resolveForDay($day);
        $preferredSite = null;
        if (isset($row['site']['id'])) {
            $preferredSite = AttendanceSite::query()->whereKey((int) $row['site']['id'])->first();
        }
        $resolvedPolicy = $this->policyResolver->resolveForDay($day, $preferredSite);
        $windowIn = $this->windowEvaluator->evaluateCheckIn($now, $resolvedPolicy['policy']);
        $windowOut = $this->windowEvaluator->evaluateCheckOut($now, $resolvedPolicy['policy']);

        $manualLike = [
            StudentDailyFinalStatus::Excused->value,
            StudentDailyFinalStatus::Sick->value,
            StudentDailyFinalStatus::Dispensation->value,
        ];

        $canCheckIn = $row['status'] !== StudentDailyFinalStatus::Holiday->value
            && ! in_array($row['status'], $manualLike, true)
            && ($row['source'] === 'absent' || ($row['source'] === 'daily_attendance' && $row['check_in_at'] === null))
            && ! ($dayOverride?->allow_check_in === false)
            && ($windowIn['allowed'] ?? false);

        $canCheckOut = $row['status'] !== StudentDailyFinalStatus::Holiday->value
            && ! in_array($row['status'], $manualLike, true)
            && $row['source'] === 'daily_attendance'
            && $row['check_in_at'] !== null
            && $row['check_out_at'] === null
            && ! ($dayOverride?->waive_check_out === true)
            && ! ($dayOverride?->allow_check_out === false)
            && ($windowOut['allowed'] ?? false);

        $row['can_check_in'] = $canCheckIn;
        $row['can_check_out'] = $canCheckOut;
        $row['override'] = $this->overridePayload($dayOverride);
        $row['effective_policy'] = $resolvedPolicy['policy'];
        $row['site'] = $resolvedPolicy['site'] !== null
            ? [
                'id' => $resolvedPolicy['site']->id,
                'name' => $resolvedPolicy['site']->name,
            ]
            : null;

        return $row;
    }

    /**
     * @return array{
     *   date: string,
     *   status: string,
     *   label: string,
     *   source: string,
     *   check_in_at: string|null,
     *   check_out_at: string|null,
     *   late_minutes: int|null,
     *   message?: string|null,
     *   site?: array{id: int, name: string}|null
     * }
     */
    public function finalRowForDay(User $user, Carbon $dayInSchoolTz): array
    {
        $tz = SchoolAttendanceTime::timezone();
        $day = $dayInSchoolTz->copy()->timezone($tz)->startOfDay();
        $dateStr = $day->toDateString();
        $dayOverride = $this->overrideResolver->resolveForDay($day);

        $blockingEvent = AcademicCalendarEvent::query()
            ->active()
            ->overlapsDate($day)
            ->where(function ($query): void {
                $query->where('allow_attendance', false)
                    ->orWhere('override_schedule', true);
            })
            ->orderByDesc('override_schedule')
            ->orderBy('start_date')
            ->first();

        if ($blockingEvent) {
            return [
                'date' => $dateStr,
                'status' => StudentDailyFinalStatus::Holiday->value,
                'label' => 'Libur',
                'source' => 'holiday',
                'check_in_at' => null,
                'check_out_at' => null,
                'late_minutes' => null,
                'message' => "Hari libur atau event: {$blockingEvent->name}.",
                'site' => null,
                'override' => $this->overridePayload($dayOverride),
            ];
        }

        $studentProfile = $user->studentProfile;
        $manual = null;
        if ($studentProfile) {
            $manual = AttendanceManualStatus::query()
                ->where('student_profile_id', $studentProfile->id)
                ->whereDate('date', $dateStr)
                ->where('status', AttendanceManualRecordStatus::Approved)
                ->first();
        }

        if ($manual !== null) {
            $final = $this->mapManualTypeToFinal($manual->type);
            $message = $manual->type === AttendanceManualType::Excused
                ? 'Anda telah diberi status izin oleh guru/admin.'
                : 'Anda telah diberi status oleh guru/admin.';

            return [
                'date' => $dateStr,
                'status' => $final->value,
                'label' => $this->labelFor($final),
                'source' => 'manual_status',
                'check_in_at' => null,
                'check_out_at' => null,
                'late_minutes' => null,
                'message' => $message,
                'site' => null,
                'override' => $this->overridePayload($dayOverride),
            ];
        }

        $daily = DailyAttendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $dateStr)
            ->with('attendanceSite:id,name')
            ->first();

        if ($daily && $daily->check_in_at !== null) {
            $final = $daily->status === DailyAttendancePhysicalStatus::Late
                ? StudentDailyFinalStatus::Late
                : StudentDailyFinalStatus::Present;

            return [
                'date' => $dateStr,
                'status' => $final->value,
                'label' => $this->labelFor($final),
                'source' => 'daily_attendance',
                'check_in_at' => $this->formatIsoSchoolTz($daily->check_in_at),
                'check_out_at' => $this->formatIsoSchoolTz($daily->check_out_at),
                'late_minutes' => $daily->late_minutes,
                'message' => $this->overrideMessage($dayOverride),
                'site' => $daily->attendanceSite !== null
                    ? [
                        'id' => $daily->attendanceSite->id,
                        'name' => $daily->attendanceSite->name,
                    ]
                    : null,
                'override' => $this->overridePayload($dayOverride),
            ];
        }

        return [
            'date' => $dateStr,
            'status' => StudentDailyFinalStatus::Absent->value,
            'label' => $this->labelFor(StudentDailyFinalStatus::Absent),
            'source' => 'absent',
            'check_in_at' => null,
            'check_out_at' => null,
            'late_minutes' => null,
            'message' => $this->overrideMessage($dayOverride),
            'site' => null,
            'override' => $this->overridePayload($dayOverride),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildSummaryForDateRange(User $user, Carbon $fromDay, Carbon $toDay): array
    {
        $tz = SchoolAttendanceTime::timezone();
        $cursor = $fromDay->copy()->timezone($tz)->startOfDay();
        $end = $toDay->copy()->timezone($tz)->startOfDay();
        $rows = [];

        while ($cursor->lte($end)) {
            $rows[] = $this->finalRowForDay($user, $cursor);
            $cursor->addDay();
        }

        return $rows;
    }

    public function labelFor(StudentDailyFinalStatus $status): string
    {
        return match ($status) {
            StudentDailyFinalStatus::Present => 'Hadir',
            StudentDailyFinalStatus::Late => 'Terlambat',
            StudentDailyFinalStatus::Excused => 'Izin',
            StudentDailyFinalStatus::Sick => 'Sakit',
            StudentDailyFinalStatus::Dispensation => 'Dispensasi',
            StudentDailyFinalStatus::Absent => 'Alpa',
            StudentDailyFinalStatus::Holiday => 'Libur',
        };
    }

    private function mapManualTypeToFinal(AttendanceManualType $type): StudentDailyFinalStatus
    {
        return match ($type) {
            AttendanceManualType::Excused => StudentDailyFinalStatus::Excused,
            AttendanceManualType::Sick => StudentDailyFinalStatus::Sick,
            AttendanceManualType::Dispensation => StudentDailyFinalStatus::Dispensation,
        };
    }

    private function formatIsoSchoolTz(?CarbonInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::instance($value)
            ->timezone(SchoolAttendanceTime::timezone())
            ->toIso8601String();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function overridePayload(?AttendanceDayOverride $override): ?array
    {
        if (! $override) {
            return null;
        }

        return [
            'active' => true,
            'id' => $override->id,
            'name' => $override->name,
            'event_type' => $override->event_type,
            'dismiss_students_early' => $override->dismiss_students_early,
            'waive_check_out' => $override->waive_check_out,
            'allow_check_in' => $override->allow_check_in,
            'allow_check_out' => $override->allow_check_out,
        ];
    }

    private function overrideMessage(?AttendanceDayOverride $override): ?string
    {
        if (! $override) {
            return null;
        }

        if ($override->waive_check_out) {
            return 'Hari ini check-out manual tidak diwajibkan.';
        }

        if ($override->dismiss_students_early && $override->check_out_open_at) {
            return "Hari ini siswa dipulangkan lebih awal. Check-out dibuka pukul {$override->check_out_open_at}.";
        }

        return null;
    }
}

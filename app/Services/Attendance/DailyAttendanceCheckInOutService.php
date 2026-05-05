<?php

namespace App\Services\Attendance;

use App\Enums\DailyAttendancePhysicalStatus;
use App\Models\DailyAttendance;
use App\Models\User;

class DailyAttendanceCheckInOutService
{
    public function __construct(
        private readonly DailyAttendanceEligibilityService $eligibilityService,
        private readonly AttendanceEvidenceValidationService $evidenceValidationService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   status: string,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   attendance_status: DailyAttendancePhysicalStatus|null,
     *   late_minutes: int|null,
     *   record: DailyAttendance|null,
     *   validation: array<string, mixed>
     * }
     */
    public function checkIn(User $user, array $payload): array
    {
        $attendanceAt = SchoolAttendanceTime::resolveAttendanceAt($payload['client_time'] ?? null);
        $siteId = (int) $payload['attendance_site_id'];
        $eligibility = $this->eligibilityService->evaluateCheckIn($user, $attendanceAt, $siteId);
        $evidence = $this->evidenceValidationService->validate(
            $siteId,
            $payload['network'],
            $payload['location'],
        );

        $validation = [
            'eligibility' => $this->sanitizeEligibilityForResponse($eligibility),
            'evidence' => $evidence,
        ];

        $approved = ($eligibility['allowed'] ?? false) && ($evidence['valid'] ?? false);
        if (! $approved) {
            $reasonCode = $eligibility['reason_code'] ?? $evidence['reason_code'] ?? null;
            $reasonDetail = $eligibility['reason_detail'] ?? $evidence['reason_detail'] ?? null;

            return [
                'status' => 'rejected',
                'reason_code' => $reasonCode,
                'reason_detail' => $reasonDetail,
                'attendance_status' => null,
                'late_minutes' => null,
                'record' => null,
                'validation' => $validation,
            ];
        }

        $schoolTz = SchoolAttendanceTime::timezone();
        $date = $attendanceAt->copy()->timezone($schoolTz)->toDateString();
        $studentProfile = $user->studentProfile;
        if (! $studentProfile) {
            return [
                'status' => 'rejected',
                'reason_code' => 'PROFILE_NOT_FOUND',
                'reason_detail' => 'Profil siswa belum tersedia.',
                'attendance_status' => null,
                'late_minutes' => null,
                'record' => null,
                'validation' => $validation,
            ];
        }

        /** @var DailyAttendancePhysicalStatus $physicalStatus */
        $physicalStatus = $eligibility['attendance_status'];
        $lateMinutes = $eligibility['late_minutes'] ?? 0;

        $record = DailyAttendance::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $date,
            ],
            [
                'student_profile_id' => $studentProfile->id,
                'attendance_site_id' => $siteId,
                'check_in_at' => $attendanceAt,
                'status' => $physicalStatus,
                'late_minutes' => $lateMinutes,
                'check_in_reason_code' => $eligibility['reason_code'],
                'check_in_reason_detail' => $eligibility['reason_detail'],
                'network_payload' => $payload['network'] ?? null,
                'location_payload' => $payload['location'] ?? null,
                'device_payload' => $payload['device'] ?? null,
            ],
        );

        return [
            'status' => 'approved',
            'reason_code' => $eligibility['reason_code'],
            'reason_detail' => $eligibility['reason_detail'],
            'attendance_status' => $physicalStatus,
            'late_minutes' => $lateMinutes,
            'record' => $record->fresh(['attendanceSite']),
            'validation' => $validation,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   status: string,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   record: DailyAttendance|null,
     *   validation: array<string, mixed>
     * }
     */
    public function checkOut(User $user, array $payload): array
    {
        $attendanceAt = SchoolAttendanceTime::resolveAttendanceAt($payload['client_time'] ?? null);
        $siteId = (int) $payload['attendance_site_id'];
        $eligibility = $this->eligibilityService->evaluateCheckOut($user, $attendanceAt, $siteId);
        $evidence = $this->evidenceValidationService->validate(
            $siteId,
            $payload['network'],
            $payload['location'],
        );

        $validation = [
            'eligibility' => $this->sanitizeEligibilityForResponse($eligibility),
            'evidence' => $evidence,
        ];

        $approved = ($eligibility['allowed'] ?? false) && ($evidence['valid'] ?? false);
        if (! $approved) {
            $reasonCode = $eligibility['reason_code'] ?? $evidence['reason_code'] ?? null;
            $reasonDetail = $eligibility['reason_detail'] ?? $evidence['reason_detail'] ?? null;

            return [
                'status' => 'rejected',
                'reason_code' => $reasonCode,
                'reason_detail' => $reasonDetail,
                'record' => null,
                'validation' => $validation,
            ];
        }

        $schoolTz = SchoolAttendanceTime::timezone();
        $date = $attendanceAt->copy()->timezone($schoolTz)->toDateString();
        $record = DailyAttendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if (! $record) {
            return [
                'status' => 'rejected',
                'reason_code' => 'NO_CHECK_IN',
                'reason_detail' => 'Belum ada check-in untuk hari ini.',
                'record' => null,
                'validation' => $validation,
            ];
        }

        $record->update([
            'check_out_at' => $attendanceAt,
            'network_payload' => $payload['network'] ?? $record->network_payload,
            'location_payload' => $payload['location'] ?? $record->location_payload,
            'device_payload' => $payload['device'] ?? $record->device_payload,
        ]);

        return [
            'status' => 'approved',
            'reason_code' => null,
            'reason_detail' => null,
            'record' => $record->fresh(['attendanceSite']),
            'validation' => $validation,
        ];
    }

    /**
     * @param  array<string, mixed>  $eligibility
     * @return array<string, mixed>
     */
    private function sanitizeEligibilityForResponse(array $eligibility): array
    {
        unset($eligibility['day_override']);

        return $eligibility;
    }
}

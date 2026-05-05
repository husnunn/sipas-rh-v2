<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\AttendanceValidationLog;
use App\Models\User;
use Carbon\Carbon;

class AttendanceDecisionService
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $eligibility
     * @param  array<string, mixed>  $evidence
     * @return array{
     *   status: string,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   record: AttendanceRecord
     * }
     */
    public function decideAndStore(
        User $user,
        array $payload,
        array $eligibility,
        array $evidence,
        Carbon $attendanceAt
    ): array {
        $status = ($eligibility['allowed'] ?? false) && ($evidence['valid'] ?? false) ? 'approved' : 'rejected';
        $reasonCode = $eligibility['reason_code'] ?? $evidence['reason_code'] ?? null;
        $reasonDetail = $eligibility['reason_detail'] ?? $evidence['reason_detail'] ?? null;

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_site_id' => $payload['attendance_site_id'],
            'schedule_id' => $eligibility['schedule_id'] ?? null,
            'attendance_type' => $payload['attendance_type'],
            'status' => $status,
            'attendance_at' => $attendanceAt,
            'client_time' => $payload['client_time'] ?? null,
            'reason_code' => $reasonCode,
            'reason_detail' => $reasonDetail,
            'distance_m' => $evidence['distance_m'] ?? null,
            'network_payload' => $payload['network'] ?? null,
            'location_payload' => $payload['location'] ?? null,
        ]);

        AttendanceValidationLog::create([
            'attendance_record_id' => $record->id,
            'user_id' => $user->id,
            'status' => $status,
            'reason_code' => $reasonCode,
            'details' => [
                'eligibility' => $eligibility,
                'evidence' => $evidence,
                'device' => $payload['device'] ?? null,
            ],
        ]);

        return [
            'status' => $status,
            'reason_code' => $reasonCode,
            'reason_detail' => $reasonDetail,
            'record' => $record,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Attendance\StoreAttendanceRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceDecisionService;
use App\Services\Attendance\AttendanceEligibilityService;
use App\Services\Attendance\AttendanceEvidenceValidationService;
use App\Services\Attendance\MobileAttendanceSitePicker;
use App\Services\Attendance\SchoolAttendanceTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherAttendanceController extends Controller
{
    public function checkIn(
        StoreAttendanceRequest $request,
        AttendanceEligibilityService $eligibilityService,
        AttendanceEvidenceValidationService $evidenceValidationService,
        AttendanceDecisionService $decisionService,
    ): JsonResponse {
        return $this->handle(
            $request,
            'check_in',
            $eligibilityService,
            $evidenceValidationService,
            $decisionService,
        );
    }

    public function checkOut(
        StoreAttendanceRequest $request,
        AttendanceEligibilityService $eligibilityService,
        AttendanceEvidenceValidationService $evidenceValidationService,
        AttendanceDecisionService $decisionService,
    ): JsonResponse {
        return $this->handle(
            $request,
            'check_out',
            $eligibilityService,
            $evidenceValidationService,
            $decisionService,
        );
    }

    public function today(Request $request): JsonResponse
    {
        $dayStart = SchoolAttendanceTime::now()->copy()->startOfDay();
        $dayEnd = SchoolAttendanceTime::now()->copy()->endOfDay();

        $records = AttendanceRecord::query()
            ->where('user_id', $request->user()->id)
            ->whereBetween('attendance_at', [$dayStart, $dayEnd])
            ->with('attendanceSite')
            ->orderByDesc('attendance_at')
            ->get();

        return response()->json([
            'data' => AttendanceRecordResource::collection($records),
            'attendance_sites' => MobileAttendanceSitePicker::forTeacher(),
        ]);
    }

    private function handle(
        StoreAttendanceRequest $request,
        string $expectedType,
        AttendanceEligibilityService $eligibilityService,
        AttendanceEvidenceValidationService $evidenceValidationService,
        AttendanceDecisionService $decisionService
    ): JsonResponse {
        $payload = $request->validated();
        $payload['attendance_type'] = $expectedType;

        $attendanceAt = SchoolAttendanceTime::resolveAttendanceAt($payload['client_time'] ?? null);
        $eligibility = $eligibilityService->evaluate($request->user(), $attendanceAt, $expectedType);
        $evidence = $evidenceValidationService->validate(
            (int) $payload['attendance_site_id'],
            $payload['network'],
            $payload['location'],
        );

        $decision = $decisionService->decideAndStore(
            $request->user(),
            $payload,
            $eligibility,
            $evidence,
            $attendanceAt,
        );

        /** @var AttendanceRecord $record */
        $record = $decision['record']->load('attendanceSite');

        return response()->json([
            'message' => $decision['status'] === 'approved'
                ? 'Absensi berhasil disetujui.'
                : 'Absensi ditolak oleh sistem validasi.',
            'status' => $decision['status'],
            'reason_code' => $decision['reason_code'],
            'reason_detail' => $decision['reason_detail'],
            'validation' => [
                'eligibility' => $eligibility,
                'evidence' => $evidence,
            ],
            'record' => new AttendanceRecordResource($record),
        ], $decision['status'] === 'approved' ? 200 : 422);
    }
}

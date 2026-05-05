<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Attendance\StoreDailyAttendanceRequest;
use App\Http\Resources\AttendanceSitePickerApiResource;
use App\Http\Resources\DailyAttendanceRecordApiResource;
use App\Models\AttendanceSite;
use App\Services\Attendance\DailyAttendanceCheckInOutService;
use App\Services\Attendance\StudentDayFinalStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Student daily school attendance (not schedule-based). Contract: spec/AbsenBaru.md §8.
 *
 * Routes: POST /api/v1/student/daily-attendance/check-in|check-out, GET .../today
 */
class StudentDailyAttendanceController extends Controller
{
    public function checkIn(
        StoreDailyAttendanceRequest $request,
        DailyAttendanceCheckInOutService $service,
    ): JsonResponse {
        $payload = $request->validated();
        $result = $service->checkIn($request->user(), $payload);

        if ($result['status'] !== 'approved') {
            return response()->json([
                'message' => 'Absensi ditolak oleh sistem validasi.',
                'status' => $result['status'],
                'reason_code' => $result['reason_code'],
                'reason_detail' => $result['reason_detail'],
                'validation' => $result['validation'],
            ], 422);
        }

        $physical = $result['attendance_status']?->value;
        $message = $physical === 'late'
            ? 'Absensi masuk berhasil, tercatat terlambat.'
            : 'Absensi masuk berhasil.';

        return response()->json([
            'message' => $message,
            'status' => 'approved',
            'attendance_status' => $physical,
            'late_minutes' => $result['late_minutes'] ?? 0,
            'reason_code' => $result['reason_code'],
            'reason_detail' => $result['reason_detail'],
            'record' => new DailyAttendanceRecordApiResource($result['record']),
        ]);
    }

    public function checkOut(
        StoreDailyAttendanceRequest $request,
        DailyAttendanceCheckInOutService $service,
    ): JsonResponse {
        $payload = $request->validated();
        $result = $service->checkOut($request->user(), $payload);

        if ($result['status'] !== 'approved') {
            return response()->json([
                'message' => 'Absensi ditolak oleh sistem validasi.',
                'status' => $result['status'],
                'reason_code' => $result['reason_code'],
                'reason_detail' => $result['reason_detail'],
                'validation' => $result['validation'],
            ], 422);
        }

        return response()->json([
            'message' => 'Absensi pulang berhasil.',
            'status' => 'approved',
            'record' => new DailyAttendanceRecordApiResource($result['record']),
        ]);
    }

    public function today(Request $request, StudentDayFinalStatusService $finalStatusService): JsonResponse
    {
        $data = $finalStatusService->buildTodayPayload($request->user());
        $sites = AttendanceSite::query()
            ->forApiPicker()
            ->get(['id', 'name', 'latitude', 'longitude', 'radius_m']);

        return response()->json([
            'data' => $data,
            'attendance_sites' => AttendanceSitePickerApiResource::collection($sites)->resolve(),
        ]);
    }
}

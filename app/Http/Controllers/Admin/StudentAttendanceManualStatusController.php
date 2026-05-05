<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceManualRecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceManualStatusRequest;
use App\Http\Requests\Admin\UpdateAttendanceManualStatusRequest;
use App\Models\AttendanceManualStatus;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentAttendanceManualStatusController extends Controller
{
    public function store(StoreAttendanceManualStatusRequest $request, StudentProfile $student): RedirectResponse
    {
        $this->assertNoApprovedConflict($student, (string) $request->validated('date'));

        AttendanceManualStatus::create([
            'user_id' => $student->user_id,
            'student_profile_id' => $student->id,
            'attendance_site_id' => $request->validated('attendance_site_id'),
            'date' => $request->validated('date'),
            'type' => $request->validated('type'),
            'reason' => $request->validated('reason'),
            'notes' => $request->validated('notes'),
            'status' => AttendanceManualRecordStatus::Approved,
            'created_by' => $request->user()->id,
            'updated_by' => null,
        ]);

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Status manual absensi berhasil ditambahkan.']);
    }

    public function update(
        UpdateAttendanceManualStatusRequest $request,
        StudentProfile $student,
        AttendanceManualStatus $manualStatus,
    ): RedirectResponse {
        $this->assertBelongsToStudent($student, $manualStatus);
        if ($manualStatus->status !== AttendanceManualRecordStatus::Approved) {
            throw ValidationException::withMessages([
                'manual_status' => ['Hanya status yang masih disetujui yang dapat diubah.'],
            ]);
        }

        $validated = $request->validated();
        if (array_key_exists('date', $validated)) {
            $this->assertNoApprovedConflict($student, (string) $validated['date'], ignoreId: $manualStatus->id);
        }

        $manualStatus->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Status manual absensi berhasil diperbarui.']);
    }

    public function cancel(
        Request $request,
        StudentProfile $student,
        AttendanceManualStatus $manualStatus,
    ): RedirectResponse {
        $this->assertBelongsToStudent($student, $manualStatus);
        if ($manualStatus->status !== AttendanceManualRecordStatus::Approved) {
            return redirect()->back()
                ->with('flash', ['type' => 'info', 'message' => 'Status sudah dibatalkan sebelumnya.']);
        }

        $manualStatus->update([
            'status' => AttendanceManualRecordStatus::Cancelled,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Status manual absensi dibatalkan.']);
    }

    private function assertBelongsToStudent(StudentProfile $student, AttendanceManualStatus $manualStatus): void
    {
        abort_unless($manualStatus->student_profile_id === $student->id, 404);
    }

    private function assertNoApprovedConflict(StudentProfile $student, string $date, ?int $ignoreId = null): void
    {
        $query = AttendanceManualStatus::query()
            ->where('student_profile_id', $student->id)
            ->whereDate('date', $date)
            ->where('status', AttendanceManualRecordStatus::Approved);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'date' => ['Sudah ada status manual disetujui untuk tanggal ini.'],
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceDayOverrideRequest;
use App\Http\Requests\Admin\UpdateAttendanceDayOverrideRequest;
use App\Models\AttendanceDayOverride;
use App\Models\AttendanceSite;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceDayOverrideController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $eventType = $request->string('event_type')->toString();

        return Inertia::render('Admin/AttendanceDayOverrides/Index', [
            'overrides' => AttendanceDayOverride::query()
                ->with('attendanceSite:id,name')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status): void {
                    $query->where('is_active', $status === 'active');
                })
                ->when(in_array($eventType, $this->eventTypes(), true), function ($query) use ($eventType): void {
                    $query->where('event_type', $eventType);
                })
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => in_array($status, ['active', 'inactive'], true) ? $status : '',
                'event_type' => in_array($eventType, $this->eventTypes(), true) ? $eventType : '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AttendanceDayOverrides/Form', [
            'mode' => 'create',
            'override' => null,
            'eventTypes' => $this->eventTypes(),
            'attendanceSites' => $this->attendanceSites(),
        ]);
    }

    public function store(StoreAttendanceDayOverrideRequest $request): RedirectResponse
    {
        AttendanceDayOverride::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => null,
        ]);

        return redirect()->route('admin.attendance-day-overrides.index')
            ->with('flash', ['type' => 'success', 'message' => 'Override absensi harian berhasil ditambahkan.']);
    }

    public function show(AttendanceDayOverride $attendanceDayOverride): Response
    {
        $attendanceDayOverride->load('attendanceSite:id,name', 'createdByUser:id,name', 'updatedByUser:id,name');

        return Inertia::render('Admin/AttendanceDayOverrides/Show', [
            'override' => $attendanceDayOverride,
        ]);
    }

    public function edit(AttendanceDayOverride $attendanceDayOverride): Response
    {
        return Inertia::render('Admin/AttendanceDayOverrides/Form', [
            'mode' => 'edit',
            'override' => $attendanceDayOverride,
            'eventTypes' => $this->eventTypes(),
            'attendanceSites' => $this->attendanceSites(),
        ]);
    }

    public function update(UpdateAttendanceDayOverrideRequest $request, AttendanceDayOverride $attendanceDayOverride): RedirectResponse
    {
        $attendanceDayOverride->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.attendance-day-overrides.index')
            ->with('flash', ['type' => 'success', 'message' => 'Override absensi harian berhasil diperbarui.']);
    }

    public function destroy(AttendanceDayOverride $attendanceDayOverride): RedirectResponse
    {
        $attendanceDayOverride->delete();

        return redirect()->route('admin.attendance-day-overrides.index')
            ->with('flash', ['type' => 'success', 'message' => 'Override absensi harian berhasil dihapus.']);
    }

    public function toggleActive(AttendanceDayOverride $attendanceDayOverride): RedirectResponse
    {
        $attendanceDayOverride->update([
            'is_active' => ! $attendanceDayOverride->is_active,
        ]);

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Status aktif override diperbarui.']);
    }

    public function cancel(AttendanceDayOverride $attendanceDayOverride): RedirectResponse
    {
        $attendanceDayOverride->update([
            'is_active' => false,
        ]);

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Override berhasil dinonaktifkan.']);
    }

    /**
     * @return list<string>
     */
    private function eventTypes(): array
    {
        return [
            'early_dismissal',
            'teacher_meeting',
            'special_event',
            'holiday_override',
            'attendance_closed',
            'custom',
        ];
    }

    /**
     * @return Collection<int, AttendanceSite>
     */
    private function attendanceSites()
    {
        return AttendanceSite::query()->orderBy('name')->get(['id', 'name']);
    }
}

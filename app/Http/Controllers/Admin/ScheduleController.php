<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(): Response
    {
        $activeSchoolYear = SchoolYear::active()->first();

        $schedules = Schedule::query()
            ->when($activeSchoolYear, fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->with(['classRoom', 'subject', 'teacherProfile', 'schoolYear'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(25);

        return Inertia::render('Admin/Schedules/Index', [
            'schedules' => $schedules,
            'activeSchoolYear' => $activeSchoolYear,
            'days' => Schedule::DAYS,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Schedules/Form', [
            'mode' => 'create',
            'schedule' => null,
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
            'classes' => ClassRoom::active()->with('schoolYear')->get(),
            'subjects' => Subject::active()->get(),
            'teachers' => TeacherProfile::with('user', 'subjects')->get(),
            'days' => Schedule::DAYS,
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        Schedule::create($request->validated());

        return redirect()->route('admin.schedules.index')
            ->with('flash', ['type' => 'success', 'message' => 'Jadwal berhasil ditambahkan.']);
    }

    public function show(Schedule $schedule): Response
    {
        $schedule->load(['classRoom', 'subject', 'teacherProfile', 'schoolYear']);

        return Inertia::render('Admin/Schedules/Show', [
            'schedule' => $schedule,
            'days' => Schedule::DAYS,
        ]);
    }

    public function edit(Schedule $schedule): Response
    {
        $schedule->load(['classRoom', 'subject', 'teacherProfile', 'schoolYear']);

        return Inertia::render('Admin/Schedules/Form', [
            'mode' => 'edit',
            'schedule' => $schedule,
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
            'classes' => ClassRoom::active()->with('schoolYear')->get(),
            'subjects' => Subject::active()->get(),
            'teachers' => TeacherProfile::with('user', 'subjects')->get(),
            'days' => Schedule::DAYS,
        ]);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $schedule->update($request->validated());

        return redirect()->route('admin.schedules.index')
            ->with('flash', ['type' => 'success', 'message' => 'Jadwal berhasil diperbarui.']);
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')
            ->with('flash', ['type' => 'success', 'message' => 'Jadwal berhasil dihapus.']);
    }
}

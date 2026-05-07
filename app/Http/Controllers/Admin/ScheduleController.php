<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesAdminDeletes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    use HandlesAdminDeletes;

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'teacher_profile_id' => ['nullable', 'integer', 'exists:teacher_profiles,id'],
            'day_of_week' => ['nullable', 'integer', 'between:1,6'],
        ]);

        $activeSchoolYear = SchoolYear::active()->first();
        $effectiveSchoolYearId = isset($filters['school_year_id'])
            ? (int) $filters['school_year_id']
            : ($activeSchoolYear?->id);

        $search = trim((string) ($filters['search'] ?? ''));

        $schedules = Schedule::query()
            ->when($effectiveSchoolYearId, fn ($q) => $q->where('school_year_id', $effectiveSchoolYearId))
            ->when(! empty($filters['class_id']), fn ($q) => $q->where('class_id', (int) $filters['class_id']))
            ->when(! empty($filters['subject_id']), fn ($q) => $q->where('subject_id', (int) $filters['subject_id']))
            ->when(! empty($filters['teacher_profile_id']), fn ($q) => $q->where('teacher_profile_id', (int) $filters['teacher_profile_id']))
            ->when(! empty($filters['day_of_week']), fn ($q) => $q->where('day_of_week', (int) $filters['day_of_week']))
            ->when($search !== '', function ($q) use ($search): void {
                $needle = '%'.$search.'%';

                $q->where(function ($q) use ($needle): void {
                    $q->where('room', 'like', $needle)
                        ->orWhere('notes', 'like', $needle)
                        ->orWhereHas('classRoom', fn ($rel) => $rel->where('name', 'like', $needle))
                        ->orWhereHas('subject', fn ($rel) => $rel->where('name', 'like', $needle)->orWhere('code', 'like', $needle))
                        ->orWhereHas('teacherProfile', fn ($rel) => $rel->where('full_name', 'like', $needle));
                });
            })
            ->with(['classRoom:id,name,school_year_id', 'subject:id,code,name', 'teacherProfile:id,full_name', 'schoolYear:id,name,is_active'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Schedules/Index', [
            'schedules' => $schedules,
            'activeSchoolYear' => $activeSchoolYear,
            'days' => Schedule::DAYS,
            'filters' => [
                'search' => $search,
                'school_year_id' => $effectiveSchoolYearId,
                'class_id' => isset($filters['class_id']) ? (int) $filters['class_id'] : null,
                'subject_id' => isset($filters['subject_id']) ? (int) $filters['subject_id'] : null,
                'teacher_profile_id' => isset($filters['teacher_profile_id']) ? (int) $filters['teacher_profile_id'] : null,
                'day_of_week' => isset($filters['day_of_week']) ? (int) $filters['day_of_week'] : null,
            ],
            'schoolYears' => SchoolYear::orderByDesc('id')->get(['id', 'name', 'is_active']),
            'classes' => ClassRoom::active()->orderBy('name')->get(['id', 'name', 'school_year_id']),
            'subjects' => Subject::active()->orderBy('name')->get(['id', 'code', 'name']),
            'teachers' => TeacherProfile::orderBy('full_name')->get(['id', 'full_name']),
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
        return $this->tryDelete(
            function () use ($schedule): void {
                $schedule->delete();
            },
            'admin.schedules.index',
            ['type' => 'success', 'message' => 'Jadwal berhasil dihapus.'],
            'Gagal menghapus jadwal. Silakan coba lagi.',
        );
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:schedules,id'],
        ]);

        return $this->tryDelete(
            function () use ($validated): void {
                Schedule::whereIn('id', $validated['ids'])->delete();
            },
            'admin.schedules.index',
            ['type' => 'success', 'message' => count($validated['ids']).' jadwal berhasil dihapus.'],
            'Gagal menghapus jadwal. Silakan coba lagi.',
        );
    }
}

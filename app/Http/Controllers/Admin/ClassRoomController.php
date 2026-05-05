<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesAdminDeletes;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\SchoolYear;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassRoomController extends Controller
{
    use HandlesAdminDeletes;

    public function index(): Response
    {
        $classes = ClassRoom::query()
            ->with(['schoolYear', 'homeroomTeacher.user'])
            ->orderByDesc('id')
            ->paginate(25);

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $classes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Classes/Form', [
            'mode' => 'create',
            'classRoom' => null,
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
            'teachers' => TeacherProfile::with('user')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'name' => ['required', 'string', 'max:20'],
            'level' => ['required', 'integer', 'min:1', 'max:12'],
            'homeroom_teacher_id' => ['nullable', 'exists:teacher_profiles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ClassRoom::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.classes.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data kelas berhasil ditambahkan.']);
    }

    public function show(ClassRoom $class): Response
    {
        $class->load(['schoolYear', 'homeroomTeacher.user', 'students.user']);

        return Inertia::render('Admin/Classes/Show', [
            'classRoom' => $class,
        ]);
    }

    public function edit(ClassRoom $class): Response
    {
        $class->load(['schoolYear', 'homeroomTeacher.user']);

        return Inertia::render('Admin/Classes/Form', [
            'mode' => 'edit',
            'classRoom' => $class,
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
            'teachers' => TeacherProfile::with('user')->orderByDesc('id')->get(),
        ]);
    }

    public function update(Request $request, ClassRoom $class): RedirectResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'name' => ['required', 'string', 'max:20'],
            'level' => ['required', 'integer', 'min:1', 'max:12'],
            'homeroom_teacher_id' => ['nullable', 'exists:teacher_profiles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $class->update([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.classes.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data kelas berhasil diperbarui.']);
    }

    public function destroy(ClassRoom $class): RedirectResponse
    {
        return $this->tryDelete(
            function () use ($class): void {
                $class->delete();
            },
            'admin.classes.index',
            ['type' => 'success', 'message' => 'Data kelas berhasil dihapus.'],
            'Gagal menghapus data kelas. Silakan coba lagi.',
        );
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:classes,id'],
        ]);

        return $this->tryDelete(
            function () use ($validated): void {
                ClassRoom::whereIn('id', $validated['ids'])->delete();
            },
            'admin.classes.index',
            ['type' => 'success', 'message' => count($validated['ids']).' data kelas berhasil dihapus.'],
            'Gagal menghapus data kelas. Silakan coba lagi.',
        );
    }
}

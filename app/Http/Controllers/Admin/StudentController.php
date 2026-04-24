<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(): Response
    {
        $students = StudentProfile::query()
            ->with(['user', 'classes'])
            ->orderByDesc('id')
            ->paginate(25);

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Students/Form', [
            'mode' => 'create',
            'student' => null,
            'classes' => ClassRoom::active()->orderBy('name')->get(),
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'nis' => ['required', 'string', 'max:20', 'unique:student_profiles,nis'],
            'nisn' => ['nullable', 'string', 'max:20', 'unique:student_profiles,nisn'],
            'gender' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'school_year_id' => ['nullable', 'exists:school_years,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password'] ?? $validated['nis']),
                'roles' => ['student'],
            ]);

            $student = StudentProfile::create([
                'user_id' => $user->id,
                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?? null,
                'full_name' => $validated['name'],
                'gender' => $validated['gender'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'birth_place' => $validated['birth_place'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'parent_name' => $validated['parent_name'] ?? null,
                'parent_phone' => $validated['parent_phone'] ?? null,
            ]);

            if (! empty($validated['class_id']) && ! empty($validated['school_year_id'])) {
                $student->classes()->attach($validated['class_id'], [
                    'school_year_id' => $validated['school_year_id'],
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('admin.students.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data siswa berhasil ditambahkan.']);
    }

    public function show(StudentProfile $student): Response
    {
        $student->load(['user', 'classes.schoolYear']);

        return Inertia::render('Admin/Students/Show', [
            'student' => $student,
        ]);
    }

    public function edit(StudentProfile $student): Response
    {
        $student->load(['user', 'classes']);

        return Inertia::render('Admin/Students/Form', [
            'mode' => 'edit',
            'student' => $student,
            'classes' => ClassRoom::active()->orderBy('name')->get(),
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
        ]);
    }

    public function update(Request $request, StudentProfile $student): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $student->user_id],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $student->user_id],
            'password' => ['nullable', 'string', 'min:6'],
            'nis' => ['required', 'string', 'max:20', 'unique:student_profiles,nis,' . $student->id],
            'nisn' => ['nullable', 'string', 'max:20', 'unique:student_profiles,nisn,' . $student->id],
            'gender' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'school_year_id' => ['nullable', 'exists:school_years,id'],
        ]);

        DB::transaction(function () use ($student, $validated): void {
            $student->user->update([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
            ]);

            $student->update([
                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?? null,
                'full_name' => $validated['name'],
                'gender' => $validated['gender'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'birth_place' => $validated['birth_place'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'parent_name' => $validated['parent_name'] ?? null,
                'parent_phone' => $validated['parent_phone'] ?? null,
            ]);

            if (! empty($validated['class_id']) && ! empty($validated['school_year_id'])) {
                $student->classes()->sync([
                    $validated['class_id'] => [
                        'school_year_id' => $validated['school_year_id'],
                        'is_active' => true,
                    ],
                ]);
            }
        });

        return redirect()->route('admin.students.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data siswa berhasil diperbarui.']);
    }

    public function destroy(StudentProfile $student): RedirectResponse
    {
        $student->user()->delete();

        return redirect()->route('admin.students.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data siswa berhasil dihapus.']);
    }
}

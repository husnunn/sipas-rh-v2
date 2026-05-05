<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesAdminDeletes;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    use HandlesAdminDeletes;

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'account_status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));

        $query = TeacherProfile::query()
            ->with(['user', 'subjects'])
            ->when($search !== '', function (Builder $q) use ($search): void {
                $needle = '%'.$search.'%';
                $q->where(function (Builder $q) use ($needle): void {
                    $q->where('nip', 'like', $needle)
                        ->orWhere('full_name', 'like', $needle)
                        ->orWhereHas('user', fn (Builder $uq) => $uq->where('name', 'like', $needle)
                            ->orWhere('username', 'like', $needle));
                });
            })
            ->when(($filters['account_status'] ?? '') === 'active', function (Builder $q): void {
                $q->whereHas('user', fn (Builder $uq) => $uq->where('is_active', true));
            })
            ->when(($filters['account_status'] ?? '') === 'inactive', function (Builder $q): void {
                $q->whereHas('user', fn (Builder $uq) => $uq->where('is_active', false));
            })
            ->orderByDesc('id');

        return Inertia::render('Admin/Teachers/Index', [
            'teachers' => $query->paginate(25)->withQueryString(),
            'filters' => [
                'search' => $search,
                'account_status' => $filters['account_status'] ?? null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Teachers/Form', [
            'mode' => 'create',
            'teacher' => null,
            'subjects' => Subject::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'nip' => ['nullable', 'string', 'max:30', 'unique:teacher_profiles,nip'],
            'gender' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => [
                'exists:subjects,id',
                function ($attribute, $value, $fail) {
                    $count = DB::table('teacher_subjects')
                        ->where('subject_id', $value)
                        ->count();
                    if ($count >= 2) {
                        $subject = Subject::find($value);
                        $fail("Mata pelajaran {$subject->name} sudah maksimal diajar oleh 2 guru.");
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password'] ?? ($validated['nip'] ?? $validated['username'])),
                'roles' => ['teacher'],
            ]);

            $teacher = TeacherProfile::create([
                'user_id' => $user->id,
                'nip' => $validated['nip'] ?? null,
                'full_name' => $validated['name'],
                'gender' => $validated['gender'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            $teacher->subjects()->sync($validated['subject_ids'] ?? []);
        });

        return redirect()->route('admin.teachers.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data guru berhasil ditambahkan.']);
    }

    public function show(TeacherProfile $teacher): Response
    {
        $teacher->load(['user', 'subjects']);

        return Inertia::render('Admin/Teachers/Show', [
            'teacher' => $teacher,
        ]);
    }

    public function edit(TeacherProfile $teacher): Response
    {
        $teacher->load(['user', 'subjects']);

        return Inertia::render('Admin/Teachers/Form', [
            'mode' => 'edit',
            'teacher' => $teacher,
            'subjects' => Subject::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, TeacherProfile $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,'.$teacher->user_id],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$teacher->user_id],
            'password' => ['nullable', 'string', 'min:6'],
            'nip' => ['nullable', 'string', 'max:30', 'unique:teacher_profiles,nip,'.$teacher->id],
            'gender' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => [
                'exists:subjects,id',
                function ($attribute, $value, $fail) use ($teacher) {
                    $count = DB::table('teacher_subjects')
                        ->where('subject_id', $value)
                        ->where('teacher_profile_id', '!=', $teacher->id)
                        ->count();
                    if ($count >= 2) {
                        $subject = Subject::find($value);
                        $fail("Mata pelajaran {$subject->name} sudah maksimal diajar oleh 2 guru.");
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($teacher, $validated): void {
            $teacher->user->update([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
            ]);

            $teacher->update([
                'nip' => $validated['nip'] ?? null,
                'full_name' => $validated['name'],
                'gender' => $validated['gender'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            $teacher->subjects()->sync($validated['subject_ids'] ?? []);
        });

        return redirect()->route('admin.teachers.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data guru berhasil diperbarui.']);
    }

    public function destroy(TeacherProfile $teacher): RedirectResponse
    {
        return $this->tryDelete(
            function () use ($teacher): void {
                User::purgePasswordResetAuditsForUserIds([$teacher->user_id]);
                $teacher->user()->delete();
            },
            'admin.teachers.index',
            ['type' => 'success', 'message' => 'Data guru berhasil dihapus.'],
            'Gagal menghapus data guru. Silakan coba lagi.',
        );
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:teacher_profiles,id'],
        ]);

        return $this->tryDelete(
            function () use ($validated): void {
                $userIds = TeacherProfile::query()
                    ->whereIn('id', $validated['ids'])
                    ->pluck('user_id')
                    ->all();
                User::purgePasswordResetAuditsForUserIds($userIds);
                User::query()->whereIn('id', $userIds)->delete();
            },
            'admin.teachers.index',
            ['type' => 'success', 'message' => count($validated['ids']).' data guru berhasil dihapus.'],
            'Gagal menghapus data guru. Silakan coba lagi.',
        );
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StudentParentRelation;
use App\Http\Controllers\Concerns\HandlesAdminDeletes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Models\AttendanceManualStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSite;
use App\Models\ClassRoom;
use App\Models\DailyAttendance;
use App\Models\SchoolYear;
use App\Models\StudentParent;
use App\Models\StudentProfile;
use App\Models\StudentProfileExtension;
use App\Models\User;
use App\Services\Attendance\SchoolAttendanceTime;
use App\Services\Attendance\StudentDayFinalStatusService;
use App\Services\Students\StudentExcelImporter;
use App\Services\Wilayah\WilayahAddressSnapshot;
use App\Support\MonthlyIncomeBands;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StudentController extends Controller
{
    use HandlesAdminDeletes;

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'account_status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));

        $query = StudentProfile::query()
            ->with(['user', 'classes.schoolYear'])
            ->when($search !== '', function ($q) use ($search): void {
                $needle = '%'.$search.'%';
                $q->where(function ($q) use ($needle): void {
                    $q->where('nis', 'like', $needle)
                        ->orWhere('nisn', 'like', $needle)
                        ->orWhere('full_name', 'like', $needle)
                        ->orWhereHas('user', fn (Builder $uq) => $uq->where('name', 'like', $needle)
                            ->orWhere('username', 'like', $needle));
                });
            })
            ->when(! empty($filters['class_id']), function (Builder $q) use ($filters): void {
                $classId = (int) $filters['class_id'];
                $schoolYearId = ! empty($filters['school_year_id']) ? (int) $filters['school_year_id'] : null;

                $q->whereExists(function (\Illuminate\Database\Query\Builder $sub) use ($classId, $schoolYearId): void {
                    $sub->selectRaw('1')
                        ->from('class_student')
                        ->whereColumn('class_student.student_profile_id', 'student_profiles.id')
                        ->where('class_student.class_id', '=', $classId)
                        ->where('class_student.is_active', '=', true);

                    if ($schoolYearId !== null) {
                        $sub->where('class_student.school_year_id', '=', $schoolYearId);
                    }
                });
            })
            ->when(empty($filters['class_id']) && ! empty($filters['school_year_id']), function (Builder $q) use ($filters): void {
                $yearId = (int) $filters['school_year_id'];
                $q->whereExists(function (\Illuminate\Database\Query\Builder $sub) use ($yearId): void {
                    $sub->selectRaw('1')
                        ->from('class_student')
                        ->whereColumn('class_student.student_profile_id', 'student_profiles.id')
                        ->where('class_student.school_year_id', '=', $yearId)
                        ->where('class_student.is_active', '=', true);
                });
            })
            ->when(in_array(($filters['gender'] ?? '') ?: null, ['male', 'female'], true), function ($q) use ($filters): void {
                $q->where('gender', $filters['gender']);
            })
            ->when(($filters['account_status'] ?? '') === 'active', function ($q): void {
                $q->whereHas('user', fn (Builder $uq) => $uq->where('is_active', true));
            })
            ->when(($filters['account_status'] ?? '') === 'inactive', function ($q): void {
                $q->whereHas('user', fn (Builder $uq) => $uq->where('is_active', false));
            })
            ->orderByDesc('id');

        return Inertia::render('Admin/Students/Index', [
            'students' => $query->paginate(25)->withQueryString(),
            'classOptions' => ClassRoom::active()
                ->orderBy('name')
                ->get(['id', 'name', 'school_year_id']),
            'schoolYears' => SchoolYear::query()
                ->orderByDesc('id')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'class_id' => isset($filters['class_id']) ? (int) $filters['class_id'] : null,
                'school_year_id' => isset($filters['school_year_id']) ? (int) $filters['school_year_id'] : null,
                'gender' => $filters['gender'] ?? null,
                'account_status' => $filters['account_status'] ?? null,
            ],
        ]);
    }

    public function importForm(): Response
    {
        return Inertia::render('Admin/Students/Import', [
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
        ]);
    }

    public function import(ImportStudentsRequest $request, StudentExcelImporter $importer): RedirectResponse
    {
        $storedPath = $request->file('file')->store('imports');
        $absolutePath = Storage::path($storedPath);

        try {
            $result = $importer->import(
                $absolutePath,
                (int) $request->validated('school_year_id'),
            );
        } catch (Throwable $e) {
            Storage::delete($storedPath);

            return redirect()
                ->route('admin.students.import')
                ->with('flash', [
                    'type' => 'error',
                    'message' => 'Berkas Excel tidak dapat dibaca: '.$e->getMessage(),
                ]);
        }

        Storage::delete($storedPath);

        $message = $result->summaryMessage();

        if ($result->errors !== []) {
            $sample = array_slice($result->errors, 0, 5);
            $lines = array_map(
                static fn (array $e): string => sprintf(
                    '%s baris %d: %s',
                    $e['sheet'],
                    $e['row'],
                    $e['message'],
                ),
                $sample,
            );
            $message .= ' Contoh baris gagal: '.implode('; ', $lines);
            if (count($result->errors) > 5) {
                $message .= ' …';
            }
        }

        $type = 'success';
        if ($result->created === 0 && $result->skipped > 0) {
            $type = 'warning';
        }

        return redirect()
            ->route('admin.students.index')
            ->with('flash', [
                'type' => $type,
                'message' => $message,
            ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Students/Form', [
            'mode' => 'create',
            'student' => null,
            'classes' => ClassRoom::active()->orderBy('name')->get(),
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
            'incomeBandOptions' => MonthlyIncomeBands::options(),
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
            ...$this->studentExtensionValidationRules(),
        ]);

        DB::transaction(function () use ($validated, $request): void {
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

            $this->syncStudentExtensionFromRequest($student, $request, $validated);
            $this->syncStudentParentsFromValidated($student, $validated);
        });

        return redirect()->route('admin.students.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data siswa berhasil ditambahkan.']);
    }

    public function show(StudentProfile $student, StudentDayFinalStatusService $finalStatusService): Response
    {
        $student->load(['user', 'classes.schoolYear']);
        $attendanceRecords = AttendanceRecord::query()
            ->where('user_id', $student->user_id)
            ->with('attendanceSite:id,name')
            ->orderByDesc('attendance_at')
            ->limit(100)
            ->get();

        $dailyAttendances = DailyAttendance::query()
            ->where('student_profile_id', $student->id)
            ->with('attendanceSite:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(120)
            ->get();

        $manualAttendanceStatuses = AttendanceManualStatus::query()
            ->where('student_profile_id', $student->id)
            ->with(['attendanceSite:id,name', 'createdByUser:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $fromDay = SchoolAttendanceTime::now()->copy()->subDays(90)->startOfDay();
        $toDay = SchoolAttendanceTime::now()->copy()->startOfDay();
        $dailyAttendanceSummary = array_reverse($finalStatusService->buildSummaryForDateRange(
            $student->user,
            $fromDay,
            $toDay,
        ));

        $attendanceSitesForManual = AttendanceSite::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Students/Show', [
            'student' => $student,
            'attendanceRecords' => $attendanceRecords,
            'dailyAttendances' => $dailyAttendances,
            'manualAttendanceStatuses' => $manualAttendanceStatuses,
            'dailyAttendanceSummary' => $dailyAttendanceSummary,
            'attendanceSitesForManual' => $attendanceSitesForManual,
        ]);
    }

    public function exportAttendance(StudentProfile $student): HttpResponse
    {
        $attendanceRecords = AttendanceRecord::query()
            ->where('user_id', $student->user_id)
            ->with('attendanceSite:id,name')
            ->orderByDesc('attendance_at')
            ->get();

        $filename = 'attendance-student-'.$student->nis.'-'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = static function () use ($attendanceRecords): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['ID', 'Attendance Time', 'Type', 'Status', 'Reason Code', 'Reason Detail', 'Distance (m)', 'Site']);

            foreach ($attendanceRecords as $record) {
                fputcsv($stream, [
                    $record->id,
                    optional($record->attendance_at)->toDateTimeString(),
                    $record->attendance_type,
                    $record->status,
                    $record->reason_code,
                    $record->reason_detail,
                    $record->distance_m,
                    $record->attendanceSite?->name,
                ]);
            }

            fclose($stream);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function edit(StudentProfile $student): Response
    {
        $student->load(['user', 'classes', 'extension', 'parents']);

        return Inertia::render('Admin/Students/Form', [
            'mode' => 'edit',
            'student' => $student,
            'classes' => ClassRoom::active()->orderBy('name')->get(),
            'schoolYears' => SchoolYear::orderByDesc('id')->get(),
            'incomeBandOptions' => MonthlyIncomeBands::options(),
        ]);
    }

    public function update(Request $request, StudentProfile $student): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,'.$student->user_id],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$student->user_id],
            'password' => ['nullable', 'string', 'min:6'],
            'nis' => ['required', 'string', 'max:20', 'unique:student_profiles,nis,'.$student->id],
            'nisn' => ['nullable', 'string', 'max:20', 'unique:student_profiles,nisn,'.$student->id],
            'gender' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            ...$this->studentExtensionValidationRules(),
        ]);

        DB::transaction(function () use ($student, $validated, $request): void {
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

            $this->syncStudentExtensionFromRequest($student, $request, $validated);
            $this->syncStudentParentsFromValidated($student, $validated);
        });

        return redirect()->route('admin.students.index')
            ->with('flash', ['type' => 'success', 'message' => 'Data siswa berhasil diperbarui.']);
    }

    /**
     * @return array<string, array<int, mixed|string|\Closure>>
     */
    private function studentExtensionValidationRules(): array
    {
        return [
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'remove_profile_photo' => ['sometimes', 'boolean'],
            'street_address' => ['nullable', 'string', 'max:2000'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'village' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'wilayah_village_id' => ['nullable', 'string', 'size:10', 'exists:villages,id'],
            'religion' => ['nullable', 'string', 'max:50'],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'mother_full_name' => ['nullable', 'string', 'max:150'],
            'mother_occupation' => ['nullable', 'string', 'max:150'],
            'mother_monthly_income_band' => ['nullable', 'string', Rule::in(MonthlyIncomeBands::values())],
            'mother_nik' => ['nullable', 'digits:16'],
            'mother_birth_date' => ['nullable', 'date'],
            'father_full_name' => ['nullable', 'string', 'max:150'],
            'father_occupation' => ['nullable', 'string', 'max:150'],
            'father_monthly_income_band' => ['nullable', 'string', Rule::in(MonthlyIncomeBands::values())],
            'father_nik' => ['nullable', 'digits:16'],
            'father_birth_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncStudentExtensionFromRequest(StudentProfile $student, Request $request, array $validated): void
    {
        $extension = StudentProfileExtension::query()->firstOrNew(['student_profile_id' => $student->id]);
        $data = Arr::only($validated, [
            'street_address',
            'rt',
            'rw',
            'village',
            'district',
            'city',
            'province',
            'postal_code',
            'religion',
            'blood_type',
        ]);

        $wilayahVillageId = $validated['wilayah_village_id'] ?? null;
        if (is_string($wilayahVillageId) && $wilayahVillageId !== '') {
            $snapshot = WilayahAddressSnapshot::fromVillageId($wilayahVillageId);
            if ($snapshot !== null) {
                $data = array_merge($data, $snapshot);
            }
        } else {
            $data['wilayah_village_id'] = null;
        }

        $extension->fill($data);

        $remove = filter_var($validated['remove_profile_photo'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($request->hasFile('profile_photo')) {
            $uploaded = $request->file('profile_photo');
            \assert($uploaded !== null && $uploaded->isValid());

            if ($extension->profile_photo_path !== null && $extension->profile_photo_path !== '' && Storage::disk('public')->exists($extension->profile_photo_path)) {
                Storage::disk('public')->delete($extension->profile_photo_path);
            }

            $extension->profile_photo_path = $uploaded->store('student-profile-extensions/'.$student->id, 'public');
        } elseif ($remove && ($extension->profile_photo_path !== null && $extension->profile_photo_path !== '')) {
            if (Storage::disk('public')->exists($extension->profile_photo_path)) {
                Storage::disk('public')->delete($extension->profile_photo_path);
            }
            $extension->profile_photo_path = null;
        }

        $extension->save();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncStudentParentsFromValidated(StudentProfile $student, array $validated): void
    {
        foreach (StudentParentRelation::cases() as $relation) {
            $prefix = $relation === StudentParentRelation::Mother ? 'mother_' : 'father_';

            $payload = [
                'full_name' => $validated[$prefix.'full_name'] ?? null,
                'occupation' => $validated[$prefix.'occupation'] ?? null,
                'monthly_income_band' => $validated[$prefix.'monthly_income_band'] ?? null,
                'nik' => $validated[$prefix.'nik'] ?? null,
                'birth_date' => $validated[$prefix.'birth_date'] ?? null,
            ];

            $hasAnyData = Collection::make($payload)
                ->contains(static fn ($value): bool => $value !== null && $value !== '');

            if (! $hasAnyData) {
                StudentParent::query()
                    ->where('student_profile_id', $student->id)
                    ->where('relation', $relation->value)
                    ->delete();

                continue;
            }

            StudentParent::query()->updateOrCreate(
                [
                    'student_profile_id' => $student->id,
                    'relation' => $relation->value,
                ],
                $payload,
            );
        }
    }

    public function destroy(StudentProfile $student): RedirectResponse
    {
        return $this->tryDelete(
            function () use ($student): void {
                User::purgePasswordResetAuditsForUserIds([$student->user_id]);
                $student->user()->delete();
            },
            'admin.students.index',
            ['type' => 'success', 'message' => 'Data siswa berhasil dihapus.'],
            'Gagal menghapus data siswa. Silakan coba lagi.',
        );
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:student_profiles,id'],
        ]);

        return $this->tryDelete(
            function () use ($validated): void {
                $userIds = StudentProfile::query()
                    ->whereIn('id', $validated['ids'])
                    ->pluck('user_id')
                    ->all();
                User::purgePasswordResetAuditsForUserIds($userIds);
                User::query()->whereIn('id', $userIds)->delete();
            },
            'admin.students.index',
            ['type' => 'success', 'message' => count($validated['ids']).' data siswa berhasil dihapus.'],
            'Gagal menghapus data siswa. Silakan coba lagi.',
        );
    }
}

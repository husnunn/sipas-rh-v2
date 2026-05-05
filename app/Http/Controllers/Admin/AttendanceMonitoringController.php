<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSite;
use App\Models\ClassRoom;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Admin\UnifiedStudentAttendanceMonitor;
use App\Services\Attendance\SchoolAttendanceTime;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceMonitoringController extends Controller
{
    public function __construct(
        private readonly UnifiedStudentAttendanceMonitor $unifiedMonitor,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $filters['report'] = 'monitoring';
        $filters['student_profile_id'] = null;

        $result = $this->unifiedMonitor->paginate($filters, 20);
        /** @var LengthAwarePaginator $paginator */
        $paginator = $result['paginator'];
        $stats = $result['stats'];

        $rows = $this->presentUnifiedRows(collect($paginator->items()), $filters);
        $paginator->setCollection(collect($rows));

        $classesQuery = ClassRoom::query()->active()->with('schoolYear:id,name')->orderBy('name');
        if ($filters['school_year_id'] !== null) {
            $classesQuery->where('school_year_id', $filters['school_year_id']);
        }

        $studentsForReport = $this->studentsForReportDropdown($filters);

        return Inertia::render('Admin/AttendanceRecords/Index', [
            'records' => $paginator,
            'filters' => $filters,
            'stats' => [
                'total' => $stats['total'],
                'approved' => $stats['success'],
                'rejected' => $stats['rejected'],
                'approval_rate' => $stats['approval_rate'],
            ],
            'school_timezone' => SchoolAttendanceTime::timezone(),
            'classes' => $classesQuery->get(['id', 'name', 'school_year_id']),
            'school_years' => SchoolYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'students_for_report' => $studentsForReport,
        ]);
    }

    public function printReport(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $this->assertReportScopeComplete($filters);
        $rows = $this->unifiedMonitor->allRowsForExport($filters);
        $presented = $this->presentUnifiedRows($rows, $filters);

        $title = $this->reportTitle($filters);

        return view('admin.attendance-report-print', [
            'title' => $title,
            'filters' => $filters,
            'rows' => $presented,
            'school_timezone' => SchoolAttendanceTime::timezone(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $this->assertReportScopeComplete($filters);
        $rows = $this->unifiedMonitor->allRowsForExport($filters);
        $presented = $this->presentUnifiedRows($rows, $filters);

        $suffix = match ($filters['report'] ?? 'monitoring') {
            'student' => 'siswa',
            'class' => 'kelas',
            'school_year' => 'tahun-ajaran',
            default => 'monitoring',
        };
        $filename = 'laporan-absensi-'.$suffix.'-'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($presented): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'sumber',
                'waktu',
                'nama',
                'username',
                'nis',
                'kelas',
                'mapel',
                'tahun_ajaran',
                'semester',
                'tipe',
                'status',
                'lokasi',
                'keterangan',
            ]);

            foreach ($presented as $row) {
                fputcsv($stream, [
                    $row['feed_source_label'] ?? '',
                    $row['attendance_time'] ?? '',
                    $row['user']['name'] ?? '',
                    $row['user']['username'] ?? '',
                    $row['nis'] ?? '',
                    $row['class']['name'] ?? '',
                    $row['subject']['name'] ?? '',
                    $row['school_year']['name'] ?? '',
                    $row['schedule_semester'] ?? '',
                    $row['attendance_type_label'] ?? '',
                    $row['status'] ?? '',
                    $row['site']['name'] ?? '',
                    $row['reason_detail'] ?? '',
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function reportTitle(array $filters): string
    {
        return match ($filters['report'] ?? 'monitoring') {
            'student' => 'Laporan absensi per siswa',
            'class' => 'Laporan absensi per kelas',
            'school_year' => 'Laporan absensi per tahun ajaran',
            default => 'Riwayat absensi siswa',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function studentsForReportDropdown(array $filters): array
    {
        $q = StudentProfile::query()->orderBy('full_name');

        if ($filters['class_id'] !== null) {
            $q->whereHas('classes', function ($rel) use ($filters): void {
                $rel->where('classes.id', $filters['class_id']);
                if ($filters['school_year_id'] !== null) {
                    $rel->where('class_student.school_year_id', $filters['school_year_id']);
                }
            });
        } elseif ($filters['school_year_id'] !== null) {
            $q->whereHas('classes', function ($rel) use ($filters): void {
                $rel->where('class_student.school_year_id', $filters['school_year_id'])
                    ->where('class_student.is_active', true);
            });
        } else {
            return [];
        }

        return $q->limit(500)->get(['id', 'full_name', 'nis'])->map(fn (StudentProfile $p): array => [
            'id' => $p->id,
            'label' => trim($p->full_name.($p->nis ? ' ('.$p->nis.')' : '')),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   school_year_id: ?int,
     *   class_id: ?int,
     *   semester: string,
     *   status: string,
     *   attendance_type: string,
     *   report: string,
     *   student_profile_id: ?int
     * }
     */
    private function validatedFilters(Request $request): array
    {
        $merge = [];
        foreach (['class_id', 'school_year_id', 'student_profile_id'] as $key) {
            if ($request->has($key) && $request->input($key) === '') {
                $merge[$key] = null;
            }
        }
        if ($merge !== []) {
            $request->merge($merge);
        }

        $validated = $request->validate([
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'semester' => ['nullable', 'in:all,1,2'],
            'status' => ['nullable', 'in:all,approved,rejected'],
            'attendance_type' => ['nullable', 'in:all,check_in,check_out,daily_check_in,daily_check_out,manual_excused,manual_sick,manual_dispensation'],
            'report' => ['nullable', 'in:monitoring,student,class,school_year'],
            'student_profile_id' => ['nullable', 'integer', 'exists:student_profiles,id'],
        ]);

        return [
            'school_year_id' => isset($validated['school_year_id']) ? (int) $validated['school_year_id'] : null,
            'class_id' => isset($validated['class_id']) ? (int) $validated['class_id'] : null,
            'semester' => $validated['semester'] ?? 'all',
            'status' => $validated['status'] ?? 'all',
            'attendance_type' => $validated['attendance_type'] ?? 'all',
            'report' => $validated['report'] ?? 'monitoring',
            'student_profile_id' => isset($validated['student_profile_id']) ? (int) $validated['student_profile_id'] : null,
        ];
    }

    /**
     * @param  Collection<int, object>  $rawRows
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function presentUnifiedRows(Collection $rawRows, array $filters): array
    {
        $userIds = $rawRows->pluck('user_id')->filter()->unique()->values()->all();
        $profileIds = $rawRows->pluck('student_profile_id')->filter()->unique()->values()->all();
        $siteIds = $rawRows->pluck('attendance_site_id')->filter()->unique()->values()->all();

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'username', 'roles'])
            ->keyBy('id');

        $profiles = StudentProfile::query()
            ->whereIn('id', $profileIds)
            ->with([
                'classes' => fn ($q) => $q->withPivot('school_year_id', 'is_active')
                    ->with('schoolYear:id,name'),
            ])
            ->get()
            ->keyBy('id');

        $sites = AttendanceSite::query()
            ->whereIn('id', $siteIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $tz = SchoolAttendanceTime::timezone();

        return $rawRows->map(function (object $r) use ($users, $profiles, $sites, $tz, $filters): array {
            $user = $users->get((int) $r->user_id);
            $profile = $r->student_profile_id !== null ? $profiles->get((int) $r->student_profile_id) : null;

            $occurred = $r->occurred_at !== null
                ? Carbon::parse((string) $r->occurred_at, $tz)->toIso8601String()
                : null;

            $classFromRow = $r->class_id !== null
                ? ['id' => (int) $r->class_id, 'name' => (string) $r->class_name]
                : $this->resolveDisplayClass($profile, $filters);

            $schoolYearFromRow = $r->school_year_id !== null
                ? ['id' => (int) $r->school_year_id, 'name' => (string) $r->school_year_name]
                : $this->resolveDisplaySchoolYear($profile, $filters, $classFromRow);

            $site = $r->attendance_site_id !== null
                ? ['id' => (int) $r->attendance_site_id, 'name' => (string) ($sites->get((int) $r->attendance_site_id)?->name ?? '')]
                : null;

            $sem = $r->semester;
            $semLabel = ($sem === null || $sem === '') ? '—' : 'Semester '.(string) $sem;

            return [
                'id' => (string) $r->row_key,
                'row_key' => (string) $r->row_key,
                'feed_source' => (string) $r->feed_source,
                'feed_source_label' => $this->feedSourceLabel((string) $r->feed_source),
                'attendance_type' => (string) $r->attendance_type,
                'attendance_type_label' => $this->attendanceTypeLabel((string) $r->attendance_type),
                'status' => (string) $r->row_status,
                'attendance_time' => $occurred,
                'reason_detail' => $r->reason_detail !== null ? (string) $r->reason_detail : null,
                'user' => [
                    'id' => $user?->id,
                    'name' => $user?->name,
                    'username' => $user?->username,
                    'roles' => $user?->roles ?? [],
                ],
                'civitas' => 'siswa',
                'nis' => $profile?->nis,
                'student_profile_id' => $profile?->id,
                'class' => $classFromRow,
                'subject' => $r->subject_id !== null
                    ? ['id' => (int) $r->subject_id, 'name' => (string) $r->subject_name]
                    : null,
                'school_year' => $schoolYearFromRow,
                'schedule_semester' => $semLabel,
                'site' => $site,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function assertReportScopeComplete(array $filters): void
    {
        $report = $filters['report'] ?? 'monitoring';

        if ($report === 'monitoring') {
            return;
        }

        if ($report === 'student' && ($filters['student_profile_id'] ?? null) === null) {
            throw ValidationException::withMessages([
                'student_profile_id' => ['Pilih siswa untuk laporan per siswa.'],
            ]);
        }

        if ($report === 'class' && ($filters['class_id'] ?? null) === null) {
            throw ValidationException::withMessages([
                'class_id' => ['Pilih kelas untuk laporan per kelas.'],
            ]);
        }

        if ($report === 'school_year' && ($filters['school_year_id'] ?? null) === null) {
            throw ValidationException::withMessages([
                'school_year_id' => ['Pilih tahun ajaran untuk laporan per angkatan.'],
            ]);
        }
    }

    /**
     * @param  array{id: int, name: string}|null  $classFromRow
     * @return array{id: int, name: string}|null
     */
    private function resolveDisplaySchoolYear(?StudentProfile $profile, array $filters, ?array $classFromRow): ?array
    {
        if ($filters['school_year_id'] !== null) {
            $sy = SchoolYear::query()->find($filters['school_year_id']);
            if ($sy !== null) {
                return ['id' => $sy->id, 'name' => $sy->name];
            }
        }

        if ($profile === null || $classFromRow === null) {
            return null;
        }

        $class = $profile->classes->firstWhere('id', $classFromRow['id']);
        if ($class === null) {
            return null;
        }

        $syId = (int) $class->pivot->school_year_id;
        $sy = SchoolYear::query()->find($syId);

        return $sy !== null ? ['id' => $sy->id, 'name' => $sy->name] : null;
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function resolveDisplayClass(?StudentProfile $profile, array $filters): ?array
    {
        if ($profile === null) {
            return null;
        }

        $classes = $profile->classes;
        if ($classes->isEmpty()) {
            return null;
        }

        if ($filters['class_id'] !== null) {
            $hit = $classes->firstWhere('id', $filters['class_id']);

            return $hit !== null ? ['id' => (int) $hit->id, 'name' => (string) $hit->name] : null;
        }

        if ($filters['school_year_id'] !== null) {
            $hit = $classes->first(function ($c) use ($filters): bool {
                return (int) $c->pivot->school_year_id === (int) $filters['school_year_id']
                    && (bool) $c->pivot->is_active;
            });

            if ($hit !== null) {
                return ['id' => (int) $hit->id, 'name' => (string) $hit->name];
            }
        }

        $active = $classes->first(fn ($c): bool => (bool) $c->pivot->is_active);

        return $active !== null
            ? ['id' => (int) $active->id, 'name' => (string) $active->name]
            : ['id' => (int) $classes->first()->id, 'name' => (string) $classes->first()->name];
    }

    private function feedSourceLabel(string $feed): string
    {
        return match (true) {
            $feed === 'legacy' => 'Jadwal mapel',
            str_starts_with($feed, 'daily') => 'Absensi harian',
            $feed === 'manual' => 'Status manual',
            default => $feed,
        };
    }

    private function attendanceTypeLabel(string $type): string
    {
        return match ($type) {
            'check_in' => 'Check-in (mapel)',
            'check_out' => 'Check-out (mapel)',
            'daily_check_in' => 'Masuk harian',
            'daily_check_out' => 'Pulang harian',
            'manual_excused' => 'Izin',
            'manual_sick' => 'Sakit',
            'manual_dispensation' => 'Dispensasi',
            default => $type,
        };
    }
}

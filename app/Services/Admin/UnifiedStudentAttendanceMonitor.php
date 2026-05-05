<?php

namespace App\Services\Admin;

use App\Models\SchoolYear;
use App\Services\Attendance\SchoolAttendanceTime;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Merges student attendance from schedule-based records, daily check-in/out, and manual statuses
 * for admin monitoring (single timeline, paginated via SQL UNION).
 */
final class UnifiedStudentAttendanceMonitor
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{paginator: LengthAwarePaginator, stats: array{total: int, success: int, rejected: int, approval_rate: float}}
     */
    public function paginate(array $filters, int $perPage = 20): array
    {
        [$from, $to] = $this->resolveDateBounds($filters['school_year_id'] ?? null);

        $union = $this->buildUnion($filters, $from, $to);
        $base = DB::query()->fromSub($union, 'unified')->orderByDesc('occurred_at')->orderByDesc('row_key');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $base->paginate($perPage)->withQueryString();

        $stats = $this->computeStats($filters, $from, $to);

        return [
            'paginator' => $paginator,
            'stats' => $stats,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    public function allRowsForExport(array $filters): Collection
    {
        [$from, $to] = $this->resolveDateBounds($filters['school_year_id'] ?? null);
        $union = $this->buildUnion($filters, $from, $to);

        return DB::query()
            ->fromSub($union, 'unified')
            ->orderByDesc('occurred_at')
            ->orderByDesc('row_key')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, success: int, rejected: int, approval_rate: float}
     */
    private function computeStats(array $filters, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $union = $this->buildUnion($filters, $from, $to);
        $rows = DB::query()->fromSub($union, 'u')->select(['row_status', 'feed_source'])->get();

        $total = $rows->count();
        $success = $rows->filter(function (object $r): bool {
            return $this->isSuccessRowStatus((string) $r->row_status, (string) $r->feed_source);
        })->count();

        $rejected = $rows->where('row_status', 'rejected')->count();
        $approvalRate = $total > 0 ? round(100 * $success / $total, 1) : 0.0;

        return [
            'total' => $total,
            'success' => $success,
            'rejected' => $rejected,
            'approval_rate' => $approvalRate,
        ];
    }

    private function isSuccessRowStatus(string $rowStatus, string $feedSource): bool
    {
        if ($feedSource === 'legacy') {
            return $rowStatus === 'approved';
        }

        if (str_starts_with($feedSource, 'daily')) {
            return in_array($rowStatus, ['present', 'late'], true);
        }

        if ($feedSource === 'manual') {
            return $rowStatus === 'approved';
        }

        return false;
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function resolveDateBounds(?int $schoolYearId): array
    {
        if ($schoolYearId !== null) {
            $tz = SchoolAttendanceTime::timezone();
            $sy = SchoolYear::query()->find($schoolYearId);
            if ($sy !== null) {
                $from = Carbon::parse($sy->start_date)->timezone($tz)->startOfDay();
                $to = Carbon::parse($sy->end_date)->timezone($tz)->endOfDay();

                return [$from, $to];
            }
        }

        // Default monitoring page: show all history unless admin explicitly filters by school year.
        return [null, null];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildUnion(array $filters, ?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        $branches = [];

        $legacy = $this->legacyBranch($filters, $from, $to);
        if ($legacy !== null) {
            $branches[] = $legacy;
        }

        foreach (['check_in' => 'daily_in', 'check_out' => 'daily_out'] as $phase => $feed) {
            $daily = $this->dailyBranch($filters, $from, $to, $phase, $feed);
            if ($daily !== null) {
                $branches[] = $daily;
            }
        }

        $manual = $this->manualBranch($filters, $from, $to);
        if ($manual !== null) {
            $branches[] = $manual;
        }

        if ($branches === []) {
            return $this->emptyUnion();
        }

        $merged = array_shift($branches);
        foreach ($branches as $b) {
            $merged = $merged->unionAll($b);
        }

        return $merged;
    }

    private function emptyUnion(): Builder
    {
        return DB::table('attendance_records as ar')
            ->leftJoin('users as u', 'u.id', '=', 'ar.user_id')
            ->leftJoin('schedules as sch', 'sch.id', '=', 'ar.schedule_id')
            ->leftJoin('classes as cl', 'cl.id', '=', 'sch.class_id')
            ->leftJoin('subjects as sub', 'sub.id', '=', 'sch.subject_id')
            ->leftJoin('school_years as sy', 'sy.id', '=', 'sch.school_year_id')
            ->leftJoin('student_profiles as sp', 'sp.user_id', '=', 'ar.user_id')
            ->whereRaw('0 = 1')
            ->selectRaw("'empty-0' as row_key")
            ->addSelect([
                DB::raw('NULL as occurred_at'),
                DB::raw("'legacy' as feed_source"),
                DB::raw("'check_in' as attendance_type"),
                DB::raw("'rejected' as row_status"),
                DB::raw('NULL as reason_detail'),
                DB::raw('NULL as attendance_site_id'),
                DB::raw('NULL as schedule_id'),
                DB::raw('NULL as subject_id'),
                DB::raw('NULL as subject_name'),
                DB::raw('NULL as class_id'),
                DB::raw('NULL as class_name'),
                DB::raw('NULL as school_year_id'),
                DB::raw('NULL as school_year_name'),
                DB::raw('NULL as semester'),
                DB::raw('NULL as user_id'),
                DB::raw('NULL as student_profile_id'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function legacyBranch(array $filters, ?CarbonInterface $from, ?CarbonInterface $to): ?Builder
    {
        if (! $this->branchAllowedByAttendanceType($filters['attendance_type'] ?? 'all', 'legacy')) {
            return null;
        }

        if (! $this->branchAllowedByStatus($filters['status'] ?? 'all', 'legacy')) {
            return null;
        }

        $q = DB::table('attendance_records as ar')
            ->join('users as u', 'u.id', '=', 'ar.user_id')
            ->whereJsonContains('u.roles', 'student')
            ->leftJoin('schedules as sch', 'sch.id', '=', 'ar.schedule_id')
            ->leftJoin('classes as cl', 'cl.id', '=', 'sch.class_id')
            ->leftJoin('subjects as sub', 'sub.id', '=', 'sch.subject_id')
            ->leftJoin('school_years as sy', 'sy.id', '=', 'sch.school_year_id')
            ->leftJoin('student_profiles as sp', 'sp.user_id', '=', 'ar.user_id');

        if ($from !== null && $to !== null) {
            $q->whereBetween('ar.attendance_at', [$from, $to]);
        }

        $this->applyReportScope($q, $filters, 'sp.id');

        if (($filters['school_year_id'] ?? null) !== null) {
            $q->whereNotNull('sch.id')
                ->where('sy.id', (int) $filters['school_year_id']);
        }

        if (($filters['class_id'] ?? null) !== null) {
            $q->whereNotNull('sch.id')
                ->where('cl.id', (int) $filters['class_id']);
        }

        if (($filters['semester'] ?? 'all') !== 'all') {
            $q->whereNotNull('sch.id')
                ->where('sch.semester', (int) $filters['semester']);
        }

        if (($filters['status'] ?? 'all') === 'approved') {
            $q->where('ar.status', 'approved');
        } elseif (($filters['status'] ?? 'all') === 'rejected') {
            $q->where('ar.status', 'rejected');
        }

        if (($filters['attendance_type'] ?? 'all') === 'check_in') {
            $q->where('ar.attendance_type', 'check_in');
        } elseif (($filters['attendance_type'] ?? 'all') === 'check_out') {
            $q->where('ar.attendance_type', 'check_out');
        }

        $q->selectRaw($this->rowKeyExpr('legacy', 'ar.id').' as row_key')
            ->addSelect([
                'ar.attendance_at as occurred_at',
                DB::raw("'legacy' as feed_source"),
                'ar.attendance_type as attendance_type',
                'ar.status as row_status',
                'ar.reason_detail as reason_detail',
                'ar.attendance_site_id as attendance_site_id',
                'ar.schedule_id as schedule_id',
                'sub.id as subject_id',
                'sub.name as subject_name',
                'cl.id as class_id',
                'cl.name as class_name',
                'sy.id as school_year_id',
                'sy.name as school_year_name',
                'sch.semester as semester',
                'ar.user_id as user_id',
                'sp.id as student_profile_id',
            ]);

        return $q;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function dailyBranch(array $filters, ?CarbonInterface $from, ?CarbonInterface $to, string $phase, string $feedSource): ?Builder
    {
        $typeKey = $phase === 'check_in' ? 'daily_check_in' : 'daily_check_out';
        if (! $this->branchAllowedByAttendanceType($filters['attendance_type'] ?? 'all', $typeKey)) {
            return null;
        }

        if (! $this->branchAllowedByStatus($filters['status'] ?? 'all', 'daily')) {
            return null;
        }

        $timeCol = $phase === 'check_in' ? 'da.check_in_at' : 'da.check_out_at';

        $q = DB::table('daily_attendances as da')
            ->join('users as u', 'u.id', '=', 'da.user_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'da.student_profile_id')
            ->whereNotNull($timeCol);

        if ($from !== null && $to !== null) {
            $q->whereBetween('da.date', [$from->toDateString(), $to->toDateString()]);
        }

        $this->applyClassSchoolYearPivotFilter($q, $filters, 'sp.id');
        $this->applyReportScope($q, $filters, 'sp.id');

        if (($filters['semester'] ?? 'all') !== 'all') {
            return null;
        }

        $q->selectRaw($this->rowKeyExpr($feedSource, 'da.id').' as row_key')
            ->addSelect([
                DB::raw("{$timeCol} as occurred_at"),
                DB::raw("'{$feedSource}' as feed_source"),
                DB::raw($phase === 'check_in' ? "'daily_check_in' as attendance_type" : "'daily_check_out' as attendance_type"),
                DB::raw($this->dailyStatusSql('da.status').' as row_status'),
                'da.check_in_reason_detail as reason_detail',
                'da.attendance_site_id as attendance_site_id',
                DB::raw('NULL as schedule_id'),
                DB::raw('NULL as subject_id'),
                DB::raw('NULL as subject_name'),
                DB::raw('NULL as class_id'),
                DB::raw('NULL as class_name'),
                DB::raw('NULL as school_year_id'),
                DB::raw('NULL as school_year_name'),
                DB::raw('NULL as semester'),
                'da.user_id as user_id',
                'sp.id as student_profile_id',
            ]);

        return $q;
    }

    private function dailyStatusSql(string $column): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "CAST({$column} AS TEXT)";
        }

        return "CAST({$column} AS CHAR)";
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function manualBranch(array $filters, ?CarbonInterface $from, ?CarbonInterface $to): ?Builder
    {
        if (! $this->branchAllowedByAttendanceType($filters['attendance_type'] ?? 'all', 'manual')) {
            return null;
        }

        if (! $this->branchAllowedByStatus($filters['status'] ?? 'all', 'manual')) {
            return null;
        }

        $occurred = $this->manualOccurredAtSql('manual.date');

        $q = DB::table('attendance_manual_statuses as manual')
            ->join('users as u', 'u.id', '=', 'manual.user_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'manual.student_profile_id');

        if ($from !== null && $to !== null) {
            $q->whereBetween('manual.date', [$from->toDateString(), $to->toDateString()]);
        }

        $this->applyClassSchoolYearPivotFilter($q, $filters, 'sp.id');
        $this->applyReportScope($q, $filters, 'sp.id');

        if (($filters['semester'] ?? 'all') !== 'all') {
            return null;
        }

        if ($this->manualTypeFromFilter($filters['attendance_type'] ?? 'all') !== null) {
            $q->where('manual.type', $this->manualTypeFromFilter($filters['attendance_type'] ?? 'all'));
        }

        if (($filters['status'] ?? 'all') === 'approved') {
            $q->where('manual.status', 'approved');
        }

        $typeCase = <<<'SQL'
CASE manual.type
    WHEN 'excused' THEN 'manual_excused'
    WHEN 'sick' THEN 'manual_sick'
    WHEN 'dispensation' THEN 'manual_dispensation'
    ELSE 'manual_other'
END
SQL;

        $q->selectRaw($this->rowKeyExpr('manual', 'manual.id').' as row_key')
            ->addSelect([
                DB::raw("{$occurred} as occurred_at"),
                DB::raw("'manual' as feed_source"),
                DB::raw("({$typeCase}) as attendance_type"),
                DB::raw($this->dailyStatusSql('manual.status').' as row_status'),
                'manual.reason as reason_detail',
                'manual.attendance_site_id as attendance_site_id',
                DB::raw('NULL as schedule_id'),
                DB::raw('NULL as subject_id'),
                DB::raw('NULL as subject_name'),
                DB::raw('NULL as class_id'),
                DB::raw('NULL as class_name'),
                DB::raw('NULL as school_year_id'),
                DB::raw('NULL as school_year_name'),
                DB::raw('NULL as semester'),
                'manual.user_id as user_id',
                'sp.id as student_profile_id',
            ]);

        return $q;
    }

    private function manualTypeFromFilter(string $filter): ?string
    {
        return match ($filter) {
            'manual_excused' => 'excused',
            'manual_sick' => 'sick',
            'manual_dispensation' => 'dispensation',
            default => null,
        };
    }

    private function manualOccurredAtSql(string $dateColumn): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "datetime({$dateColumn} || ' 12:00:00')";
        }

        return "CAST(CONCAT({$dateColumn}, ' 12:00:00') AS DATETIME)";
    }

    private function rowKeyExpr(string $prefix, string $idColumn): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "'{$prefix}-' || {$idColumn}";
        }

        return "CONCAT('{$prefix}-', {$idColumn})";
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyClassSchoolYearPivotFilter(Builder $q, array $filters, string $studentProfileColumn): void
    {
        $classId = $filters['class_id'] ?? null;
        $schoolYearId = $filters['school_year_id'] ?? null;

        if ($classId === null && $schoolYearId === null) {
            return;
        }

        $q->whereExists(function (Builder $sub) use ($classId, $schoolYearId, $studentProfileColumn): void {
            $sub->from('class_student as cs')
                ->whereColumn('cs.student_profile_id', $studentProfileColumn)
                ->where('cs.is_active', true);

            if ($classId !== null) {
                $sub->where('cs.class_id', (int) $classId);
            }

            if ($schoolYearId !== null) {
                $sub->where('cs.school_year_id', (int) $schoolYearId);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyReportScope(Builder $q, array $filters, string $studentProfileColumn): void
    {
        $report = $filters['report'] ?? 'monitoring';
        $studentProfileId = $filters['student_profile_id'] ?? null;

        if ($report === 'student' && $studentProfileId !== null) {
            $q->where($studentProfileColumn, (int) $studentProfileId);
        }
    }

    private function branchAllowedByAttendanceType(string $filter, string $branchKind): bool
    {
        if ($filter === 'all') {
            return true;
        }

        return match ($branchKind) {
            'legacy' => in_array($filter, ['check_in', 'check_out'], true),
            'daily_check_in' => $filter === 'daily_check_in',
            'daily_check_out' => $filter === 'daily_check_out',
            'manual' => str_starts_with($filter, 'manual_'),
            default => false,
        };
    }

    private function branchAllowedByStatus(string $filter, string $branchKind): bool
    {
        if ($filter === 'all') {
            return true;
        }

        if ($filter === 'rejected') {
            return $branchKind === 'legacy';
        }

        return true;
    }
}

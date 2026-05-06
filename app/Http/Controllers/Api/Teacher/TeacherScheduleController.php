<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;
use App\Models\AcademicCalendarEvent;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Services\Attendance\SchoolAttendanceTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeacherScheduleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($this->shouldHideSchedulesForDate($request)) {
            return ScheduleResource::collection(collect());
        }

        $schedules = $this->teacherSchedulesQuery($request, applyDayQueryFilter: true)->get();

        return ScheduleResource::collection($schedules);
    }

    /**
     * Jadwal mengajar dikelompokkan per hari; hanya hari yang memiliki minimal satu slot
     * yang dikembalikan (contoh: Senin & Jumat saja untuk UI tab Android).
     */
    public function byDay(Request $request): JsonResponse
    {
        if ($this->shouldHideSchedulesForDate($request)) {
            return response()->json(['data' => []]);
        }

        $schedules = $this->teacherSchedulesQuery($request, applyDayQueryFilter: false)->get();
        $grouped = $schedules->groupBy('day_of_week')->sortKeys();

        $data = $grouped->map(function ($items, int|string $dayOfWeek) use ($request): array {
            $dayInt = (int) $dayOfWeek;
            $sorted = $items->sortBy('start_time')->values();

            return [
                'day_of_week' => $dayInt,
                'day_name' => Schedule::DAYS[$dayInt] ?? 'Unknown',
                'schedules' => ScheduleResource::collection($sorted)->toArray($request),
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    private function shouldHideSchedulesForDate(Request $request): bool
    {
        if (! $request->query('date')) {
            return false;
        }

        $date = SchoolAttendanceTime::parseCalendarDate((string) $request->query('date'));
        $event = AcademicCalendarEvent::query()
            ->active()
            ->overlapsDate($date)
            ->where(function ($query) {
                $query->where('allow_attendance', false)
                    ->orWhere('override_schedule', true);
            })
            ->first();

        return $event !== null;
    }

    /**
     * @return Builder<Schedule>
     */
    private function teacherSchedulesQuery(Request $request, bool $applyDayQueryFilter): Builder
    {
        $user = $request->user();
        $teacherProfile = $user->teacherProfile;

        if (! $teacherProfile) {
            abort(404, 'Profil guru belum dibuat.');
        }

        $activeSchoolYear = SchoolYear::active()->first();

        return Schedule::query()
            ->where('teacher_profile_id', $teacherProfile->id)
            ->when($activeSchoolYear, fn (Builder $q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->when($request->query('semester'), fn (Builder $q, $semester) => $q->where('semester', $semester))
            ->when($applyDayQueryFilter && $request->filled('day'), function (Builder $q) use ($request) {
                $q->where('day_of_week', (int) $request->query('day'));
            })
            ->active()
            ->with(['classRoom', 'subject', 'schoolYear'])
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }
}

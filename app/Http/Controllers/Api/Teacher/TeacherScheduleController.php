<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeacherScheduleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $teacherProfile = $user->teacherProfile;

        if (! $teacherProfile) {
            abort(404, 'Profil guru belum dibuat.');
        }

        $activeSchoolYear = SchoolYear::active()->first();

        $schedules = Schedule::query()
            ->where('teacher_profile_id', $teacherProfile->id)
            ->when($activeSchoolYear, fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->when($request->query('semester'), fn ($q, $semester) => $q->where('semester', $semester))
            ->when($request->query('day'), fn ($q, $day) => $q->where('day_of_week', $day))
            ->active()
            ->with(['classRoom', 'subject', 'schoolYear'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return ScheduleResource::collection($schedules);
    }
}

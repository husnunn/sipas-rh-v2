<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentScheduleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            abort(404, 'Profil siswa belum dibuat.');
        }

        $activeSchoolYear = SchoolYear::active()->first();

        // Get the student's active class ID
        $activeClassQuery = $studentProfile->activeClass();

        if ($activeSchoolYear) {
            $activeClassQuery->wherePivot('school_year_id', $activeSchoolYear->id);
        }

        $activeClass = $activeClassQuery->first();

        if (! $activeClass) {
            return ScheduleResource::collection(collect());
        }

        $schedules = Schedule::query()
            ->where('class_id', $activeClass->id)
            ->when($activeSchoolYear, fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->when($request->query('semester'), fn ($q, $semester) => $q->where('semester', $semester))
            ->when($request->query('day'), fn ($q, $day) => $q->where('day_of_week', $day))
            ->active()
            ->with(['teacherProfile', 'subject', 'schoolYear'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return ScheduleResource::collection($schedules);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $activeSchoolYear = SchoolYear::active()->first();

        // Get today's day of week (1=Senin ... 6=Sabtu, 0=Minggu)
        $todayDow = now()->dayOfWeekIso; // 1=Monday matches our convention

        $todaySchedules = $activeSchoolYear
            ? Schedule::query()
                ->where('school_year_id', $activeSchoolYear->id)
                ->where('day_of_week', $todayDow)
                ->active()
                ->with(['classRoom', 'subject', 'teacherProfile'])
                ->orderBy('start_time')
                ->limit(10)
                ->get()
            : collect();

        return Inertia::render('Admin/Dashboard', [
            'stats' => Inertia::defer(fn () => [
                'totalStudents' => StudentProfile::count(),
                'totalTeachers' => TeacherProfile::count(),
                'totalClasses' => $activeSchoolYear
                    ? ClassRoom::where('school_year_id', $activeSchoolYear->id)->active()->count()
                    : 0,
                'totalSubjects' => Subject::active()->count(),
            ]),
            'todaySchedules' => $todaySchedules,
            'activeSchoolYear' => $activeSchoolYear,
            'days' => Schedule::DAYS,
        ]);
    }
}

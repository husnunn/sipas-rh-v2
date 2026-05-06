<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceSitePickerApiResource;
use App\Http\Resources\StudentProfileResource;
use App\Models\AttendanceSite;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    public function me(Request $request): StudentProfileResource
    {
        $user = $request->user();
        $user->load([
            'studentProfile.activeClass.homeroomTeacher',
            'studentProfile.extension',
            'studentProfile.parents',
        ]);

        if (! $user->studentProfile) {
            abort(404, 'Profil siswa belum dibuat.');
        }

        $sites = AttendanceSite::query()
            ->forApiPicker()
            ->get(['id', 'name', 'latitude', 'longitude', 'radius_m']);

        return (new StudentProfileResource($user->studentProfile))->additional([
            'attendance_sites' => AttendanceSitePickerApiResource::collection($sites)->resolve(),
        ]);
    }
}

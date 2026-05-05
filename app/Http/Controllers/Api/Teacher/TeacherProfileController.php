<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceSitePickerApiResource;
use App\Http\Resources\TeacherProfileResource;
use App\Models\AttendanceSite;
use Illuminate\Http\Request;

class TeacherProfileController extends Controller
{
    public function me(Request $request): TeacherProfileResource
    {
        $user = $request->user();
        $user->load('teacherProfile.subjects');

        if (! $user->teacherProfile) {
            abort(404, 'Profil guru belum dibuat.');
        }

        $sites = AttendanceSite::query()
            ->forApiPicker()
            ->get(['id', 'name', 'latitude', 'longitude', 'radius_m']);

        return (new TeacherProfileResource($user->teacherProfile))->additional([
            'attendance_sites' => AttendanceSitePickerApiResource::collection($sites)->resolve(),
        ]);
    }
}

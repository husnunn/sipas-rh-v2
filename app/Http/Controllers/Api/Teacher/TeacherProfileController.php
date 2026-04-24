<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherProfileResource;
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

        return new TeacherProfileResource($user->teacherProfile);
    }
}

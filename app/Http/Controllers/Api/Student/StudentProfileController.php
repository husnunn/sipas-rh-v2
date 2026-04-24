<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentProfileResource;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    public function me(Request $request): StudentProfileResource
    {
        $user = $request->user();
        $user->load('studentProfile.activeClass');

        if (! $user->studentProfile) {
            abort(404, 'Profil siswa belum dibuat.');
        }

        return new StudentProfileResource($user->studentProfile);
    }
}

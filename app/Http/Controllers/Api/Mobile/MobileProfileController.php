<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\UpdateMobileProfilePasswordRequest;
use App\Http\Requests\Api\Mobile\UpdateMobileProfilePhotoRequest;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MobileProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        \assert($user instanceof User);

        if ($user->isStudent()) {
            return $this->studentProfileResponse($user);
        }

        if ($user->isTeacher()) {
            return $this->teacherProfileResponse($user);
        }

        return response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki akses untuk mengubah profil.',
        ], 403);
    }

    public function updatePhoto(UpdateMobileProfilePhotoRequest $request): JsonResponse
    {
        $user = $request->user();
        \assert($user instanceof User);

        $profile = $user->isStudent()
            ? $user->studentProfile
            : $user->teacherProfile;

        if ($profile === null) {
            return response()->json([
                'success' => false,
                'message' => 'Profil tidak ditemukan.',
            ], 404);
        }

        $file = $request->file('photo');
        \assert($file !== null);

        $directory = 'profile-photos/'.$user->id;
        $path = $file->store($directory, 'public');

        if ($profile->photo !== null && $profile->photo !== $path && Storage::disk('public')->exists($profile->photo)) {
            Storage::disk('public')->delete($profile->photo);
        }

        $profile->update(['photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            'data' => [
                'profile_photo_url' => $this->publicPhotoUrl($path),
            ],
        ]);
    }

    public function updatePassword(UpdateMobileProfilePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        \assert($user instanceof User);

        $user->update([
            'password' => $request->validated('password'),
            'plain_password' => null,
            'must_change_password' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    private function studentProfileResponse(User $user): JsonResponse
    {
        $user->loadMissing(['studentProfile.activeClass.schoolYear']);

        if ($user->studentProfile === null) {
            return response()->json([
                'success' => false,
                'message' => 'Profil siswa belum dibuat.',
            ], 404);
        }

        $activeClass = $user->studentProfile->activeClass->first();
        \assert($activeClass instanceof ClassRoom || $activeClass === null);

        $classPayload = null;
        if ($activeClass !== null) {
            $classPayload = [
                'id' => $activeClass->id,
                'name' => $activeClass->name,
            ];
        }

        $schoolPayload = $this->schoolPayloadFromClass($activeClass);

        return response()->json([
            'success' => true,
            'message' => 'Profile loaded successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'student',
                'profile_photo_url' => $this->publicPhotoUrl($user->studentProfile->photo),
                'school' => $schoolPayload,
                'class' => $classPayload,
            ],
        ]);
    }

    private function teacherProfileResponse(User $user): JsonResponse
    {
        $user->loadMissing('teacherProfile');

        if ($user->teacherProfile === null) {
            return response()->json([
                'success' => false,
                'message' => 'Profil guru belum dibuat.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile loaded successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'teacher',
                'profile_photo_url' => $this->publicPhotoUrl($user->teacherProfile->photo),
                'school' => $this->defaultSchoolPayload(),
                'class' => null,
            ],
        ]);
    }

    /**
     * @return array{id: int, name: string}
     */
    private function schoolPayloadFromClass(?ClassRoom $class): array
    {
        if ($class !== null) {
            $class->loadMissing('schoolYear');
            if ($class->schoolYear !== null) {
                return [
                    'id' => (int) $class->school_year_id,
                    'name' => $class->schoolYear->name,
                ];
            }
        }

        return $this->defaultSchoolPayload();
    }

    /**
     * @return array{id: int, name: string}
     */
    private function defaultSchoolPayload(): array
    {
        return [
            'id' => (int) config('app.school_id'),
            'name' => (string) config('app.school_name'),
        ];
    }

    private function publicPhotoUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return url(Storage::disk('public')->url($path));
    }
}

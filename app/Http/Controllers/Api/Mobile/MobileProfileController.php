<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\UpdateMobileProfilePasswordRequest;
use App\Http\Requests\Api\Mobile\UpdateMobileProfilePhotoRequest;
use App\Models\ClassRoom;
use App\Models\StudentParent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $user->loadMissing([
            'studentProfile.activeClass.schoolYear',
            'studentProfile.extension',
            'studentProfile.parents',
        ]);

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
                'user' => $this->userPayload($user),
                'profile' => [
                    'id' => $user->studentProfile->id,
                    'nis' => $user->studentProfile->nis,
                    'nisn' => $user->studentProfile->nisn,
                    'full_name' => $user->studentProfile->full_name,
                    'gender' => $user->studentProfile->gender,
                    'birth_date' => $user->studentProfile->birth_date?->toDateString(),
                    'birth_place' => $user->studentProfile->birth_place,
                    'phone' => $user->studentProfile->phone,
                    'address' => $user->studentProfile->address,
                    'parent_name' => $user->studentProfile->parent_name,
                    'parent_phone' => $user->studentProfile->parent_phone,
                ],
                'extension' => $this->studentExtensionPayload($user),
                'parents' => $this->studentParentsPayload($user),
            ],
        ]);
    }

    private function teacherProfileResponse(User $user): JsonResponse
    {
        $user->loadMissing([
            'teacherProfile.subjects',
            'teacherProfile.extension',
            'teacherProfile.homeroomClass',
        ]);

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
                'user' => $this->userPayload($user),
                'profile' => [
                    'id' => $user->teacherProfile->id,
                    'nip' => $user->teacherProfile->nip,
                    'full_name' => $user->teacherProfile->full_name,
                    'gender' => $user->teacherProfile->gender,
                    'phone' => $user->teacherProfile->phone,
                    'address' => $user->teacherProfile->address,
                ],
                'extension' => $this->teacherExtensionPayload($user),
                'subjects' => $user->teacherProfile->subjects
                    ->map(static fn ($subject): array => [
                        'id' => $subject->id,
                        'code' => $subject->code,
                        'name' => $subject->name,
                    ])
                    ->values(),
                'homeroom_classes' => $user->teacherProfile->homeroomClass
                    ->map(static fn (ClassRoom $class): array => [
                        'id' => $class->id,
                        'name' => $class->name,
                        'level' => $class->level,
                    ])
                    ->values(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $user->roles,
            'is_active' => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function studentExtensionPayload(User $user): ?array
    {
        $ext = $user->studentProfile?->extension;
        if ($ext === null) {
            return null;
        }

        return [
            'street_address' => $ext->street_address,
            'rt' => $ext->rt,
            'rw' => $ext->rw,
            'village' => $ext->village,
            'district' => $ext->district,
            'city' => $ext->city,
            'province' => $ext->province,
            'postal_code' => $ext->postal_code,
            'wilayah_village_id' => $ext->wilayah_village_id,
            'religion' => $ext->religion,
            'blood_type' => $ext->blood_type,
            'profile_photo_url' => $this->publicPhotoUrl($ext->profile_photo_path),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function studentParentsPayload(User $user): array
    {
        /** @var Collection<int, StudentParent> $rows */
        $rows = $user->studentProfile?->parents ?? collect();

        return $rows
            ->sortBy(static fn (StudentParent $row): string => $row->relation?->value ?? '')
            ->values()
            ->map(static fn (StudentParent $row): array => [
                'relation' => $row->relation?->value,
                'full_name' => $row->full_name,
                'occupation' => $row->occupation,
                'monthly_income_band' => $row->monthly_income_band,
                'nik' => $row->nik,
                'birth_date' => $row->birth_date?->toDateString(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function teacherExtensionPayload(User $user): ?array
    {
        $ext = $user->teacherProfile?->extension;
        if ($ext === null) {
            return null;
        }

        return [
            'birth_date' => $ext->birth_date?->toDateString(),
            'birth_place' => $ext->birth_place,
            'street_address' => $ext->street_address,
            'rt' => $ext->rt,
            'rw' => $ext->rw,
            'village' => $ext->village,
            'district' => $ext->district,
            'city' => $ext->city,
            'province' => $ext->province,
            'postal_code' => $ext->postal_code,
            'wilayah_village_id' => $ext->wilayah_village_id,
            'religion' => $ext->religion,
            'blood_type' => $ext->blood_type,
            'profile_photo_url' => $this->publicPhotoUrl($ext->profile_photo_path),
        ];
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

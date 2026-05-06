<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TeacherProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nip' => $this->nip,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'address' => $this->address,
            'photo_url' => $this->photo ? Storage::url($this->photo) : null,
            'subjects' => SubjectResource::collection($this->whenLoaded('subjects')),
            'extension' => $this->whenLoaded('extension', function () {
                if ($this->extension === null) {
                    return null;
                }

                return [
                    'birth_date' => $this->extension->birth_date?->toDateString(),
                    'birth_place' => $this->extension->birth_place,
                    'street_address' => $this->extension->street_address,
                    'rt' => $this->extension->rt,
                    'rw' => $this->extension->rw,
                    'village' => $this->extension->village,
                    'district' => $this->extension->district,
                    'city' => $this->extension->city,
                    'province' => $this->extension->province,
                    'postal_code' => $this->extension->postal_code,
                    'wilayah_village_id' => $this->extension->wilayah_village_id,
                    'religion' => $this->extension->religion,
                    'blood_type' => $this->extension->blood_type,
                    'profile_photo_url' => $this->extension->profile_photo_path
                        ? Storage::url($this->extension->profile_photo_path)
                        : null,
                ];
            }),
            'homeroom_classes' => $this->whenLoaded('homeroomClass', function () {
                return $this->homeroomClass
                    ->map(static function ($class): array {
                        return [
                            'id' => $class->id,
                            'name' => $class->name,
                            'level' => $class->level,
                        ];
                    })
                    ->values();
            }),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'email' => $this->user->email,
                'roles' => $this->user->roles,
                'is_active' => $this->user->is_active,
                'must_change_password' => $this->user->must_change_password,
                'last_login_at' => $this->user->last_login_at?->toIso8601String(),
                'email_verified_at' => $this->user->email_verified_at?->toIso8601String(),
            ],
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeClass = $this->whenLoaded('activeClass', function () {
            $class = $this->activeClass->first();

            return $class ? [
                'id' => $class->id,
                'name' => $class->name,
                'level' => $class->level,
                'homeroom_teacher' => $class->homeroomTeacher?->full_name,
            ] : null;
        });

        return [
            'id' => $this->id,
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'birth_place' => $this->birth_place,
            'phone' => $this->phone,
            'address' => $this->address,
            'parent_name' => $this->parent_name,
            'parent_phone' => $this->parent_phone,
            'photo_url' => $this->photo ? Storage::url($this->photo) : null,
            'current_class' => $activeClass,
            'extension' => $this->whenLoaded('extension', function () {
                if ($this->extension === null) {
                    return null;
                }

                return [
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
            'parents' => $this->whenLoaded('parents', function () {
                return $this->parents
                    ->sortBy('relation')
                    ->values()
                    ->map(static function ($row): array {
                        return [
                            'relation' => $row->relation?->value,
                            'full_name' => $row->full_name,
                            'occupation' => $row->occupation,
                            'monthly_income_band' => $row->monthly_income_band,
                            'nik' => $row->nik,
                            'birth_date' => $row->birth_date?->toDateString(),
                        ];
                    });
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

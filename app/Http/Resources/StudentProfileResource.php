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
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'is_active' => $this->user->is_active,
            ],
        ];
    }
}

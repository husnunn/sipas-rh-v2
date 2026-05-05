<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAttendanceRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = (new AttendanceRecordResource($this->resource))->toArray($request);

        $user = $this->user;
        $civitas = '-';
        if ($user !== null) {
            $roles = $user->roles ?? [];
            if (in_array('teacher', $roles, true)) {
                $civitas = 'guru';
            } elseif (in_array('student', $roles, true)) {
                $civitas = 'siswa';
            } elseif (in_array('admin', $roles, true)) {
                $civitas = 'admin';
            }
        }

        return array_merge($base, [
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'username' => $user?->username,
                'roles' => $user?->roles ?? [],
            ],
            'civitas' => $civitas,
            'class' => $this->schedule?->classRoom !== null
                ? [
                    'id' => $this->schedule->classRoom->id,
                    'name' => $this->schedule->classRoom->name,
                ]
                : null,
            'subject' => $this->schedule?->subject !== null
                ? [
                    'id' => $this->schedule->subject->id,
                    'name' => $this->schedule->subject->name,
                ]
                : null,
            'school_year' => $this->schedule?->schoolYear !== null
                ? [
                    'id' => $this->schedule->schoolYear->id,
                    'name' => $this->schedule->schoolYear->name,
                ]
                : null,
            'schedule_semester' => $this->schedule?->semester,
        ]);
    }
}

<?php

namespace App\Http\Resources;

use App\Services\Attendance\SchoolAttendanceTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_type' => $this->attendance_type,
            'status' => $this->status,
            'attendance_time' => $this->attendance_time,
            'reason_code' => $this->reason_code,
            'reason_detail' => $this->reason_detail,
            'distance_m' => $this->distance_m,
            'site' => $this->whenLoaded('attendanceSite', fn () => [
                'id' => $this->attendanceSite?->id,
                'name' => $this->attendanceSite?->name,
            ]),
            'schedule_id' => $this->schedule_id,
            'network' => $this->network_payload,
            'location' => $this->location_payload,
            'created_at' => $this->created_at
                ?->copy()
                ->setTimezone(SchoolAttendanceTime::timezone())
                ->toIso8601String(),
        ];
    }
}

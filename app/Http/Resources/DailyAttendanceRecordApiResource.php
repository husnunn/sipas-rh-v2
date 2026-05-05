<?php

namespace App\Http\Resources;

use App\Models\DailyAttendance;
use App\Services\Attendance\SchoolAttendanceTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DailyAttendance
 */
class DailyAttendanceRecordApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tz = SchoolAttendanceTime::timezone();

        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'check_in_at' => $this->check_in_at !== null
                ? Carbon::instance($this->check_in_at)->timezone($tz)->toIso8601String()
                : null,
            'check_out_at' => $this->check_out_at !== null
                ? Carbon::instance($this->check_out_at)->timezone($tz)->toIso8601String()
                : null,
            'status' => $this->status?->value,
            'late_minutes' => $this->late_minutes,
            'site' => $this->whenLoaded('attendanceSite', fn () => [
                'id' => $this->attendanceSite?->id,
                'name' => $this->attendanceSite?->name,
            ]),
        ];
    }
}

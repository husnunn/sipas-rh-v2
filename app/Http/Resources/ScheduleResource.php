<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'day' => Schedule::DAYS[$this->day_of_week] ?? 'Unknown',
            'day_of_week' => $this->day_of_week,
            'start_time' => substr($this->start_time, 0, 5),
            'end_time' => substr($this->end_time, 0, 5),
            'room' => $this->room,
            'semester' => $this->semester,
            'notes' => $this->notes,
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject->id,
                'code' => $this->subject->code,
                'name' => $this->subject->name,
            ]),
            'class' => $this->whenLoaded('classRoom', fn () => [
                'id' => $this->classRoom->id,
                'name' => $this->classRoom->name,
                'level' => $this->classRoom->level,
            ]),
            'teacher' => $this->whenLoaded('teacherProfile', fn () => [
                'id' => $this->teacherProfile->id,
                'full_name' => $this->teacherProfile->full_name,
                'nip' => $this->teacherProfile->nip,
            ]),
            'school_year' => $this->whenLoaded('schoolYear', fn () => $this->schoolYear->name),
        ];
    }
}

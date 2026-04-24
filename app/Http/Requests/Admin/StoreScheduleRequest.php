<?php

namespace App\Http\Requests\Admin;

use App\Rules\NoClassScheduleConflict;
use App\Rules\NoTeacherScheduleConflict;
use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'school_year_id' => ['required', 'exists:school_years,id'],
            'semester' => ['required', 'integer', 'in:1,2'],
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_profile_id' => ['required', 'exists:teacher_profiles,id'],
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'start_time' => ['required', 'date_format:H:i', new NoTeacherScheduleConflict, new NoClassScheduleConflict],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_time.after' => 'Jam selesai harus lebih besar dari jam mulai.',
            'day_of_week.between' => 'Hari harus antara 1 (Senin) sampai 6 (Sabtu).',
            'semester.in' => 'Semester harus 1 atau 2.',
        ];
    }
}

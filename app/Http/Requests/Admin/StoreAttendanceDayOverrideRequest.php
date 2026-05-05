<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesAttendancePolicyTimeInputs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceDayOverrideRequest extends FormRequest
{
    use NormalizesAttendancePolicyTimeInputs;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'event_type' => ['required', 'in:early_dismissal,teacher_meeting,special_event,holiday_override,attendance_closed,custom'],
            'is_active' => ['sometimes', 'boolean'],
            'attendance_site_id' => ['nullable', 'integer', 'exists:attendance_sites,id'],
            'override_attendance_policy' => ['sometimes', 'boolean'],
            'override_schedule' => ['sometimes', 'boolean'],
            'allow_check_in' => ['sometimes', 'boolean'],
            'allow_check_out' => ['sometimes', 'boolean'],
            'waive_check_out' => ['sometimes', 'boolean'],
            'dismiss_students_early' => ['sometimes', 'boolean'],
            'check_in_open_at' => ['nullable', 'date_format:H:i'],
            'check_in_on_time_until' => ['nullable', 'date_format:H:i'],
            'check_in_close_at' => ['nullable', 'date_format:H:i'],
            'check_out_open_at' => ['nullable', 'date_format:H:i'],
            'check_out_close_at' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $data = $this->validated();
                $overrideAttendancePolicy = (bool) ($data['override_attendance_policy'] ?? false);

                if ($overrideAttendancePolicy) {
                    $required = [
                        'check_in_open_at',
                        'check_in_on_time_until',
                        'check_in_close_at',
                        'check_out_open_at',
                        'check_out_close_at',
                    ];
                    foreach ($required as $field) {
                        if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                            $validator->errors()->add($field, 'Wajib diisi saat override policy aktif.');
                        }
                    }
                }

                $inOpen = $data['check_in_open_at'] ?? null;
                $inOnTime = $data['check_in_on_time_until'] ?? null;
                $inClose = $data['check_in_close_at'] ?? null;
                if ($inOpen && $inOnTime && $inClose && ! ($inOpen <= $inOnTime && $inOnTime <= $inClose)) {
                    $validator->errors()->add('check_in_on_time_until', 'Urutan jam check-in harus open <= on_time_until <= close.');
                }

                $outOpen = $data['check_out_open_at'] ?? null;
                $outClose = $data['check_out_close_at'] ?? null;
                if ($outOpen && $outClose && ! ($outOpen <= $outClose)) {
                    $validator->errors()->add('check_out_close_at', 'Urutan jam check-out harus open <= close.');
                }
            },
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Enums\AttendanceManualType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceManualStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'type' => ['required', Rule::enum(AttendanceManualType::class)],
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attendance_site_id' => ['nullable', 'integer', 'exists:attendance_sites,id'],
        ];
    }
}

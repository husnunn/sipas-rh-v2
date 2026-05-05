<?php

namespace App\Http\Requests\Api\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
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
            'attendance_site_id' => ['required', 'integer', 'exists:attendance_sites,id'],
            'attendance_type' => ['required', 'in:check_in,check_out'],
            'client_time' => ['nullable', 'date'],
            'network' => ['required', 'array'],
            'network.ssid' => ['nullable', 'string', 'max:255'],
            'network.bssid' => ['nullable', 'string', 'max:17'],
            'network.local_ip' => ['nullable', 'ip'],
            'network.gateway_ip' => ['nullable', 'ip'],
            'network.subnet_prefix' => ['nullable', 'integer', 'min:0', 'max:32'],
            'network.transport' => ['nullable', 'string', 'max:20'],
            'location' => ['required', 'array'],
            'location.latitude' => ['required', 'numeric', 'between:-90,90'],
            'location.longitude' => ['required', 'numeric', 'between:-180,180'],
            'location.accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'location.provider' => ['nullable', 'string', 'max:50'],
            'location.is_mock' => ['nullable', 'boolean'],
            'location.captured_at' => ['nullable', 'date'],
            'device' => ['nullable', 'array'],
            'device.platform' => ['nullable', 'string', 'max:20'],
            'device.app_version' => ['nullable', 'string', 'max:30'],
            'device.os_version' => ['nullable', 'string', 'max:30'],
        ];
    }
}

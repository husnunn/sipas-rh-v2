<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesAttendancePolicyTimeInputs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceSiteRequest extends FormRequest
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_m' => ['required', 'integer', 'min:1', 'max:10000'],
            'check_in_open_at' => ['nullable', 'date_format:H:i'],
            'check_in_on_time_until' => ['nullable', 'date_format:H:i'],
            'check_in_close_at' => ['nullable', 'date_format:H:i'],
            'check_out_open_at' => ['nullable', 'date_format:H:i'],
            'check_out_close_at' => ['nullable', 'date_format:H:i'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'wifi_rules' => ['nullable', 'array'],
            'wifi_rules.*.ssid' => ['required_with:wifi_rules', 'string', 'max:255'],
            'wifi_rules.*.bssid' => ['nullable', 'string', 'max:17'],
            'wifi_rules.*.ip_subnet' => ['nullable', 'string', 'max:43'],
            'wifi_rules.*.is_active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $data = $this->validated();

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

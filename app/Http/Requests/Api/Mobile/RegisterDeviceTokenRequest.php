<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegisterDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:8192'],
            'platform' => ['sometimes', 'nullable', 'string', Rule::in(['android', 'ios'])],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'os_version' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Data tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

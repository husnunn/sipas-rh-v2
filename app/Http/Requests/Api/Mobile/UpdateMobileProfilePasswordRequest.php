<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateMobileProfilePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Password lama tidak sesuai.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $message = 'Data tidak valid.';
        if ($validator->errors()->has('current_password')) {
            $message = 'Password lama tidak sesuai.';
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $validator->errors(),
        ], 422));
    }
}

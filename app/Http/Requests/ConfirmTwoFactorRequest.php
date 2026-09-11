<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Debes ingresar el codigo de 6 digitos.',
            'code.regex' => 'El codigo debe tener exactamente 6 digitos.',
        ];
    }
}

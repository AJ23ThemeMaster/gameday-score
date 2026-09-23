<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para actualizar un coach existente. Mismas reglas que
 * StoreCoachRequest pero la regla unique del documento excluye al
 * propio coach.
 */
class UpdateCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teamId = $this->route('team');
        $coachId = $this->route('coach');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('coaches', 'document_id')
                    ->where('team_id', $teamId)
                    ->ignore($coachId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'max:80'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'document_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'document_id.unique' => 'Ya existe un entrenador con ese documento en este equipo.',
            'user_id.exists' => 'El usuario seleccionado no existe.',
        ];
    }
}

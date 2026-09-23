<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear un coach dentro de un equipo.
 *
 * El coach es scoped al team (FK teams.team_id). Documento es unico
 * por equipo (no global). user_id es opcional y debe existir si se
 * envia.
 */
class StoreCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teamId = $this->route('team');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('coaches', 'document_id')->where('team_id', $teamId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'max:80'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            // Fotos: nullable, debe ser imagen, max 2MB.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'document_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // user_id opcional: si se envia, debe existir en users.
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

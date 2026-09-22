<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * DISI-delegado: validacion para crear un usuario desde el panel admin.
 * El email debe ser unico y la password obligatoria (min 8 caracteres).
 * team_id y category_id son opcionales pero, si se envian, deben existir.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(\App\Models\User::class, 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(\Spatie\Permission\Models\Role::class, 'name')->where('guard_name', 'web')],
            'team_id' => ['nullable', 'integer', Rule::exists(\App\Models\Team::class, 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists(\App\Models\Category::class, 'id')],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
            'team_id.exists' => 'El equipo seleccionado no existe.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }
}

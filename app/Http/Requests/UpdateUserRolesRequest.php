<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(\Spatie\Permission\Models\Role::class, 'name')->where('guard_name', 'web')],
            // DISI-80: equipo asociado (opcional). Validamos exists solo si se
            // envia un valor no nulo.
            'team_id' => ['nullable', 'integer', Rule::exists(\App\Models\Team::class, 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
            'team_id.exists' => 'El equipo seleccionado no existe.',
        ];
    }
}

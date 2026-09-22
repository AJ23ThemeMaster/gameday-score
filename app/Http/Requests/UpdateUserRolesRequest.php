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
            // DISI-delegado: categoria asociada (opcional). Requerida solo si el
            // usuario tiene el rol delegado. Validamos exists si se envia valor.
            'category_id' => ['nullable', 'integer', Rule::exists(\App\Models\Category::class, 'id')],
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

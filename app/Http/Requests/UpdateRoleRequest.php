<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9_\-]+$/i',
                Rule::unique(Role::class, 'name')
                    ->where('guard_name', 'web')
                    ->ignore($roleId),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists(Permission::class, 'name')->where('guard_name', 'web')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.regex' => 'El nombre solo puede tener letras, números, guion y guion bajo.',
            'name.unique' => 'Ya existe un rol con ese nombre.',
            'permissions.*.exists' => 'Uno de los permisos seleccionados no existe.',
        ];
    }
}

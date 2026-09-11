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
            'roles.*' => ['string', Rule::exists(Role::class, 'name')->where('guard_name', 'web')],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }
}

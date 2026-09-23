<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para actualizar un roster existente.
 *
 * La logica es identica a StoreRosterRequest pero la regla de unicidad
 * del nombre ignora el roster actual (caso comun: solo se cambia la
 * temporada sin tocar el resto).
 */
class UpdateRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Team $team */
        $team = $this->route('team');
        /** @var \App\Models\Roster $roster */
        $roster = $this->route('roster');

        return [
            // Ver StoreRosterRequest: categories.team_id es nullable, asi
            // que validamos cualquier categoria activa. La FK del roster
            // ya garantiza que el roster pertenece a $team.
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('active', true)),
            ],
            'name' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('rosters', 'name')
                    ->ignore($roster->id)
                    ->where(fn ($q) => $q
                        ->where('team_id', $team->id)
                        ->where('category_id', $this->input('category_id'))),
            ],
            'manager_coach_id' => [
                'nullable',
                'integer',
                Rule::exists('coaches', 'id')->where(fn ($q) => $q->where('team_id', $team->id)),
            ],
            'delegate_user_id' => [
                'nullable',
                'integer',
                function (string $attribute, $value, \Closure $fail) use ($team): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $user = User::find($value);
                    if (! $user) {
                        $fail('El delegado seleccionado no existe.');
                        return;
                    }
                    if (! $user->hasRole('delegado')) {
                        $fail('El usuario seleccionado no tiene el rol delegado.');
                        return;
                    }
                    if ((int) $user->team_id !== (int) $team->id) {
                        $fail('El delegado debe pertenecer al mismo equipo del roster.');
                        return;
                    }
                    $categoryId = $this->input('category_id');
                    if ($categoryId && (int) $user->category_id !== (int) $categoryId) {
                        $fail('El delegado debe estar asociado a la categoría del roster.');
                    }
                },
            ],
            'coaches' => ['nullable', 'array'],
            'coaches.*' => [
                'integer',
                Rule::exists('coaches', 'id')->where(fn ($q) => $q->where('team_id', $team->id)),
            ],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe o está inactiva.',
            'name.max' => 'El nombre no puede tener más de 120 caracteres.',
            'name.unique' => 'Ya existe un roster con este nombre en este (equipo, categoría).',
            'manager_coach_id.exists' => 'El manager seleccionado no pertenece a este equipo.',
            'coaches.*.exists' => 'Uno de los entrenadores seleccionados no pertenece a este equipo.',
        ];
    }
}

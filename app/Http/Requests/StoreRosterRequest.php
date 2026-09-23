<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Coach;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear un roster por (equipo, categoria).
 *
 * Reglas de negocio aplicadas:
 *  - El nombre del roster es opcional (se usa para distinguir temporadas
 *    o aliases como "Temporada 2026"); pero si se da, debe ser unico
 *    dentro del (team, category).
 *  - manager_coach_id y delegate_user_id son opcionales pero, si vienen,
 *    deben pertenecer al equipo y rol correctos:
 *      - manager_coach_id: Coach con team_id = $team->id
 *      - delegate_user_id: User con rol 'delegado', team_id = $team->id
 *        y category_id = $category->id (el delegado solo puede actuar
 *        en su (equipo, categoria))
 *  - coaches[] (N adicionales via pivote): cada coach debe pertenecer al
 *    team del roster.
 *  - active: bool, default true.
 */
class StoreRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorizacion por scope (admin vs gestor) la hace el controller
        // antes de llamar a validated(). Este FormRequest solo valida
        // formato de los campos.
        return true;
    }

    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            // La columna categories.team_id es nullable en la BD, asi que
            // aceptamos cualquier categoria activa. La vinculacion real del
            // roster con el equipo es la FK rosters.team_id. Los atletas
            // del roster se derivan de athletes con team_id + category_id
            // coincidentes (no requiere categories.team_id).
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

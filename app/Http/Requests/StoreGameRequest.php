<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'stadium_id' => ['nullable', 'integer', 'exists:stadiums,id'],
            'home_team_id' => ['required', 'integer', 'exists:teams,id', 'different:away_team_id'],
            'away_team_id' => ['required', 'integer', 'exists:teams,id', 'different:home_team_id'],
            'scheduled_at' => ['required', 'date'],
            'status' => ['nullable', 'in:scheduled,in_progress,paused,completed,suspended,cancelled'],
            'is_public' => ['boolean'],
            'scorekeeper_ids' => ['nullable', 'array'],
            'scorekeeper_ids.*' => ['integer', 'exists:scorekeepers,id'],
            'referee_ids' => ['nullable', 'array'],
            'referee_ids.*' => ['integer', 'exists:referees,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Debes seleccionar una categoría.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'tournament_id.exists' => 'El torneo seleccionado no existe.',
            'home_team_id.required' => 'Debes seleccionar el equipo local.',
            'home_team_id.exists' => 'El equipo local seleccionado no existe.',
            'home_team_id.different' => 'El equipo local y visitante deben ser distintos.',
            'away_team_id.required' => 'Debes seleccionar el equipo visitante.',
            'away_team_id.exists' => 'El equipo visitante seleccionado no existe.',
            'away_team_id.different' => 'El equipo local y visitante deben ser distintos.',
            'stadium_id.exists' => 'El estadio seleccionado no existe.',
            'scheduled_at.required' => 'La fecha y hora del juego son obligatorias.',
            'scheduled_at.date' => 'La fecha y hora no tienen un formato válido.',
            'scorekeeper_ids.array' => 'Los anotadores deben ser una lista.',
            'scorekeeper_ids.*.exists' => 'Uno de los anotadores seleccionados no existe.',
            'referee_ids.array' => 'Los árbitros deben ser una lista.',
            'referee_ids.*.exists' => 'Uno de los árbitros seleccionados no existe.',
        ];
    }
}

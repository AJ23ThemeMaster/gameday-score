<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddAthleteToRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'athlete_id' => ['required', 'integer', 'exists:athletes,id'],
            'team_id' => ['required', 'integer', 'exists:teams,id', 'in:'.$this->route('game')->home_team_id.','.$this->route('game')->away_team_id],
            'lineup_order' => ['nullable', 'integer', 'between:1,30'],
            'position' => ['nullable', 'in:P,C,1B,2B,3B,SS,LF,CF,RF,DH'],
            'is_starter' => ['boolean'],
            'is_pitcher' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'athlete_id.required' => 'Debes seleccionar un atleta.',
            'athlete_id.exists' => 'El atleta seleccionado no existe.',
            'team_id.required' => 'Debes indicar el equipo (local o visitante).',
            'team_id.in' => 'El equipo debe ser el local o el visitante de este juego.',
            'lineup_order.between' => 'El orden de bateo debe estar entre :min y :max.',
        ];
    }
}

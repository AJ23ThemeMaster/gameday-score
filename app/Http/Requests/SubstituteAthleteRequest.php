<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubstituteAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'out_athlete_id' => ['required', 'integer', 'exists:athletes,id'],
            'in_athlete_id' => ['required', 'integer', 'exists:athletes,id', 'different:out_athlete_id'],
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'lineup_order' => ['nullable', 'integer', 'between:1,30'],
            'position' => ['nullable', 'in:P,C,1B,2B,3B,SS,LF,CF,RF,DH'],
            'is_pitcher' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'out_athlete_id.required' => 'Debes indicar qué atleta sale del juego.',
            'in_athlete_id.required' => 'Debes indicar qué atleta entra al juego.',
            'in_athlete_id.different' => 'El atleta que entra debe ser distinto al que sale.',
        ];
    }
}

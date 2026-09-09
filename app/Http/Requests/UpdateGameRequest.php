<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
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
}

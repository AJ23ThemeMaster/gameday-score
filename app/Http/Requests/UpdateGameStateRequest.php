<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'balls' => ['nullable', 'integer', 'between:0,3'],
            'strikes' => ['nullable', 'integer', 'between:0,2'],
            'outs' => ['nullable', 'integer', 'between:0,2'],
            'bases' => ['nullable', 'array'],
            'bases.first' => ['nullable', 'boolean'],
            'bases.second' => ['nullable', 'boolean'],
            'bases.third' => ['nullable', 'boolean'],
            'home_score' => ['nullable', 'integer', 'between:0,99'],
            'away_score' => ['nullable', 'integer', 'between:0,99'],
            'current_inning' => ['nullable', 'integer', 'between:1,30'],
            'inning_half' => ['nullable', 'in:top,bottom'],
            'status' => ['nullable', 'in:scheduled,in_progress,paused,completed,suspended,cancelled'],
            'mercy_rule_applied' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'balls.between' => 'Las bolas deben estar entre :min y :max.',
            'strikes.between' => 'Los strikes deben estar entre :min y :max.',
            'outs.between' => 'Los outs deben estar entre :min y :max.',
            'current_inning.between' => 'El inning debe estar entre :min y :max.',
            'inning_half.in' => 'La parte del inning debe ser "top" o "bottom".',
        ];
    }
}

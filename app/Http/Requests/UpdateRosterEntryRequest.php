<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRosterEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lineup_order' => ['nullable', 'integer', 'between:1,30'],
            'position' => ['nullable', 'in:P,C,1B,2B,3B,SS,LF,CF,RF,DH'],
            'is_starter' => ['boolean'],
            'is_pitcher' => ['boolean'],
        ];
    }
}

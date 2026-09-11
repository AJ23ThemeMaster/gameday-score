<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'name' => ['required', 'string', 'max:150'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'home_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/', 'max:7'],
            'away_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/', 'max:7'],
            'active' => ['boolean'],
            'remove_logo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tournament_id.exists' => 'El torneo seleccionado no existe.',
            'name.required' => 'El nombre del equipo es obligatorio.',
            'logo.image' => 'El logo debe ser una imagen válida.',
            'logo.mimes' => 'El logo debe ser JPG, PNG, WEBP o SVG.',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
        ];
    }
}

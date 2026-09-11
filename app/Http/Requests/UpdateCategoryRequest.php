<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'lowercase', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'innings_count' => ['required', 'integer', 'between:1,99'],
            'mercy_rule_difference' => ['required', 'integer', 'between:1,99'],
            'mercy_rule_inning' => ['required', 'integer', 'between:1,99'],
            'pitch_limit' => ['nullable', 'integer', 'between:1,999'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'team_id.exists' => 'El equipo seleccionado no existe.',
            'name.required' => 'El nombre es obligatorio.',
            'slug.unique' => 'Ya existe otra categoría con ese slug.',
            'innings_count.between' => 'Los innings deben estar entre :min y :max.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'lowercase', Rule::unique('categories', 'slug')],
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
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de :max caracteres.',
            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'Ya existe una categoría con ese slug.',
            'slug.lowercase' => 'El slug debe estar en minúsculas.',
            'innings_count.required' => 'La cantidad de innings es obligatoria.',
            'innings_count.between' => 'Los innings deben estar entre :min y :max.',
            'mercy_rule_difference.required' => 'La diferencia del nocaut es obligatoria.',
            'mercy_rule_difference.between' => 'La diferencia debe estar entre :min y :max.',
            'mercy_rule_inning.required' => 'El inning del nocaut es obligatorio.',
            'mercy_rule_inning.between' => 'El inning debe estar entre :min y :max.',
            'pitch_limit.between' => 'El límite de lanzamientos debe estar entre :min y :max.',
        ];
    }
}

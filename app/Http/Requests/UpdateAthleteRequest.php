<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $athleteId = $this->route('athlete');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_id' => ['nullable', 'string', 'max:30', 'unique:athletes,document_id,'.$athleteId],
            'birth_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'document_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            // DISI-N: un atleta puede pertenecer a MAXIMO 2 categorias del
            // mismo equipo. La validacion fina de "todas del mismo team"
            // la hace Athlete::syncCategories() en el controller.
            'categories' => ['nullable', 'array', 'max:2'],
            'categories.*' => ['integer', 'exists:categories,id', 'distinct'],
            'number' => ['nullable', 'integer', 'between:0,99'],
            'position' => ['nullable', 'in:P,C,1B,2B,3B,SS,LF,CF,RF,DH'],
            'bats' => ['nullable', 'in:L,R,S'],
            'throws' => ['nullable', 'in:L,R'],
            'active' => ['boolean'],
            'remove_photo' => ['boolean'],
            'remove_document' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'categories.max' => 'Un atleta puede pertenecer a máximo 2 categorías.',
            'categories.*.exists' => 'Una de las categorías seleccionadas no existe.',
            'categories.*.distinct' => 'No puedes asignar la misma categoría dos veces.',
        ];
    }
}

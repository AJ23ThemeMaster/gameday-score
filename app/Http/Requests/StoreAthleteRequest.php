<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_id' => ['nullable', 'string', 'max:30', 'unique:athletes,document_id'],
            'birth_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'number' => ['nullable', 'integer', 'between:0,99'],
            'position' => ['nullable', 'in:P,C,1B,2B,3B,SS,LF,CF,RF,DH'],
            'bats' => ['nullable', 'in:L,R,S'],
            'throws' => ['nullable', 'in:L,R'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'document_id.unique' => 'Ya existe un atleta con ese documento.',
            'photo.image' => 'La foto debe ser una imagen válida.',
            'photo.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'photo.max' => 'La foto no puede pesar más de 2 MB.',
            'team_id.exists' => 'El equipo seleccionado no existe.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'number.between' => 'El número debe estar entre :min y :max.',
        ];
    }
}

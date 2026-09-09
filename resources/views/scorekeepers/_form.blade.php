@php
    $person = $scorekeeper ?? null;
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <x-input-label for="first_name" :value="__('Nombre')" />
        <x-text-input id="first_name" name="first_name" type="text" class="block mt-1 w-full"
                      :value="old('first_name', $person->first_name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="last_name" :value="__('Apellido')" />
        <x-text-input id="last_name" name="last_name" type="text" class="block mt-1 w-full"
                      :value="old('last_name', $person->last_name ?? '')" required />
        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="document_id" :value="__('Cédula / Documento')" />
        <x-text-input id="document_id" name="document_id" type="text" maxlength="30"
                      class="block mt-1 w-full" :value="old('document_id', $person->document_id ?? '')" />
        <x-input-error :messages="$errors->get('document_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" :value="__('Teléfono')" />
        <x-text-input id="phone" name="phone" type="text" maxlength="30"
                      class="block mt-1 w-full" :value="old('phone', $person->phone ?? '')" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="email" :value="__('Correo electrónico')" />
        <x-text-input id="email" name="email" type="email" maxlength="150"
                      class="block mt-1 w-full" :value="old('email', $person->email ?? '')" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" :value="__('Notas')" />
        <textarea id="notes" name="notes" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $person->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="photo" :value="__('Foto')" />
        @if (! empty($person?->photo_path) && Storage::disk('public')->exists($person->photo_path))
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ $person->photoUrl }}" class="h-20 w-20 object-cover bg-gray-50 rounded-full border border-gray-200 p-1">
                <label class="flex items-center text-sm text-red-600">
                    <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-red-600">
                    <span class="ms-2">{{ __('Eliminar foto actual') }}</span>
                </label>
            </div>
        @endif
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
               class="mt-2 block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">{{ __('Formatos: JPG, PNG o WEBP. Tamaño máximo: 2 MB.') }}</p>
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $person->active ?? true) ? 'checked' : '' }}
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <label for="active" class="ms-2 text-sm text-gray-700">{{ __('Activo (visible para asignar a juegos)') }}</label>
    </div>

</div>

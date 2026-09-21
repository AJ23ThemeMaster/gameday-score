<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <x-input-label for="first_name" :value="__('Nombre')" />
        <x-text-input id="first_name" name="first_name" type="text" class="block mt-1 w-full"
                      :value="old('first_name', $scorekeeper->first_name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="last_name" :value="__('Apellido')" />
        <x-text-input id="last_name" name="last_name" type="text" class="block mt-1 w-full"
                      :value="old('last_name', $scorekeeper->last_name ?? '')" required />
        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="document_id" :value="__('Cédula / Documento')" />
        <x-text-input id="document_id" name="document_id" type="text" class="block mt-1 w-full"
                      :value="old('document_id', $scorekeeper->document_id ?? '')" />
        <x-input-error :messages="$errors->get('document_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" :value="__('Teléfono')" />
        <x-text-input id="phone" name="phone" type="tel" class="block mt-1 w-full"
                      :value="old('phone', $scorekeeper->phone ?? '')" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" :value="__('Correo electrónico (opcional)')" />
        <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                      :value="old('email', $scorekeeper->email ?? '')" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="photo" :value="__('Foto del anotador')" />
        @if (! empty($scorekeeper?->photo_path) && Storage::disk('public')->exists($scorekeeper->photo_path))
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ $scorekeeper->photoUrl }}" class="h-20 w-20 object-cover bg-wv-surface rounded-full border border-wv-border p-1">
                <label class="flex items-center text-sm text-wv-alert">
                    <input type="checkbox" name="remove_photo" value="1"
                           class="rounded border-wv-border bg-wv-surface text-wv-alert focus:ring-wv-alert mr-2">
                    <span class="ms-2">{{ __('Eliminar foto actual') }}</span>
                </label>
            </div>
        @endif
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
               class="mt-2 block w-full text-sm text-wv-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-wv-accent-soft file:text-wv-accent hover:file:bg-wv-accent">
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('JPG, PNG o WEBP. Tamaño máximo: 2 MB.') }}</p>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" :value="__('Notas')" />
        <textarea id="notes" name="notes" rows="3"
                  class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">{{ old('notes', $scorekeeper->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $scorekeeper->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Activo (visible para asignar a juegos)') }}
        </label>
    </div>

</div>
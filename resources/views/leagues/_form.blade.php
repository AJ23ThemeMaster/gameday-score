{{-- Formulario compartido para crear/editar liga. --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div class="md:col-span-2">
        <x-input-label for="name" :value="__('Nombre')" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                      :value="old('name', $league->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="short_name" :value="__('Nombre corto (opcional)')" />
        <x-text-input id="short_name" name="short_name" type="text" maxlength="50"
                      class="block mt-1 w-full" :value="old('short_name', $league->short_name ?? '')" />
        <x-input-error :messages="$errors->get('short_name')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Ej: FVB, PLBV, PONY.') }}</p>
    </div>

    <div>
        <x-input-label for="country" :value="__('País (opcional)')" />
        <x-text-input id="country" name="country" type="text" maxlength="80"
                      class="block mt-1 w-full" :value="old('country', $league->country ?? 'Venezuela')" />
        <x-input-error :messages="$errors->get('country')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Descripción')" />
        <textarea id="description" name="description" rows="3"
                  class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">{{ old('description', $league->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="logo" :value="__('Logo de la liga')" />
        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml"
               class="block mt-1 w-full text-sm text-wv-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-wv-accent-soft file:text-wv-accent hover:file:bg-wv-accent" />
        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">PNG, JPG, WebP o SVG. Máximo 2 MB.</p>

        @if (isset($league) && $league->logo_path)
            <div class="mt-3 flex items-center gap-3 p-3 bg-wv-bg border border-wv-border rounded-md">
                <img src="{{ $league->logo_url }}" alt="Logo de {{ $league->name }}"
                     class="h-14 w-14 object-contain rounded bg-white p-1">
                <label class="flex items-center text-sm text-wv-alert">
                    <input type="checkbox" name="remove_logo" value="1"
                           {{ old('remove_logo') ? 'checked' : '' }}
                           class="rounded border-wv-border bg-wv-surface text-wv-alert focus:ring-wv-alert mr-2">
                    {{ __('Eliminar logo actual') }}
                </label>
            </div>
        @endif
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $league->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Liga activa (visible en formularios de creación de torneos)') }}
        </label>
    </div>

</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div class="md:col-span-2">
        <x-input-label for="name" :value="__('Nombre del estadio')" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                      :value="old('name', $stadium->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="city" :value="__('Ciudad')" />
        <x-text-input id="city" name="city" type="text" class="block mt-1 w-full"
                      :value="old('city', $stadium->city ?? '')" />
        <x-input-error :messages="$errors->get('city')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="state" :value="__('Estado / Provincia')" />
        <x-text-input id="state" name="state" type="text" class="block mt-1 w-full"
                      :value="old('state', $stadium->state ?? '')" />
        <x-input-error :messages="$errors->get('state')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="address" :value="__('Dirección')" />
        <x-text-input id="address" name="address" type="text" class="block mt-1 w-full"
                      :value="old('address', $stadium->address ?? '')" />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="capacity" :value="__('Capacidad (espectadores)')" />
        <x-text-input id="capacity" name="capacity" type="number" min="0" max="999999"
                      class="block mt-1 w-full" :value="old('capacity', $stadium->capacity ?? '')" />
        <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" :value="__('Notas')" />
        <textarea id="notes" name="notes" rows="3"
                  class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">{{ old('notes', $stadium->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $stadium->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Estadio activo (visible en formularios de creación de juegos)') }}
        </label>
    </div>

</div>
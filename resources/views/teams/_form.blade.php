{{-- Formulario compartido para crear/editar equipo con upload de logo --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div class="md:col-span-2">
        <x-input-label for="name" :value="__('Nombre del equipo')" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                      :value="old('name', $team->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="tournament_id" :value="__('Torneo (opcional)')" />
        <select id="tournament_id" name="tournament_id"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">-- {{ __('Sin torneo asignado') }} --</option>
            @foreach ($tournaments ?? [] as $t)
                <option value="{{ $t->id }}" {{ (string) old('tournament_id', $team->tournament_id ?? '') === (string) $t->id ? 'selected' : '' }}>
                    {{ $t->league->short_name ?? $t->league->name }} — {{ $t->name }}{{ $t->category ? ' (' . $t->category . ')' : '' }}{{ $t->season ? ' [' . $t->season . ']' : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tournament_id')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">{{ __('DISI-14: 1 equipo pertenece a 1 torneo.') }}</p>
    </div>

    <div>
        <x-input-label for="short_name" :value="__('Nombre corto / Siglas')" />
        <x-text-input id="short_name" name="short_name" type="text" maxlength="50"
                      class="block mt-1 w-full" :value="old('short_name', $team->short_name ?? '')" />
        <x-input-error :messages="$errors->get('short_name')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">{{ __('Máx. 50 caracteres. Ej: LDC, TDA.') }}</p>
    </div>

    <div>
        <x-input-label for="city" :value="__('Ciudad')" />
        <x-text-input id="city" name="city" type="text" maxlength="100"
                      class="block mt-1 w-full" :value="old('city', $team->city ?? '')" />
        <x-input-error :messages="$errors->get('city')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="home_color" :value="__('Color local (hex)')" />
        <div class="mt-1 flex items-center gap-2">
            <input id="home_color" name="home_color" type="color" maxlength="7"
                   value="{{ old('home_color', $team->home_color ?? '#1a3d6e') }}"
                   class="h-10 w-16 rounded border border-gray-300 cursor-pointer">
            <x-text-input type="text" maxlength="7" pattern="^#([A-Fa-f0-9]{6})$"
                          class="block flex-1"
                          x-data x-init="$el.value = $el.value || '#1a3d6e'"
                          :value="old('home_color', $team->home_color ?? '#1a3d6e')"
                          oninput="document.getElementById('home_color').value = this.value" />
        </div>
        <x-input-error :messages="$errors->get('home_color')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="away_color" :value="__('Color visitante (hex)')" />
        <div class="mt-1 flex items-center gap-2">
            <input id="away_color_picker" name="away_color_picker" type="color"
                   value="{{ old('away_color', $team->away_color ?? '#ffffff') }}"
                   class="h-10 w-16 rounded border border-gray-300 cursor-pointer"
                   oninput="document.getElementById('away_color').value = this.value">
            <x-text-input id="away_color" name="away_color" type="text" maxlength="7" pattern="^#([A-Fa-f0-9]{6})$"
                          class="block flex-1"
                          :value="old('away_color', $team->away_color ?? '#ffffff')" />
        </div>
        <x-input-error :messages="$errors->get('away_color')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="logo" :value="__('Logo del equipo')" />
        @if (! empty($team?->logo_path) && Storage::disk('public')->exists($team->logo_path))
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ Storage::url($team->logo_path) }}" alt="Logo actual"
                     class="h-20 w-20 object-contain bg-gray-50 rounded-md border border-gray-200 p-1">
                <div>
                    <p class="text-sm text-gray-700">{{ __('Logo actual') }}</p>
                    <label class="mt-2 flex items-center text-sm text-red-600">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-red-600">
                        <span class="ms-2">{{ __('Eliminar logo actual') }}</span>
                    </label>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ __('Sube un nuevo logo para reemplazar el actual.') }}</p>
        @endif
        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml"
               class="mt-2 block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">
            {{ __('Formatos: JPG, PNG, WEBP o SVG. Tamaño máximo: 2 MB.') }}
        </p>
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $team->active ?? true) ? 'checked' : '' }}
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <label for="active" class="ms-2 text-sm text-gray-700">
            {{ __('Equipo activo (visible en formularios de creación de juegos)') }}
        </label>
    </div>

</div>

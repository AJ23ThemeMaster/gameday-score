{{-- Formulario compartido para crear/editar torneo. --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div class="md:col-span-2">
        <x-input-label for="league_id" :value="__('Liga')" />
        <select id="league_id" name="league_id" required
                class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            <option value="">-- {{ __('Selecciona una liga') }} --</option>
            @foreach ($leagues as $l)
                <option value="{{ $l->id }}" {{ (string) old('league_id', $tournament->league_id ?? request('league_id', '')) === (string) $l->id ? 'selected' : '' }}>
                    {{ $l->name }}@if ($l->short_name) ({{ $l->short_name }})@endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('league_id')" class="mt-2" />
    </div>

    {{-- DISI-57: equipos del torneo (multi-select). En el form de edicion
         listamos solo los teams del league del torneo; en el create los
         cargamos via AJAX despues de seleccionar la liga. --}}
    @isset($tournament)
        @if ($tournament->exists)
            <div class="md:col-span-2">
                <x-input-label :value="__('Equipos del torneo')" />
                @if ($availableTeams->isEmpty())
                    <p class="mt-1 text-sm text-wv-text-secondary italic">
                        {{ __('La liga seleccionada no tiene equipos registrados aún.') }}
                    </p>
                @else
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1 max-h-48 overflow-y-auto p-3 bg-wv-bg rounded-md border border-wv-border">
                        @php
                            $selectedTeamIds = old('team_ids', $tournament->teams->pluck('id')->toArray());
                        @endphp
                        @foreach ($availableTeams as $t)
                            <label class="flex items-center gap-2 text-sm hover:bg-wv-surface-hover px-2 py-1 rounded cursor-pointer text-wv-text">
                                <input type="checkbox" name="team_ids[]" value="{{ $t->id }}"
                                       {{ in_array($t->id, $selectedTeamIds, true) ? 'checked' : '' }}
                                       class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
                                @if ($t->logo_url)
                                    <img src="{{ $t->logo_url }}" alt="" class="h-4 w-4 object-contain bg-white rounded">
                                @endif
                                <span>{{ $t->name }}@if ($t->short_name) <span class="text-wv-text-secondary text-xs">({{ $t->short_name }})</span>@endif</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-wv-text-secondary">
                        {{ __('Selecciona los equipos que participan en este torneo. Solo aparecen equipos del mismo league (regla: 1 liga por equipo).') }}
                    </p>
                @endif
                <x-input-error :messages="$errors->get('team_ids')" class="mt-2" />
                <x-input-error :messages="$errors->get('team_ids.*')" class="mt-2" />
            </div>
        @endif
    @endisset

    <div class="md:col-span-2">
        <x-input-label for="name" :value="__('Nombre del torneo')" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                      :value="old('name', $tournament->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" :value="__('Categoría (opcional)')" />
        <x-text-input id="category" name="category" type="text" maxlength="80"
                      class="block mt-1 w-full" :value="old('category', $tournament->category ?? '')"
                      placeholder="Ej: Sub-12, Pony Bronce, Pre-juvenil" />
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="season" :value="__('Temporada (opcional)')" />
        <x-text-input id="season" name="season" type="text" maxlength="30"
                      class="block mt-1 w-full" :value="old('season', $tournament->season ?? '')"
                      placeholder="Ej: 2025-A" />
        <x-input-error :messages="$errors->get('season')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="starts_at" :value="__('Fecha de inicio (opcional)')" />
        <x-text-input id="starts_at" name="starts_at" type="date" class="block mt-1 w-full"
                      :value="old('starts_at', isset($tournament) && $tournament->starts_at ? $tournament->starts_at->format('Y-m-d') : '')" />
        <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="ends_at" :value="__('Fecha de fin (opcional)')" />
        <x-text-input id="ends_at" name="ends_at" type="date" class="block mt-1 w-full"
                      :value="old('ends_at', isset($tournament) && $tournament->ends_at ? $tournament->ends_at->format('Y-m-d') : '')" />
        <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Descripción')" />
        <textarea id="description" name="description" rows="3"
                  class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">{{ old('description', $tournament->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="logo" :value="__('Logo del torneo')" />
        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml"
               class="block mt-1 w-full text-sm text-wv-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-wv-accent-soft file:text-wv-accent hover:file:bg-wv-accent" />
        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">PNG, JPG, WebP o SVG. Máximo 2 MB.</p>

        @if (isset($tournament) && $tournament->logo_path)
            <div class="mt-3 flex items-center gap-3 p-3 bg-wv-bg border border-wv-border rounded-md">
                <img src="{{ $tournament->logo_url }}" alt="Logo de {{ $tournament->name }}"
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
               {{ old('active', $tournament->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Torneo activo (visible en formularios de creación de juegos)') }}
        </label>
    </div>

</div>
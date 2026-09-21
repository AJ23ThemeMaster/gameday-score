<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <x-input-label for="first_name" :value="__('Nombre')" />
        <x-text-input id="first_name" name="first_name" type="text" class="block mt-1 w-full"
                      :value="old('first_name', $athlete->first_name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="last_name" :value="__('Apellido')" />
        <x-text-input id="last_name" name="last_name" type="text" class="block mt-1 w-full"
                      :value="old('last_name', $athlete->last_name ?? '')" required />
        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="document_id" :value="__('Cédula / Documento')" />
        <x-text-input id="document_id" name="document_id" type="text" maxlength="30"
                      class="block mt-1 w-full" :value="old('document_id', $athlete->document_id ?? '')" />
        <x-input-error :messages="$errors->get('document_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="birth_date" :value="__('Fecha de nacimiento')" />
        <x-text-input id="birth_date" name="birth_date" type="date" class="block mt-1 w-full"
                      :value="old('birth_date', isset($athlete->birth_date) ? $athlete->birth_date->format('Y-m-d') : '')" />
        <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="team_id" :value="__('Equipo actual')" />
        <select id="team_id" name="team_id" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            <option value="">— {{ __('Sin equipo') }} —</option>
            @foreach ($teams as $team)
                <option value="{{ $team->id }}" {{ (string) old('team_id', $athlete->team_id ?? request('team_id', '')) === (string) $team->id ? 'selected' : '' }}>
                    {{ $team->name }}@if ($team->league) ({{ $team->league->short_name ?? $team->league->name }})@endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('team_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category_id" :value="__('Categoría')" />
        <select id="category_id" name="category_id" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            <option value="">— {{ __('Sin categoría') }} —</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string) old('category_id', $athlete->category_id ?? request('category_id', '')) === (string) $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}@if ($cat->team) ({{ $cat->team->short_name ?? $cat->team->name }})@endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('DISI-14: 1 atleta pertenece a 1 equipo Y 1 categoría.') }}</p>
    </div>

    <div>
        <x-input-label for="number" :value="__('Número (camiseta)')" />
        <x-text-input id="number" name="number" type="number" min="0" max="99"
                      class="block mt-1 w-full" :value="old('number', $athlete->number ?? '')" />
        <x-input-error :messages="$errors->get('number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="position" :value="__('Posición')" />
        <select id="position" name="position" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            <option value="">— {{ __('Sin posición') }} —</option>
            @foreach (['P' => 'P - Pitcher', 'C' => 'C - Catcher', '1B' => '1B', '2B' => '2B', '3B' => '3B', 'SS' => 'SS', 'LF' => 'LF - Left Field', 'CF' => 'CF - Center Field', 'RF' => 'RF - Right Field', 'DH' => 'DH - Designated Hitter'] as $key => $label)
                <option value="{{ $key }}" {{ old('position', $athlete->position ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('position')" class="mt-2" />
    </div>

    <div class="grid grid-cols-2 gap-2">
        <div>
            <x-input-label for="bats" :value="__('Bateo')" />
            <select id="bats" name="bats" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
                <option value="R" {{ old('bats', $athlete->bats ?? 'R') === 'R' ? 'selected' : '' }}>{{ __('Derecha') }} (R)</option>
                <option value="L" {{ old('bats', $athlete->bats ?? 'R') === 'L' ? 'selected' : '' }}>{{ __('Izquierda') }} (L)</option>
                <option value="S" {{ old('bats', $athlete->bats ?? 'R') === 'S' ? 'selected' : '' }}>{{ __('Switch') }} (S)</option>
            </select>
        </div>
        <div>
            <x-input-label for="throws" :value="__('Lanzamiento')" />
            <select id="throws" name="throws" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
                <option value="R" {{ old('throws', $athlete->throws ?? 'R') === 'R' ? 'selected' : '' }}>{{ __('Derecha') }} (R)</option>
                <option value="L" {{ old('throws', $athlete->throws ?? 'R') === 'L' ? 'selected' : '' }}>{{ __('Izquierda') }} (L)</option>
            </select>
        </div>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="photo" :value="__('Foto del atleta')" />
        @if (! empty($athlete?->photo_path) && Storage::disk('public')->exists($athlete->photo_path))
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ $athlete->photoUrl }}" alt="Foto actual"
                     class="h-20 w-20 object-cover bg-wv-surface rounded-full border border-wv-border p-1">
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
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Formatos: JPG, PNG o WEBP. Tamaño máximo: 2 MB.') }}</p>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="document_file" :value="__('Documento de identidad (cédula o acta de nacimiento)')" />
        @if (! empty($athlete?->document_file_path) && Storage::disk('public')->exists($athlete->document_file_path))
            <div class="mt-2 flex items-center gap-4">
                @if ($athlete->documentIsImage)
                    <img src="{{ $athlete->documentUrl }}" alt="Documento actual"
                         class="h-20 w-28 object-cover bg-wv-surface rounded border border-wv-border p-1">
                @else
                    <a href="{{ $athlete->documentUrl }}" target="_blank"
                       class="inline-flex items-center gap-2 text-sm text-wv-accent hover:text-wv-accent-hover underline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        {{ __('Ver documento actual') }}
                    </a>
                @endif
                <label class="flex items-center text-sm text-wv-alert">
                    <input type="checkbox" name="remove_document" value="1"
                           class="rounded border-wv-border bg-wv-surface text-wv-alert focus:ring-wv-alert mr-2">
                    <span class="ms-2">{{ __('Eliminar documento actual') }}</span>
                </label>
            </div>
        @endif
        <input id="document_file" name="document_file" type="file" accept="image/jpeg,image/png,image/webp,application/pdf"
               class="mt-2 block w-full text-sm text-wv-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-wv-accent-soft file:text-wv-accent hover:file:bg-wv-accent">
        <x-input-error :messages="$errors->get('document_file')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Formatos: JPG, PNG, WEBP o PDF. Tamaño máximo: 5 MB.') }}</p>
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $athlete->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Atleta activo (visible para asignar a juegos)') }}
        </label>
    </div>

</div>
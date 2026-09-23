<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="category_id" :value="__('Categoría')" />
            <select id="category_id" name="category_id"
                    class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm" required>
                <option value="">— {{ __('Seleccionar categoría') }} —</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" {{ (string) old('category_id', $roster->category_id ?? '') === (string) $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Solo se permiten atletas de esta categoría en el roster.') }}</p>
            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="name" :value="__('Nombre / temporada (opcional)')" />
            <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                          :value="old('name', $roster->name ?? '')" placeholder="Temporada 2026" />
            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Útil para distinguir temporadas dentro de la misma (equipo, categoría).') }}</p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="manager_coach_id" :value="__('Manager (entrenador principal, opcional)')" />
        <select id="manager_coach_id" name="manager_coach_id"
                class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            <option value="">— {{ __('Sin manager asignado') }} —</option>
            @foreach ($coaches as $c)
                <option value="{{ $c->id }}" {{ (string) old('manager_coach_id', $roster->manager_coach_id ?? '') === (string) $c->id ? 'selected' : '' }}>
                    {{ $c->full_name }}@if ($c->role) ({{ $c->role }})@endif
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Encargado principal del roster.') }}</p>
        <x-input-error :messages="$errors->get('manager_coach_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="coaches" :value="__('Entrenadores adicionales (opcional)')" />
        <select id="coaches" name="coaches[]" multiple size="6"
                class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            @foreach ($coaches as $c)
                @php
                    $selectedIds = old('coaches', isset($roster) && $roster->exists ? $roster->coaches->pluck('id')->all() : []);
                    $selectedIds = array_map('strval', $selectedIds);
                @endphp
                <option value="{{ $c->id }}" {{ in_array((string) $c->id, $selectedIds, true) ? 'selected' : '' }}>
                    {{ $c->full_name }}@if ($c->role) — {{ $c->role }}@endif
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Mantén Ctrl/Cmd presionado para seleccionar varios. El manager ya aparece arriba; si lo agregas aquí también se incluye.') }}</p>
        <x-input-error :messages="$errors->get('coaches')" class="mt-2" />
        <x-input-error :messages="$errors->get('coaches.*')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="delegate_user_id" :value="__('Delegado (opcional)')" />
        @if ($delegates->isEmpty())
            <p class="mt-1 text-sm text-wv-text-secondary italic">
                {{ __('No hay usuarios con rol delegado y scope (equipo, categoría) disponibles.') }}
            </p>
        @else
            <select id="delegate_user_id" name="delegate_user_id"
                    class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
                <option value="">— {{ __('Sin delegado asignado') }} —</option>
                @foreach ($delegates as $d)
                    <option value="{{ $d->id }}" {{ (string) old('delegate_user_id', $roster->delegate_user_id ?? '') === (string) $d->id ? 'selected' : '' }}>
                        {{ $d->name }}
                        @if ($d->category) — {{ $d->category->name }} @endif
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-wv-text-secondary">
                {{ __('El delegado debe tener rol «delegado» y pertenecer a la misma (equipo, categoría) del roster.') }}
            </p>
        @endif
        <x-input-error :messages="$errors->get('delegate_user_id')" class="mt-2" />
    </div>

    <div class="flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $roster->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Roster activo') }}
        </label>
    </div>
</div>

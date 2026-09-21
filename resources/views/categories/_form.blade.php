<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div class="md:col-span-2">
        <x-input-label for="name" :value="__('Nombre')" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                      :value="old('name', $category->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="team_id" :value="__('Equipo (opcional)')" />
        <select id="team_id" name="team_id"
                class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            <option value="">-- {{ __('Sin equipo (categoría global)') }} --</option>
            @foreach ($teams ?? [] as $t)
                <option value="{{ $t->id }}" {{ (string) old('team_id', $category->team_id ?? request('team_id', '')) === (string) $t->id ? 'selected' : '' }}>
                    {{ $t->name }}@if ($t->short_name) ({{ $t->short_name }})@endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('team_id')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('DISI-14: 1 categoría pertenece a 1 equipo. Déjalo vacío para una categoría global.') }}</p>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="slug" :value="__('Slug (URL amigable, en minúsculas)')" />
        <x-text-input id="slug" name="slug" type="text" class="block mt-1 w-full"
                      :value="old('slug', $category->slug ?? '')" required />
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Solo letras minúsculas, números y guiones. Ej: "pre-infantil".') }}</p>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Descripción')" />
        <textarea id="description" name="description" rows="3"
                  class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">{{ old('description', $category->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="innings_count" :value="__('Cantidad de innings')" />
        <x-text-input id="innings_count" name="innings_count" type="number" min="1" max="99"
                      class="block mt-1 w-full" :value="old('innings_count', $category->innings_count ?? 7)" required />
        <x-input-error :messages="$errors->get('innings_count')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="pitch_limit" :value="__('Límite de lanzamientos (opcional)')" />
        <x-text-input id="pitch_limit" name="pitch_limit" type="number" min="1" max="999"
                      class="block mt-1 w-full" :value="old('pitch_limit', $category->pitch_limit ?? '')" />
        <x-input-error :messages="$errors->get('pitch_limit')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="mercy_rule_difference" :value="__('Diferencia del nocaut')" />
        <x-text-input id="mercy_rule_difference" name="mercy_rule_difference" type="number" min="1" max="99"
                      class="block mt-1 w-full" :value="old('mercy_rule_difference', $category->mercy_rule_difference ?? 10)" required />
        <x-input-error :messages="$errors->get('mercy_rule_difference')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="mercy_rule_inning" :value="__('Inning desde el que aplica el nocaut')" />
        <x-text-input id="mercy_rule_inning" name="mercy_rule_inning" type="number" min="1" max="99"
                      class="block mt-1 w-full" :value="old('mercy_rule_inning', $category->mercy_rule_inning ?? 5)" required />
        <x-input-error :messages="$errors->get('mercy_rule_inning')" class="mt-2" />
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $category->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Categoría activa (visible en formularios de creación de juegos)') }}
        </label>
    </div>

</div>
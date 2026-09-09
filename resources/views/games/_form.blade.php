@php
    $selectedScorekeepers = old('scorekeeper_ids', $game->exists ? $game->scorekeepers->pluck('id')->toArray() : []);
    $selectedReferees = old('referee_ids', $game->exists ? $game->referees->pluck('id')->toArray() : []);
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <x-input-label for="category_id" :value="__('Categoría')" />
        <select id="category_id" name="category_id" required class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— {{ __('Selecciona') }} —</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" {{ (string) old('category_id', $game->category_id ?? '') === (string) $c->id ? 'selected' : '' }}>
                    {{ $c->name }} ({{ $c->innings_count }} innings, mercy -{{ $c->mercy_rule_difference }})
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="scheduled_at" :value="__('Fecha y hora')" />
        <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local"
                      class="block mt-1 w-full"
                      :value="old('scheduled_at', isset($game->scheduled_at) ? $game->scheduled_at->format('Y-m-d\TH:i') : '')" required />
        <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="home_team_id" :value="__('Equipo local')" />
        <select id="home_team_id" name="home_team_id" required class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— {{ __('Selecciona') }} —</option>
            @foreach ($teams as $t)
                <option value="{{ $t->id }}" {{ (string) old('home_team_id', $game->home_team_id ?? '') === (string) $t->id ? 'selected' : '' }}>
                    {{ $t->name }}{{ $t->short_name ? ' (' . $t->short_name . ')' : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('home_team_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="away_team_id" :value="__('Equipo visitante')" />
        <select id="away_team_id" name="away_team_id" required class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— {{ __('Selecciona') }} —</option>
            @foreach ($teams as $t)
                <option value="{{ $t->id }}" {{ (string) old('away_team_id', $game->away_team_id ?? '') === (string) $t->id ? 'selected' : '' }}>
                    {{ $t->name }}{{ $t->short_name ? ' (' . $t->short_name . ')' : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('away_team_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="stadium_id" :value="__('Estadio (opcional)')" />
        <select id="stadium_id" name="stadium_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— {{ __('Sin estadio definido') }} —</option>
            @foreach ($stadiums as $s)
                <option value="{{ $s->id }}" {{ (string) old('stadium_id', $game->stadium_id ?? '') === (string) $s->id ? 'selected' : '' }}>
                    {{ $s->name }}{{ $s->city ? ' — ' . $s->city : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('stadium_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="status" :value="__('Estado')" />
        <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach (['scheduled' => 'Programado', 'in_progress' => 'En vivo', 'paused' => 'Pausado', 'completed' => 'Finalizado', 'suspended' => 'Suspendido', 'cancelled' => 'Cancelado'] as $key => $label)
                <option value="{{ $key }}" {{ old('status', $game->status ?? 'scheduled') === $key ? 'selected' : '' }}>{{ __($label) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="scorekeeper_ids" :value="__('Anotadores (opcional)')" />
        <select id="scorekeeper_ids" name="scorekeeper_ids[]" multiple size="4"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($scorekeepers as $sk)
                <option value="{{ $sk->id }}" {{ in_array($sk->id, $selectedScorekeepers) ? 'selected' : '' }}>
                    {{ $sk->full_name }}{{ $sk->document_id ? ' — ' . $sk->document_id : '' }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">{{ __('Mantén Ctrl (o Cmd en Mac) para seleccionar varios.') }}</p>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="referee_ids" :value="__('Árbitros (opcional)')" />
        <select id="referee_ids" name="referee_ids[]" multiple size="4"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($referees as $rf)
                <option value="{{ $rf->id }}" {{ in_array($rf->id, $selectedReferees) ? 'selected' : '' }}>
                    {{ $rf->full_name }}{{ $rf->certification ? ' — ' . $rf->certification : '' }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">{{ __('Mantén Ctrl (o Cmd en Mac) para seleccionar varios.') }}</p>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" :value="__('Notas')" />
        <textarea id="notes" name="notes" rows="2" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $game->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="is_public" name="is_public" type="checkbox" value="1"
               {{ old('is_public', $game->is_public ?? false) ? 'checked' : '' }}
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <label for="is_public" class="ms-2 text-sm text-gray-700">
            {{ __('Juego público (cualquier persona con el enlace puede ver el avance en vivo sin login)') }}
        </label>
    </div>

</div>

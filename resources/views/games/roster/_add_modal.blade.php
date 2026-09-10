<div x-data="{ open: false, teamId: null, teamName: '', available: [], athleteId: '' }"
     @open-add-modal.window="open = true; teamId = $event.detail.teamId; teamName = $event.detail.teamName; available = $event.detail.available; athleteId = ''"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     style="display: none;">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4" @click.outside="open = false">
        <form method="POST" :action="`/games/{{ $game->id }}/roster/athletes`" class="p-6">
            @csrf
            <input type="hidden" name="team_id" :value="teamId">
            <input type="hidden" name="athlete_id" :value="athleteId">

            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('Agregar atleta al roster') }}</h3>
            <p class="text-sm text-gray-500 mb-4">
                {{ __('Equipo') }}: <span x-text="teamName" class="font-medium"></span>
            </p>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Atleta') }}</label>
                <select id="add-athlete-id" required
                        x-model="athleteId"
                        class="w-full border-gray-300 rounded-md text-sm"
                        :disabled="available.length === 0">
                    <option value="">{{ __('— Selecciona —') }}</option>
                    <template x-for="a in available" :key="a.id">
                        <option :value="a.id" x-text="`#${a.number || '?'} ${a.name}${a.position ? ' (' + a.position + ')' : ''}`"></option>
                    </template>
                </select>
                <p x-show="available.length === 0" class="mt-1 text-xs text-orange-600">
                    {{ __('Todos los atletas de este equipo ya están en el roster.') }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Orden de bateo') }}</label>
                    <input type="number" name="lineup_order" min="1" max="30"
                           class="w-full border-gray-300 rounded-md text-sm"
                           placeholder="{{ __('Opcional') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Posición') }}</label>
                    <select name="position" class="w-full border-gray-300 rounded-md text-sm">
                        <option value="">{{ __('— Sin asignar —') }}</option>
                        @foreach (['P' => 'P - Pitcher', 'C' => 'C - Catcher', '1B' => '1B', '2B' => '2B', '3B' => '3B', 'SS' => 'SS', 'LF' => 'LF', 'CF' => 'CF', 'RF' => 'RF', 'DH' => 'DH'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <label class="flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="is_starter" value="1" checked class="rounded border-gray-300 text-indigo-600">
                    <span class="ms-2">{{ __('Es titular') }}</span>
                </label>
                <label class="flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="is_pitcher" value="1" class="rounded border-gray-300 text-indigo-600">
                    <span class="ms-2">{{ __('Es el pitcher actual') }}</span>
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="open = false"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Cancelar') }}
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">
                    {{ __('Agregar') }}
                </button>
            </div>
        </form>
    </div>
</div>

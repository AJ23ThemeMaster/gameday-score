{{-- Patron Breeze: x-data inline, listener .window --}}
<div
    x-data="{
        show: false,
        teamId: null,
        teamName: '',
        available: [],
        athleteId: '',
        submitting: false,
    }"
    x-on:open-add-modal.window="show = true; teamId = $event.detail.teamId; teamName = $event.detail.teamName; available = $event.detail.available; athleteId = ''"
    x-on:close-add-modal.window="show = false; submitting = false"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-wv-bg/80"
    style="display: none;">
    <div class="bg-wv-surface border border-wv-border rounded-card shadow-2xl w-full max-w-lg mx-4" @click.outside="show = false">
        <form method="POST" :action="`/games/{{ $game->id }}/roster/athletes`" class="p-6"
              @submit.prevent="submitting = true; $store.roaster.submitAddForm($event.target)">
            @csrf
            <input type="hidden" name="team_id" :value="teamId">
            <input type="hidden" name="athlete_id" :value="athleteId">

            <h3 class="text-lg font-semibold text-wv-text mb-1">{{ __('Agregar atleta al roster') }}</h3>
            <p class="text-sm text-wv-text-secondary mb-4">
                {{ __('Equipo') }}: <span x-text="teamName" class="font-medium text-wv-accent"></span>
            </p>

            <div class="mb-3">
                <label class="block text-sm font-medium text-wv-text mb-1">{{ __('Atleta') }}</label>
                <select required
                        x-model="athleteId"
                        class="w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm"
                        :disabled="available.length === 0">
                    <option value="">{{ __('— Selecciona —') }}</option>
                    <template x-for="a in available" :key="a.id">
                        <option :value="a.id" x-text="`#${a.number || '?'} ${a.name}${a.position ? ' (' + a.position + ')' : ''}`"></option>
                    </template>
                </select>
                <p x-show="available.length === 0" class="mt-1 text-xs text-wv-accent">
                    {{ __('Todos los atletas de este equipo ya están en el roster.') }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium text-wv-text mb-1">{{ __('Orden de bateo') }}</label>
                    <input type="number" name="lineup_order" min="1" max="30"
                           class="w-full border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm"
                           placeholder="{{ __('Opcional') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-wv-text mb-1">{{ __('Posición') }}</label>
                    <select name="position" class="w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                        <option value="">{{ __('— Sin asignar —') }}</option>
                        @foreach (['P' => 'P - Pitcher', 'C' => 'C - Catcher', '1B' => '1B', '2B' => '2B', '3B' => '3B', 'SS' => 'SS', 'LF' => 'LF - Left Field', 'CF' => 'CF - Center Field', 'RF' => 'RF - Right Field', 'DH' => 'DH - Designated Hitter'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <label class="flex items-center text-sm text-wv-text">
                    <input type="checkbox" name="is_starter" value="1" checked
                           class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
                    <span class="ms-2">{{ __('Es titular') }}</span>
                </label>
                <label class="flex items-center text-sm text-wv-text">
                    <input type="checkbox" name="is_pitcher" value="1"
                           class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
                    <span class="ms-2">{{ __('Es el pitcher actual') }}</span>
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="show = false"
                        class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">
                    {{ __('Cancelar') }}
                </button>
                <button type="submit" :disabled="submitting || !athleteId"
                        class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card disabled:opacity-50">
                    <span x-show="!submitting">{{ __('Agregar') }}</span>
                    <span x-show="submitting">{{ __('Agregando...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
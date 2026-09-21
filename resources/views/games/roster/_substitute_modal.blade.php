{{-- Patron Breeze: x-data inline, listener .window --}}
<div
    x-data="{
        show: false,
        outId: null,
        outName: '',
        inId: '',
        teamId: null,
        teamName: '',
        available: [],
        lineupOrder: 0,
        position: '',
        isPitcher: false,
        submitting: false,
    }"
    x-on:open-substitute-modal.window="show = true; outId = $event.detail.outAthleteId; outName = $event.detail.outAthleteName; inId = ''; teamId = $event.detail.teamId; teamName = $event.detail.teamName; available = $event.detail.available; lineupOrder = $event.detail.currentLineupOrder || 0; position = $event.detail.currentPosition || ''; isPitcher = !!$event.detail.isPitcher"
    x-on:close-substitute-modal.window="show = false; submitting = false"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-wv-bg/80"
    style="display: none;">
    <div class="bg-wv-surface border border-wv-border rounded-card shadow-2xl w-full max-w-lg mx-4" @click.outside="show = false">
        <form method="POST" action="{{ route('games.roster.substitute', $game) }}" class="p-6"
              @submit.prevent="submitting = true; $store.roaster.submitSubstituteForm($event.target)">
            @csrf
            <input type="hidden" name="out_athlete_id" :value="outId">
            <input type="hidden" name="in_athlete_id" :value="inId">
            <input type="hidden" name="team_id" :value="teamId">
            <input type="hidden" name="lineup_order" :value="lineupOrder">
            <input type="hidden" name="position" :value="position">
            <input type="hidden" name="is_pitcher" :value="isPitcher ? '1' : '0'">

            <h3 class="text-lg font-semibold text-wv-text mb-1">{{ __('Sustitución') }}</h3>
            <p class="text-sm text-wv-text-secondary mb-4">
                {{ __('Sale') }}: <span x-text="outName" class="font-medium text-wv-alert"></span>
                {{ __('Entra en') }}: <span x-text="teamName" class="font-medium text-wv-accent"></span>
                <span class="text-xs text-wv-text-secondary" x-show="lineupOrder > 0">
                    ({{ __('orden') }} <span x-text="lineupOrder"></span><template x-if="position"> · <span x-text="position"></span></template>)
                </span>
            </p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-wv-text mb-1">{{ __('Atleta que entra') }}</label>
                <select required
                        x-model="inId"
                        class="w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm"
                        :disabled="available.length === 0">
                    <option value="">{{ __('— Selecciona —') }}</option>
                    <template x-for="a in available" :key="a.id">
                        <option :value="a.id" x-text="`#${a.number || '?'} ${a.name}${a.position ? ' (' + a.position + ')' : ''}`"></option>
                    </template>
                </select>
                <p x-show="available.length === 0" class="mt-1 text-xs text-wv-accent">
                    {{ __('No hay atletas suplentes disponibles de este equipo.') }}
                </p>
            </div>

            <div class="bg-wv-accent-soft border border-wv-accent/40 rounded-card p-3 mb-4 text-xs text-wv-text-secondary">
                <p>
                    <strong class="text-wv-text">{{ __('Nota') }}:</strong>
                    {{ __('El atleta que entra hereda la posición y el orden de bateo. Si es pitcher, las stats de pitches del lanzador anterior se conservan con él.') }}
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="show = false"
                        class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">
                    {{ __('Cancelar') }}
                </button>
                <button type="submit" :disabled="submitting || !inId"
                        class="inline-flex items-center px-4 py-2 bg-wv-alert hover:bg-wv-alert-hover text-wv-text-on-alert text-sm font-semibold rounded-card disabled:opacity-50">
                    <span x-show="!submitting">{{ __('Confirmar sustitución') }}</span>
                    <span x-show="submitting">{{ __('Procesando...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
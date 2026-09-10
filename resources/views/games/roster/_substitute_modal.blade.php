<div x-data="{ open: false, outAthleteId: null, inAthleteId: '', outAthleteName: '', teamId: null, teamName: '', available: [], currentLineupOrder: 0, currentPosition: '', isPitcher: false }"
     @open-substitute-modal.window="open = true; outAthleteId = $event.detail.outAthleteId; inAthleteId = ''; outAthleteName = $event.detail.outAthleteName; teamId = $event.detail.teamId; teamName = $event.detail.teamName; available = $event.detail.available; currentLineupOrder = $event.detail.currentLineupOrder; currentPosition = $event.detail.currentPosition; isPitcher = $event.detail.isPitcher"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     style="display: none;">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4" @click.outside="open = false">
        <form method="POST" action="{{ route('games.roster.substitute', $game) }}" class="p-6">
            @csrf
            <input type="hidden" name="out_athlete_id" :value="outAthleteId">
            <input type="hidden" name="in_athlete_id" :value="inAthleteId">
            <input type="hidden" name="team_id" :value="teamId">
            <input type="hidden" name="lineup_order" :value="currentLineupOrder">
            <input type="hidden" name="position" :value="currentPosition">
            <input type="hidden" name="is_pitcher" :value="isPitcher ? '1' : '0'">

            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('Sustitución') }}</h3>
            <p class="text-sm text-gray-500 mb-4">
                {{ __('Sale') }}: <span x-text="outAthleteName" class="font-medium text-red-600"></span>
                {{ __('Entra en') }}: <span x-text="teamName" class="font-medium text-indigo-600"></span>
                <span class="text-xs text-gray-500" x-show="currentLineupOrder > 0">
                    ({{ __('orden') }} <span x-text="currentLineupOrder"></span><template x-if="currentPosition"> · <span x-text="currentPosition"></span></template>)
                </span>
            </p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Atleta que entra') }}</label>
                <select id="sub-in-athlete-id" required
                        x-model="inAthleteId"
                        class="w-full border-gray-300 rounded-md text-sm"
                        :disabled="available.length === 0">
                    <option value="">{{ __('— Selecciona —') }}</option>
                    <template x-for="a in available" :key="a.id">
                        <option :value="a.id" x-text="`#${a.number || '?'} ${a.name}${a.position ? ' (' + a.position + ')' : ''}`"></option>
                    </template>
                </select>
                <p x-show="available.length === 0" class="mt-1 text-xs text-orange-600">
                    {{ __('No hay atletas suplentes disponibles de este equipo.') }}
                </p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-4 text-xs text-yellow-800">
                <p>
                    <strong>{{ __('Nota') }}:</strong>
                    {{ __('El atleta que entra hereda la posición y el orden de bateo. Si es pitcher, las stats de pitches del lanzador anterior se conservan con él.') }}
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="open = false"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Cancelar') }}
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-md">
                    {{ __('Confirmar sustitución') }}
                </button>
            </div>
        </form>
    </div>
</div>

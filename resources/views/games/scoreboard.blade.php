<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Scoreboard') }}: {{ $game->homeTeam->short_name ?? $game->homeTeam->name }} <span class="text-gray-400">vs</span> {{ $game->awayTeam->short_name ?? $game->awayTeam->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('games.show', $game) }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Detalle') }}</a>
                <a href="{{ route('games.roster.index', $game) }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Roster') }}</a>
            </div>
        </div>
    </x-slot>

    <style>
        /* MEJ-1: animacion slide al cambiar inning/half.
           El JS agrega la clase `is-flipping` por 600ms; el keyframe hace
           un slide-in desde arriba + slide-out hacia abajo + pulse de color
           para dar feedback claro del cambio. */
        @keyframes inning-flip {
            0%   { transform: translateY(-120%); opacity: 0; color: #f59e0b; }
            40%  { transform: translateY(0);     opacity: 1; color: #f59e0b; }
            70%  { transform: translateY(0);     opacity: 1; color: #111827; }
            100% { transform: translateY(0);     opacity: 1; color: #111827; }
        }
        .inning-anim-host.is-flipping {
            animation: inning-flip 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* MEJ-5: indicador del equipo que esta bateando.
           Cuando data-batting="1" se aplica un fondo verde MUY sutil
           y el nombre del equipo + label cambian a verde bold.
           SIN anillo, SIN escala, SIN box-shadow (estructura limpia
           similar al scoreboard del adjunto). */
        .team-zone {
            transition: background-color 0.3s ease;
            position: relative;
            border-radius: 1rem;
            padding: 1rem;
        }
        .team-zone[data-batting="1"] {
            background-color: rgba(16, 185, 129, 0.08);
        }
        .team-zone[data-batting="1"] .team-name {
            color: #047857;
        }
        .team-zone[data-batting="1"] .team-label-local,
        .team-zone[data-batting="1"] .team-label-away {
            color: #047857;
            font-weight: 700;
        }
    </style>

    <div
        class="py-6"
        x-data="scoreboardApp(@js([
            'gameId' => $game->id,
            'pollUrl' => route('games.scoreboard.poll', $game),
            'pitchUrl' => route('games.plays.pitch', $game),
            'endInningUrl' => route('games.plays.end-inning', $game),
            'endGameUrl' => route('games.plays.end-game', $game),
            'substituteUrl' => route('games.plays.substitute', $game),
            'runnerUrl' => route('games.plays.runner', $game),
            'homeName' => $game->homeTeam->name,
            'awayName' => $game->awayTeam->name,
            'homeShort' => $game->homeTeam->short_name ?? $game->homeTeam->name,
            'awayShort' => $game->awayTeam->short_name ?? $game->awayTeam->name,
            'homeTeamId' => $game->home_team_id,
            'awayTeamId' => $game->away_team_id,
            'csrf' => csrf_token(),
            'rosterAway' => $game->athletes()->wherePivot('team_id', $game->away_team_id)->orderBy('game_athlete.lineup_order')->get(['athletes.id', 'athletes.first_name', 'athletes.last_name', 'athletes.number', 'game_athlete.lineup_order', 'game_athlete.position'])->map(fn($a) => ['id' => $a->id, 'first_name' => $a->first_name, 'last_name' => $a->last_name, 'number' => $a->number, 'lineup_order' => $a->pivot->lineup_order, 'position' => $a->pivot->position])->values(),
            'rosterHome' => $game->athletes()->wherePivot('team_id', $game->home_team_id)->orderBy('game_athlete.lineup_order')->get(['athletes.id', 'athletes.first_name', 'athletes.last_name', 'athletes.number', 'game_athlete.lineup_order', 'game_athlete.position'])->map(fn($a) => ['id' => $a->id, 'first_name' => $a->first_name, 'last_name' => $a->last_name, 'number' => $a->number, 'lineup_order' => $a->pivot->lineup_order, 'position' => $a->pivot->position])->values(),
            'statsUrl' => route('games.scoreboard.stats', $game),
            'lineupReorderUrl' => route('games.lineup.reorder', $game),
            // DISI-33: estado inicial reactivo de la finalizacion del juego.
            // Si el juego ya esta finalizado al cargar la vista, isFinalized=true
            // y tab='extra' desde el principio (el render server-side ya puso
            // las secciones en su estado final). Si el juego se finaliza
            // durante la sesion, applyState() actualiza estos flags via poll.
            // Usamos $game->isCompleted() como fuente de verdad (no state.is_game_over)
            // porque el engine puede cambiar status a 'finalized' sin crear
            // una jugada Play::TYPE_GAME_END, dejando state.is_game_over=false.
            'isFinalized' => $game->isCompleted(),
            'tab' => $game->isCompleted() ? 'extra' : 'pitch',
        ]))"
        x-init="start()"
    >
        <div class="max-w-2xl mx-auto sm:px-4">

            {{-- ============ HEADER: LOGOS + SCORES + COUNT ============ --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

                {{-- Breadcrumb Liga / Torneo / Categoria (DISI-13) --}}
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center gap-2 text-xs">
                    @if ($game->tournament?->league)
                        <a href="{{ route('leagues.show', $game->tournament->league) }}"
                           class="inline-flex items-center gap-1.5 px-2 py-1 bg-white border border-gray-200 rounded-md hover:border-indigo-400 transition"
                           title="{{ $game->tournament->league->name }}">
                            @if ($game->tournament->league->logo_url)
                                <img src="{{ $game->tournament->league->logo_url }}" alt="" class="h-4 w-4 object-contain">
                            @endif
                            <span class="font-semibold text-gray-700">{{ $game->tournament->league->short_name ?? $game->tournament->league->name }}</span>
                        </a>
                        <span class="text-gray-400">/</span>
                    @endif
                    @if ($game->tournament)
                        <a href="{{ route('tournaments.show', $game->tournament) }}"
                           class="inline-flex items-center gap-1.5 px-2 py-1 bg-white border border-gray-200 rounded-md hover:border-indigo-400 transition"
                           title="{{ $game->tournament->name }}">
                            @if ($game->tournament->logo_url)
                                <img src="{{ $game->tournament->logo_url }}" alt="" class="h-4 w-4 object-contain">
                            @endif
                            <span class="font-semibold text-gray-700">{{ $game->tournament->name }}</span>
                            @if ($game->tournament->season)
                                <span class="text-gray-500 text-[10px]">({{ $game->tournament->season }})</span>
                            @endif
                        </a>
                        <span class="text-gray-400">/</span>
                    @endif
                    @if ($game->category)
                        <span class="inline-flex items-center px-2 py-1 bg-indigo-50 text-indigo-700 rounded-md font-bold">
                            {{ $game->category->name }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-500 rounded-md italic">
                            {{ __('Sin categoría') }}
                        </span>
                    @endif
                    {{-- DISI-39 + DISI-41: botones de accion rapida (esquina superior derecha) --}}
                    {{-- Ojito: detalle del juego (/games/{id}) --}}
                    {{-- Play: vista en vivo del juego (/games/{id}/live) — solo si is_public --}}
                    {{-- Lista: roster del juego (/games/{id}/roster) --}}
                    {{-- Casita: listado de juegos (/games) --}}
                    {{-- FIX DISI-40: los tags @if van PRIMERO en el DOM (fluyen desde la izquierda) --}}
                    {{-- y los botones DESPUES con ml-auto (empujan a la derecha). Antes el --}}
                    {{-- orden estaba invertido y ml-auto en el primer hijo de flex no --}}
                    {{-- funciona (no hay hermano anterior que empuje). --}}
                    <div class="ml-auto flex items-center gap-1.5">
                        <a href="{{ route('games.show', $game) }}"
                           class="inline-flex items-center justify-center h-7 w-7 bg-white border border-gray-200 rounded-md text-gray-600 hover:text-indigo-600 hover:border-indigo-400 transition"
                           title="{{ __('Ver detalle del juego') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        {{-- DISI-41: boton Live (▶ play) — solo si el juego fue creado --}}
                        {{-- con la opcion 'is_public' habilitada (mismo flag que la vista --}}
                        {{-- publica; el live view es para proyeccion/compartir marcador --}}
                        {{-- cuando el juego es publico). --}}
                        @if ($game->is_public)
                            <a href="{{ route('games.live', $game) }}"
                               target="_blank"
                               class="inline-flex items-center justify-center h-7 w-7 bg-white border border-emerald-300 rounded-md text-emerald-600 hover:text-emerald-700 hover:border-emerald-500 transition"
                               title="{{ __('Abrir vista en vivo (requiere juego público)') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                    <path d="M6.3 2.84A1 1 0 0 0 5 3.75v12.5a1 1 0 0 0 1.55.83l10-6.25a1 1 0 0 0 0-1.66l-10-6.25a1 1 0 0 0-.25-.08Z" />
                                </svg>
                            </a>
                        @endif
                        <a href="{{ route('games.roster.index', $game) }}"
                           class="inline-flex items-center justify-center h-7 w-7 bg-white border border-gray-200 rounded-md text-gray-600 hover:text-indigo-600 hover:border-indigo-400 transition"
                           title="{{ __('Ver roster del juego') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path fill-rule="evenodd" d="M2 3.75A.75.75 0 0 1 2.75 3h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 3.75Zm0 4.167a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Zm0 4.166a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Zm0 4.167a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="{{ route('games.index') }}"
                           class="inline-flex items-center justify-center h-7 w-7 bg-white border border-gray-200 rounded-md text-gray-600 hover:text-indigo-600 hover:border-indigo-400 transition"
                           title="{{ __('Ir al listado de juegos') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7a1 1 0 0 1 0 1.414l-7 7a1 1 0 0 1-1.414-1.414L14.586 11H3a1 1 0 1 1 0-2h11.586l-5.293-5.293a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- Header: Local | Score centrado | Visitante (estructura del adjunto) --}}
                <div class="grid grid-cols-3 items-center gap-2 sm:gap-4 border-b-2 border-gray-100 py-5 px-3 sm:px-6">

                    {{-- Local: logo arriba, nombre medio, label abajo (centrado en su columna) --}}
                    <div class="team-zone flex flex-col items-center gap-1.5"
                         data-team-zone="home"
                         data-batting="{{ $state['half'] === 'bottom' ? '1' : '0' }}">
                        @if ($game->homeTeam->logoUrl)
                            <img src="{{ $game->homeTeam->logoUrl }}" class="h-16 w-16 sm:h-20 sm:w-20 object-contain">
                        @else
                            <div class="h-16 w-16 sm:h-20 sm:w-20 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs">LOGO</div>
                        @endif
                        <div class="team-name text-sm sm:text-base font-bold text-gray-800 text-center leading-tight">{{ $game->homeTeam->name }}</div>
                        <div class="team-label-local text-[10px] sm:text-xs text-gray-500 uppercase tracking-wider">{{ __('Local') }}</div>
                    </div>

                    {{-- Centro: score "home - away" + Inning indicator --}}
                    <div class="flex flex-col items-center justify-center gap-1">
                        <div class="flex items-baseline gap-2 sm:gap-3 text-3xl sm:text-5xl font-black text-gray-900 leading-none">
                            <span data-score="home">{{ $score['home'] }}</span>
                            <span class="text-gray-400">-</span>
                            <span data-score="away">{{ $score['away'] }}</span>
                        </div>
                        <div class="text-xs sm:text-sm text-gray-500 flex items-center gap-1" data-inning-indicator>
                            <span>{{ __('Inning') }}</span>
                            <span class="font-bold text-gray-700 inning-anim-host" data-inning-number data-inning-anim>{{ $state['inning'] }}</span>
                            <span class="text-gray-600 inning-anim-host" data-inning-half data-inning-anim>{{ $state['half'] === 'top' ? '▲' : '▼' }}</span>
                        </div>
                        {{-- DISI-39: badge "JUEGO FINALIZADO" cuando el juego esta terminado --}}
                        {{-- Aparece centrado debajo del inning cuando $game->isCompleted()=true. --}}
                        {{-- Usa x-show para que la reactividad Alpine (DISI-34) lo muestre/oculte --}}
                        {{-- sin recargar cuando el juego se finaliza via poll. --}}
                        @if ($game->isCompleted())
                            <div class="mt-1 inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 border border-emerald-300 rounded-full text-emerald-800 text-[10px] sm:text-xs font-black uppercase tracking-wider shadow-sm"
                                 x-show="isFinalized"
                                 data-game-status="finalized">
                                <span>{{ __('Juego finalizado') }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Visitante: logo arriba, nombre medio, label abajo (centrado en su columna) --}}
                    <div class="team-zone flex flex-col items-center gap-1.5"
                         data-team-zone="away"
                         data-batting="{{ $state['half'] === 'top' ? '1' : '0' }}">
                        @if ($game->awayTeam->logoUrl)
                            <img src="{{ $game->awayTeam->logoUrl }}" class="h-16 w-16 sm:h-20 sm:w-20 object-contain">
                        @else
                            <div class="h-16 w-16 sm:h-20 sm:w-20 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs">LOGO</div>
                        @endif
                        <div class="team-name text-sm sm:text-base font-bold text-gray-800 text-center leading-tight">{{ $game->awayTeam->name }}</div>
                        <div class="team-label-away text-[10px] sm:text-xs text-gray-500 uppercase tracking-wider">{{ __('Visitante') }}</div>
                    </div>

                </div>

                {{-- Count: BOLAS / STRIKES / OUTS — solo visible mientras el juego esta en curso --}}
                @if (! $game->isCompleted())
                <div class="grid grid-cols-3 border-b-2 border-gray-100 text-center" x-show="!isFinalized">
                    <div class="py-3 px-2 border-r border-gray-100">
                        <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold mb-2">{{ __('Bolas') }}</div>
                        <div class="flex justify-center gap-2" data-balls>
                            @for ($i = 0; $i < 4; $i++)
                                <span class="w-3 h-3 rounded-full border border-gray-400 {{ $i < $state['balls'] ? 'bg-emerald-500 border-emerald-600' : 'bg-gray-100' }}"></span>
                            @endfor
                        </div>
                    </div>
                    <div class="py-3 px-2 border-r border-gray-100">
                        <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold mb-2">{{ __('Strikes') }}</div>
                        <div class="flex justify-center gap-2" data-strikes>
                            @for ($i = 0; $i < 3; $i++)
                                <span class="w-3 h-3 rounded-full border border-gray-400 {{ $i < $state['strikes'] ? 'bg-amber-500 border-amber-600' : 'bg-gray-100' }}"></span>
                            @endfor
                        </div>
                    </div>
                    <div class="py-3 px-2">
                        <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold mb-2">{{ __('Outs') }}</div>
                        <div class="flex justify-center gap-2" data-outs>
                            @for ($i = 0; $i < 3; $i++)
                                <span class="w-3 h-3 rounded-full border border-gray-400 {{ $i < $state['outs'] ? 'bg-rose-500 border-rose-600' : 'bg-gray-100' }}"></span>
                            @endfor
                        </div>
                    </div>
                </div>
                @endif

                {{-- Pitcher + Batter — solo visible mientras el juego esta en curso --}}
                @if (! $game->isCompleted())
                <div class="grid grid-cols-2 gap-0 border-b-2 border-gray-100" x-show="!isFinalized">
                    {{-- Pitcher --}}
                    <div class="p-3 flex items-center gap-3 border-r border-gray-100" data-card="pitcher">
                        <div class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0 overflow-hidden" data-athlete-avatar>
                            @if ($pitcher && $pitcher->photoUrl)
                                <img src="{{ $pitcher->photoUrl }}" class="w-full h-full object-cover" data-athlete-photo>
                            @elseif ($pitcher)
                                <span data-athlete-initials>{{ mb_strtoupper(mb_substr($pitcher->first_name ?? '', 0, 1)) }}{{ mb_strtoupper(mb_substr($pitcher->last_name ?? '', 0, 1)) }}</span>
                            @else
                                <span>?</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Pitcheando') }}</div>
                            @if ($pitcher)
                                <div class="text-sm font-bold text-gray-900 truncate">
                                    <span class="text-indigo-600">#{{ $pitcher->number ?? '?' }}</span>
                                    {{ $pitcher->full_name }}
                                </div>
                                <div class="text-[10px] text-gray-600 mt-0.5 truncate" data-pitcher-stats>
                                    {{ $pitcherStats['pitches'] }} lanz. ({{ $pitcherStats['strikes'] }}S / {{ $pitcherStats['balls'] }}B) · K: {{ $pitcherStats['strikeouts'] }} · H: {{ $pitcherStats['hits'] }}
                                </div>
                            @else
                                <div class="text-sm text-gray-400 italic">{{ __('Sin lanzador') }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Batter --}}
                    <div class="p-3 flex items-center gap-3" data-card="batter">
                        <div class="w-9 h-9 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0 overflow-hidden" data-athlete-avatar>
                            @if ($batter && $batter->photoUrl)
                                <img src="{{ $batter->photoUrl }}" class="w-full h-full object-cover" data-athlete-photo>
                            @elseif ($batter)
                                <span data-athlete-initials>{{ mb_strtoupper(mb_substr($batter->first_name ?? '', 0, 1)) }}{{ mb_strtoupper(mb_substr($batter->last_name ?? '', 0, 1)) }}</span>
                            @else
                                <span>?</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Al bate') }}</div>
                            @if ($batter)
                                <div class="text-sm font-bold text-gray-900 truncate">
                                    <span class="text-amber-600">#{{ $batter->number ?? '?' }}</span>
                                    {{ $batter->full_name }}
                                </div>
                                <div class="text-[10px] text-gray-600 mt-0.5 truncate" data-batter-stats>
                                    AB: {{ $batterStats['at_bats'] }} · H: {{ $batterStats['hits'] }} · AVG: {{ number_format($batterStats['avg'], 3, '.', '') }} · BB: {{ $batterStats['walks'] }} · K: {{ $batterStats['strikeouts'] }}
                                </div>
                            @else
                                <div class="text-sm text-gray-400 italic">{{ __('Sin bateador') }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- On-deck (Prevenido) — solo visible mientras el juego esta en curso --}}
                <div class="px-3 py-2 bg-gray-50 border-b border-gray-100 flex items-center gap-2" data-card="ondeck" x-show="!isFinalized">
                    <div class="w-7 h-7 rounded-full bg-slate-500 text-white flex items-center justify-center font-bold text-[10px] flex-shrink-0 overflow-hidden" data-athlete-avatar>
                        @if ($onDeck && $onDeck->photoUrl)
                            <img src="{{ $onDeck->photoUrl }}" class="w-full h-full object-cover" data-athlete-photo>
                        @elseif ($onDeck)
                            <span data-athlete-initials>{{ mb_strtoupper(mb_substr($onDeck->first_name ?? '', 0, 1)) }}{{ mb_strtoupper(mb_substr($onDeck->last_name ?? '', 0, 1)) }}</span>
                        @else
                            <span>?</span>
                        @endif
                    </div>
                    <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Prevenido') }}</div>
                    <div class="text-sm font-bold text-gray-700" data-on-deck-name>
                        @if ($onDeck)
                            <span class="text-gray-500">#{{ $onDeck->number ?? '?' }}</span>
                            {{ $onDeck->full_name }}
                        @else
                            <span class="text-gray-400 italic font-normal">{{ __('Sin prevenido') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Diamond: corredores en base — solo visible mientras el juego esta en curso --}}
                {{-- Contenedor: cuadrado verde con bases, home y pitcher mound posicionados dentro. --}}
                <div class="bg-emerald-700 px-3 py-4 relative" style="background-image: radial-gradient(ellipse at center, #15803d 0%, #14532d 100%);" x-show="!isFinalized">
                    <div class="mx-auto relative" style="width: 280px; height: 280px;" data-diamond>
                        {{-- 2B (arriba) --}}
                        <div class="absolute top-2 left-1/2 -translate-x-1/2" data-base="second">
                            <div class="flex flex-col items-center gap-1">
                                <button type="button"
                                        @click="openRunnerModal('second')"
                                        :disabled="!lastBases?.second"
                                        :class="lastBases?.second ? 'cursor-pointer hover:scale-110 transition-transform' : 'cursor-default'"
                                        class="w-11 h-11 {{ ! empty($runners['second']) ? 'bg-amber-300 border-2 border-amber-500 shadow-md' : 'bg-emerald-50/90 border-2 border-white' }} rounded flex items-center justify-center font-bold text-xs {{ ! empty($runners['second']) ? 'text-amber-900' : 'text-emerald-700/40' }}">
                                    @if (! empty($runners['second']))
                                        <div class="text-center leading-tight">
                                            <div class="text-[9px] font-bold">2B</div>
                                            <div class="text-[11px] font-black">{{ $runners['second']['number'] ?? '' }}</div>
                                        </div>
                                    @else
                                        2B
                                    @endif
                                </button>
                                @if (! empty($runners['second']))
                                    <div class="bg-white/95 rounded px-1.5 py-0.5 text-[10px] leading-tight text-center shadow-md" data-runner-label="second">
                                        <div class="font-bold text-gray-900 truncate max-w-[80px]" title="{{ $runners['second']['name'] ?? '' }}">{{ $runners['second']['name'] ?? '' }}</div>
                                    </div>
                                    <button type="button" @click="openRunnerModal('second')"
                                            class="bg-amber-600 hover:bg-amber-700 text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded shadow">
                                        Opciones
                                    </button>
                                @endif
                            </div>
                        </div>
                        {{-- 3B (izquierda) --}}
                        <div class="absolute top-1/2 left-2 -translate-y-1/2" data-base="third">
                            <div class="flex flex-col items-center gap-1">
                                <button type="button"
                                        @click="openRunnerModal('third')"
                                        :disabled="!lastBases?.third"
                                        :class="lastBases?.third ? 'cursor-pointer hover:scale-110 transition-transform' : 'cursor-default'"
                                        class="w-11 h-11 {{ ! empty($runners['third']) ? 'bg-amber-300 border-2 border-amber-500 shadow-md' : 'bg-emerald-50/90 border-2 border-white' }} rounded flex items-center justify-center font-bold text-xs {{ ! empty($runners['third']) ? 'text-amber-900' : 'text-emerald-700/40' }}">
                                    @if (! empty($runners['third']))
                                        <div class="text-center leading-tight">
                                            <div class="text-[9px] font-bold">3B</div>
                                            <div class="text-[11px] font-black">{{ $runners['third']['number'] ?? '' }}</div>
                                        </div>
                                    @else
                                        3B
                                    @endif
                                </button>
                                @if (! empty($runners['third']))
                                    <div class="bg-white/95 rounded px-1.5 py-0.5 text-[10px] leading-tight text-center shadow-md" data-runner-label="third">
                                        <div class="font-bold text-gray-900 truncate max-w-[80px]" title="{{ $runners['third']['name'] ?? '' }}">{{ $runners['third']['name'] ?? '' }}</div>
                                    </div>
                                    <button type="button" @click="openRunnerModal('third')"
                                            class="bg-amber-600 hover:bg-amber-700 text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded shadow">
                                        Opciones
                                    </button>
                                @endif
                            </div>
                        </div>
                        {{-- 1B (derecha) --}}
                        <div class="absolute top-1/2 right-2 -translate-y-1/2" data-base="first">
                            <div class="flex flex-col items-center gap-1">
                                <button type="button"
                                        @click="openRunnerModal('first')"
                                        :disabled="!lastBases?.first"
                                        :class="lastBases?.first ? 'cursor-pointer hover:scale-110 transition-transform' : 'cursor-default'"
                                        class="w-11 h-11 {{ ! empty($runners['first']) ? 'bg-amber-300 border-2 border-amber-500 shadow-md' : 'bg-emerald-50/90 border-2 border-white' }} rounded flex items-center justify-center font-bold text-xs {{ ! empty($runners['first']) ? 'text-amber-900' : 'text-emerald-700/40' }}">
                                    @if (! empty($runners['first']))
                                        <div class="text-center leading-tight">
                                            <div class="text-[9px] font-bold">1B</div>
                                            <div class="text-[11px] font-black">{{ $runners['first']['number'] ?? '' }}</div>
                                        </div>
                                    @else
                                        1B
                                    @endif
                                </button>
                                @if (! empty($runners['first']))
                                    <div class="bg-white/95 rounded px-1.5 py-0.5 text-[10px] leading-tight text-center shadow-md" data-runner-label="first">
                                        <div class="font-bold text-gray-900 truncate max-w-[80px]" title="{{ $runners['first']['name'] ?? '' }}">{{ $runners['first']['name'] ?? '' }}</div>
                                    </div>
                                    <button type="button" @click="openRunnerModal('first')"
                                            class="bg-amber-600 hover:bg-amber-700 text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded shadow">
                                        Opciones
                                    </button>
                                @endif
                            </div>
                        </div>
                        {{-- HOME (abajo, pentagon shape simulado con clip) --}}
                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2">
                            <div class="relative w-14 h-14 flex items-center justify-center">
                                <div class="absolute inset-0 bg-white border-2 border-gray-300" style="clip-path: polygon(50% 0%, 100% 38%, 82% 100%, 18% 100%, 0% 38%);"></div>
                                <span class="relative text-[9px] font-black text-gray-700">HOME</span>
                            </div>
                        </div>
                        {{-- Pitcher mound (centro) --}}
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            <div class="w-14 h-14 bg-amber-200/90 rounded-full flex items-center justify-center text-sm font-black text-amber-900 border-2 border-amber-300 shadow-inner">
                                P
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============ TABS (PITCHEo / BATEo / EXTRAS) — DISI-33 reactivo ============ --}}
                {{-- DISI-33: cuando el juego se finaliza durante la sesion, applyState() --}}
                {{-- activa isFinalized=true y mueve tab='extra'. Las x-show de abajo --}}
                {{-- hacen desaparecer PITCHEo/BATEo, el diamante, Bolas/Strikes/Outs, --}}
                {{-- y los 7 botones de Extras, dejando solo Stats + Box Score en 1x2. --}}
                {{-- En el render inicial, el server pasa isFinalized via x-data segun --}}
                {{-- el state.is_game_over del momento del GET, asi que la primera --}}
                {{-- pintada ya refleja la condicion correcta sin parpadeos. --}}
                <div>
                    {{-- Tab buttons: 3 botones en juego en curso, 1 solo (Extras) cuando finalizado --}}
                    <div class="grid grid-cols-3 border-t-2 border-gray-100" x-show="!isFinalized">
                        <button type="button" @click="tab = 'pitch'"
                                :class="tab === 'pitch' ? 'border-b-2 border-amber-500 text-amber-600 font-bold' : 'text-gray-500'"
                                class="py-3 text-center text-sm uppercase tracking-wider">
                            {{ __('Pitcheo') }}
                        </button>
                        <button type="button" @click="tab = 'hit'"
                                :class="tab === 'hit' ? 'border-b-2 border-amber-500 text-amber-600 font-bold' : 'text-gray-500'"
                                class="py-3 text-center text-sm uppercase tracking-wider">
                            {{ __('Bateo') }}
                        </button>
                        <button type="button" @click="tab = 'extra'"
                                :class="tab === 'extra' ? 'border-b-2 border-amber-500 text-amber-600 font-bold' : 'text-gray-500'"
                                class="py-3 text-center text-sm uppercase tracking-wider">
                            {{ __('Extras') }}
                        </button>
                    </div>
                    <div class="grid grid-cols-1 border-t-2 border-gray-100" x-show="isFinalized">
                        <button type="button" @click="tab = 'extra'"
                                :class="tab === 'extra' ? 'border-b-2 border-amber-500 text-amber-600 font-bold' : 'text-gray-500'"
                                class="py-3 text-center text-sm uppercase tracking-wider">
                            {{ __('Extras') }}
                        </button>
                    </div>

                    {{-- Tab content: PITCHEo (Fase 2 — funcional) — solo en curso --}}
                    <div x-show="tab === 'pitch' && !isFinalized" x-cloak class="grid grid-cols-4 gap-2 p-4">
                        <button type="button" @click="sendBall()"
                                :disabled="isPitching"
                                class="py-6 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-2xl font-black rounded-2xl transition">
                            {{ __('Ball') }}
                        </button>
                        <button type="button" @click="openStrikeModal()"
                                :disabled="isPitching"
                                class="py-6 bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-2xl font-black rounded-2xl transition">
                            {{ __('Strike') }}
                        </button>
                        <button type="button" @click="sendFoul()"
                                :disabled="isPitching"
                                class="py-6 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-2xl font-black rounded-2xl transition">
                            {{ __('Foul') }}
                        </button>
                        <button type="button" @click="openOutStep1()"
                                :disabled="isPitching"
                                class="py-6 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white text-2xl font-black rounded-2xl transition">
                            {{ __('Out') }}
                        </button>
                    </div>

                    {{-- Tab content: BATEo (Fase 3 — hits) — solo en curso --}}
                    <div x-show="tab === 'hit' && !isFinalized" x-cloak class="grid grid-cols-4 gap-2 p-4">
                        <button type="button" @click="openHitModal('single')"
                                :disabled="isPitching"
                                class="py-6 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-base font-black rounded-2xl transition">
                            {{ __('Sencillo') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">1B</div>
                        </button>
                        <button type="button" @click="openHitModal('double')"
                                :disabled="isPitching"
                                class="py-6 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-base font-black rounded-2xl transition">
                            {{ __('Doble') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">2B</div>
                        </button>
                        <button type="button" @click="openHitModal('triple')"
                                :disabled="isPitching"
                                class="py-6 bg-violet-500 hover:bg-violet-600 disabled:opacity-50 text-white text-base font-black rounded-2xl transition">
                            {{ __('Triple') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">3B</div>
                        </button>
                        <button type="button" @click="openHitModal('hr')"
                                :disabled="isPitching"
                                class="py-6 bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-base font-black rounded-2xl transition">
                            {{ __('HR') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">Home Run</div>
                        </button>
                        <button type="button" @click="openHitModal('inside_park')"
                                :disabled="isPitching"
                                class="col-span-2 py-5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-base font-black rounded-2xl transition">
                            {{ __('HR de pierna') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">Inside-the-park</div>
                        </button>
                        {{-- DISI-28: Toque de bolas movido de EXTRAS a BATEO --}}
                        <button type="button" @click="openBuntModal()"
                                :disabled="isPitching"
                                class="col-span-2 py-5 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white text-base font-black rounded-2xl transition">
                            {{ __('Toque de bolas') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">{{ __('Sacrifice o bunt single') }}</div>
                        </button>
                    </div>

                    {{-- Tab content: EXTRAS (Fase 4) — siempre visible cuando tab=extra --}}
                    {{-- La grilla interior cambia segun isFinalized: --}}
                    {{--   - Juego finalizado: 1x2 con SOLO Stats del juego + Box Score --}}
                    {{--   - Juego en curso: 2x4 con los 7 botones de EXTRAS --}}
                    <div x-show="tab === 'extra'" x-cloak>
                        <div class="grid grid-cols-2 gap-3 p-4" x-show="isFinalized">
                            <button type="button" @click="openStatsModal()"
                                    class="py-4 bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-bold rounded-2xl transition text-center">
                                {{ __('Stats del juego') }}
                                <div class="text-[10px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Box score completo: pitcheo y bateo') }}</div>
                            </button>
                            <a href="{{ route('games.box-score', $game) }}"
                               class="py-4 bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold rounded-2xl transition text-center block">
                                📋 {{ __('Box Score') }}
                                <div class="text-[10px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Carreras, hits, errores por inning') }}</div>
                            </a>
                        </div>
                        <div class="grid grid-cols-4 gap-2 p-4" x-show="!isFinalized">
                            <button type="button" @click="openSubstituteModal()"
                                    :disabled="isPitching"
                                    class="py-3 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-sm font-bold rounded-lg transition">
                                {{ __('Sustituir') }}
                                <div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Pitcher, bateador o corredor') }}</div>
                            </button>
                            <button type="button" @click="sendBalk()"
                                    :disabled="isPitching"
                                    class="py-3 bg-purple-500 hover:bg-purple-600 disabled:opacity-50 text-white text-sm font-bold rounded-lg transition">
                                {{ __('Balk') }}
                                <div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Corredores avanzan 1 base') }}</div>
                            </button>
                            <button type="button" @click="openLineupModal()"
                                    :disabled="isPitching"
                                    class="py-3 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-sm font-bold rounded-lg transition">
                                {{ __('Reordenar lineup') }}
                                <div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Drag & drop para cambiar el orden de bateo') }}</div>
                            </button>
                            <button type="button" @click="openStatsModal()"
                                    :disabled="isPitching"
                                    class="py-3 bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 text-white text-sm font-bold rounded-lg transition">
                                {{ __('Stats del juego') }}
                                <div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Box score completo: pitcheo y bateo') }}</div>
                            </button>
                            <a href="{{ route('games.box-score', $game) }}"
                               class="py-3 bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold rounded-lg transition text-center block">
                                📋 {{ __('Box Score') }}
                                <div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Carreras, hits, errores por inning') }}</div>
                            </a>
                            <button type="button" @click="openEndInningModal()"
                                    :disabled="isPitching"
                                    class="py-3 bg-rose-50 hover:bg-rose-100 disabled:opacity-50 text-rose-700 text-sm font-bold rounded-lg border border-rose-200 transition">
                                {{ __('Finalizar inning') }}
                            </button>
                            <button type="button" @click="openEndGameModal()"
                                    :disabled="isPitching"
                                    class="py-3 bg-rose-100 hover:bg-rose-200 disabled:opacity-50 text-rose-800 text-sm font-bold rounded-lg border border-rose-300 transition">
                                {{ __('Finalizar juego') }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Indicador de conexion en vivo --}}
            <div class="mt-3 text-center text-xs text-gray-400" data-poll-indicator>
                <span x-text="pollStatus"></span>
            </div>

            {{-- ============ MODALES PITCHEo (Fase 2) ============ --}}

            {{-- Modal: tipo de strike (Mirando / Swing / Foul Tip) --}}
            <div x-show="modal === 'strike'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-rose-500 text-white px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Tipo de ponche') }}</h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <button type="button" @click="sendStrike('looking')"
                                class="w-full py-4 bg-rose-50 hover:bg-rose-100 text-rose-700 text-lg font-bold rounded-xl border-2 border-rose-200 transition">
                            {{ __('Mirando') }}
                        </button>
                        <button type="button" @click="sendStrike('swinging')"
                                class="w-full py-4 bg-rose-100 hover:bg-rose-200 text-rose-800 text-lg font-bold rounded-xl border-2 border-rose-300 transition">
                            {{ __('Swing') }}
                        </button>
                        <button type="button" @click="sendStrike('foul_tip')"
                                class="w-full py-4 bg-rose-200 hover:bg-rose-300 text-rose-900 text-lg font-bold rounded-xl border-2 border-rose-400 transition">
                            {{ __('Foul Tip') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal Out paso 1: tipo de out --}}
            <div x-show="modal === 'out-step1'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-slate-700 text-white px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Tipo de out') }}</h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <button type="button" @click="openOutStep2('fly')"
                                class="w-full py-4 bg-sky-50 hover:bg-sky-100 text-sky-700 text-lg font-bold rounded-xl border-2 border-sky-200 transition">
                            {{ __('Fly (elevado)') }}
                        </button>
                        <button type="button" @click="openOutStep2('line')"
                                class="w-full py-4 bg-sky-100 hover:bg-sky-200 text-sky-800 text-lg font-bold rounded-xl border-2 border-sky-300 transition">
                            {{ __('Línea') }}
                        </button>
                        <button type="button" @click="openOutStep2('ground')"
                                class="w-full py-4 bg-amber-50 hover:bg-amber-100 text-amber-700 text-lg font-bold rounded-xl border-2 border-amber-200 transition">
                            {{ __('Roletazo') }}
                        </button>
                        <button type="button" @click="sendOut('reglamento')"
                                class="w-full py-4 bg-slate-50 hover:bg-slate-100 text-slate-700 text-lg font-bold rounded-xl border-2 border-slate-200 transition">
                            {{ __('De reglamento') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal: confirmacion de hit (BATEo Fase 3) --}}
            <div x-show="modal === 'hit'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-emerald-500 text-white px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">
                            <span x-text="hitConfig.label"></span>
                        </h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <p class="text-sm text-gray-600">
                            <span x-text="hitConfig.description"></span>
                        </p>
                        <div class="bg-gray-50 rounded-lg p-3 text-sm">
                            <div class="text-xs text-gray-500 uppercase font-semibold mb-1">{{ __('Resultado esperado') }}</div>
                            <div class="text-gray-800" x-html="hitConfig.preview"></div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="closeModal()"
                                    class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl">
                                {{ __('Cancelar') }}
                            </button>
                            <button type="button" @click="confirmHit()"
                                    class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl">
                                {{ __('Registrar hit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Out paso 2: jugada defensiva (fildeadores en orden) --}}
            <div x-show="modal === 'out-step2'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-slate-800 text-white px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Jugada defensiva') }}</h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-gray-600 mb-3">
                            {{ __('Toca los fildeadores en el orden que participaron.') }}
                        </p>

                        {{-- Diamante con los 9 fildeadores + bateador (DISI-30: layout rediseñado) --}}
                        <div class="relative bg-emerald-700 rounded-xl mx-auto" style="width: 320px; height: 320px;">
                            {{-- Infield dirt (circulo central) --}}
                            <div class="absolute rounded-full bg-amber-100/30" style="top: 90px; left: 40px; right: 40px; bottom: 90px;"></div>

                            {{-- Bases (esquinas del diamante) --}}
                            <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 70px; left: 50%; transform: translateX(-50%) rotate(45deg);" title="2da base"></div>
                            <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 50%; right: 8px; transform: translateY(-50%) rotate(45deg);" title="1ra base"></div>
                            <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="bottom: 8px; left: 50%; transform: translateX(-50%) rotate(45deg);" title="Home"></div>
                            <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 50%; left: 8px; transform: translateY(-50%) rotate(45deg);" title="3ra base"></div>

                            {{-- Pitcher mound (centro del diamante) --}}
                            <div class="absolute flex items-center justify-center text-xs font-black text-amber-900 bg-amber-200/90 rounded-full" style="top: 138px; left: 50%; transform: translate(-50%, -50%); width: 44px; height: 44px;">P</div>

                            {{-- Outfield (fila superior, y=10-44) --}}
                            <button type="button" @click="addFielder('LF')" style="top: 14px; left: 16px;"
                                    :class="isFielderSelected('LF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                    class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">LF</button>
                            <button type="button" @click="addFielder('CF')" style="top: 10px; left: 50%; transform: translateX(-50%);"
                                    :class="isFielderSelected('CF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                    class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">CF</button>
                            <button type="button" @click="addFielder('RF')" style="top: 14px; right: 16px;"
                                    :class="isFielderSelected('RF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                    class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">RF</button>

                            {{-- Infield medio (SS, 2B) - entre outfield y corners --}}
                            <button type="button" @click="addFielder('SS')" style="top: 102px; left: 50px;"
                                    :class="isFielderSelected('SS') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                    class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">SS</button>
                            <button type="button" @click="addFielder('2B')" style="top: 102px; right: 50px;"
                                    :class="isFielderSelected('2B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                    class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">2B</button>

                            {{-- Infield corners (3B, 1B) - a la altura del pitcher --}}
                            <button type="button" @click="addFielder('3B')" style="top: 178px; left: 28px;"
                                    :class="isFielderSelected('3B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                    class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">3B</button>
                            <button type="button" @click="addFielder('1B')" style="top: 178px; right: 28px;"
                                    :class="isFielderSelected('1B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                    class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">1B</button>

                            {{-- Catcher (debajo del home plate, abajo del centro) --}}
                            <button type="button" @click="addFielder('C')" style="bottom: 56px; left: 50%; transform: translateX(-50%);"
                                    :class="isFielderSelected('C') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                    class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">C</button>
                        </div>

                        {{-- Secuencia seleccionada --}}
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg min-h-[60px]">
                            <div class="text-xs text-gray-500 uppercase font-semibold mb-1">{{ __('Secuencia') }}</div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <template x-for="(f, i) in defensiveSequence" :key="i">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-200 text-amber-900 rounded-full text-sm font-bold">
                                        <span x-text="f"></span>
                                        <button type="button" @click="removeFielder(i)" class="text-amber-700 hover:text-red-600 font-black">&times;</button>
                                    </span>
                                </template>
                                <span x-show="defensiveSequence.length === 0" class="text-sm text-gray-400 italic">{{ __('Selecciona los fildeadores') }}</span>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <button type="button" @click="closeModal()"
                                    class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl">
                                {{ __('Cancelar') }}
                            </button>
                            <button type="button" @click="confirmOut()" :disabled="defensiveSequence.length === 0"
                                    class="flex-1 py-3 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white font-bold rounded-xl">
                                {{ __('Registrar out') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal: tipo de bunt (Fase 4) --}}
            <div x-show="modal === 'bunt'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-amber-500 text-white px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Toque de bolas') }}</h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <p class="text-sm text-gray-600">{{ __('Elige el resultado del toque:') }}</p>
                        <button type="button" @click="sendBunt('sacrifice')"
                                class="w-full py-4 bg-amber-50 hover:bg-amber-100 text-amber-700 text-lg font-bold rounded-xl border-2 border-amber-200 transition">
                            {{ __('Toque de sacrificio') }}
                            <div class="text-xs font-normal opacity-80 mt-1">{{ __('Bateador out, corredores avanzan') }}</div>
                        </button>
                        <button type="button" @click="sendBunt('bunt_single')"
                                class="w-full py-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-lg font-bold rounded-xl border-2 border-emerald-200 transition">
                            {{ __('Bunt single') }}
                            <div class="text-xs font-normal opacity-80 mt-1">{{ __('Bateador a 1B, corredores avanzan') }}</div>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal: confirmar finalizar inning (Fase 4) --}}
            <div x-show="modal === 'end-inning'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-rose-100 text-rose-800 px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Finalizar inning') }}</h3>
                        <button type="button" @click="closeModal()" class="text-rose-700/80 hover:text-rose-900 text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <p class="text-sm text-gray-700">
                            {{ __('Vas a cerrar el inning actual antes de los 3 outs. Esta accion no se puede deshacer.') }}
                        </p>
                        <div class="bg-gray-50 rounded-lg p-3 text-sm">
                            <div class="text-xs text-gray-500 uppercase font-semibold mb-1">{{ __('Inning actual') }}</div>
                            <div class="text-gray-800">
                                <span data-inning-number>{{ $game->current_inning }}</span> -
                                <span data-inning-half>{{ $game->inning_half === 'top' ? __('Top (visitante)') : __('Bottom (local)') }}</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="closeModal()"
                                    class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl">
                                {{ __('Cancelar') }}
                            </button>
                            <button type="button" @click="confirmEndInning()"
                                    class="flex-1 py-3 bg-rose-500 hover:bg-rose-600 text-white font-bold rounded-xl">
                                {{ __('Finalizar inning') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal: resumen tras finalizar inning (Fase 4/5) --}}
            <div x-show="modal === 'inning-summary'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-emerald-500 text-white px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Inning finalizado') }}</h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-3" x-show="inningSummary">
                        <div class="bg-emerald-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-emerald-700 uppercase font-semibold">Inning <span x-text="inningSummary?.inning"></span> - <span x-text="inningSummary?.half === 'top' ? 'Top' : 'Bottom'"></span></div>
                            <div class="text-4xl font-black text-emerald-800 my-1">
                                <span x-text="inningSummary?.runs ?? 0"></span> <span class="text-base font-normal">carrera<span x-show="(inningSummary?.runs ?? 0) !== 1">s</span></span>
                            </div>
                            <div class="text-xs text-emerald-700">
                                <span x-text="inningSummary?.hits ?? 0"></span> hits ·
                                <span x-text="inningSummary?.walks ?? 0"></span> BB ·
                                <span x-text="inningSummary?.strikeouts ?? 0"></span> K ·
                                <span x-text="inningSummary?.errors ?? 0"></span> E
                            </div>
                        </div>
                        <button type="button" @click="closeModal()"
                                class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl">
                            {{ __('Continuar') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal: confirmar finalizar juego (Fase 4) --}}
            <div x-show="modal === 'end-game'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-rose-700 text-white px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Finalizar juego') }}</h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <p class="text-sm text-gray-700">
                            {{ __('Vas a finalizar el juego ahora. El juego se marcara como terminado y no se podran registrar mas jugadas.') }}
                        </p>
                        <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('Score') }}:</span>
                                <span class="font-black">
                                    <span x-text="awayName"></span> <span data-score="away">{{ $game->away_score }}</span> -
                                    <span data-score="home">{{ $game->home_score }}</span> <span x-text="homeName"></span>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('Inning') }}:</span>
                                <span class="font-bold"><span data-inning-number>{{ $game->current_inning }}</span> - <span data-inning-half>{{ $game->inning_half === 'top' ? 'Top' : 'Bottom' }}</span></span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="closeModal()"
                                    class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl">
                                {{ __('Cancelar') }}
                            </button>
                            <button type="button" @click="confirmEndGame()"
                                    class="flex-1 py-3 bg-rose-700 hover:bg-rose-800 text-white font-bold rounded-xl">
                                {{ __('Finalizar juego') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal: stats del juego (MEJ-3) --}}
            <div x-show="modal === 'stats'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
                    <div class="bg-indigo-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Stats del juego') }}</h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>

                    {{-- Line score --}}
                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex-shrink-0" x-show="statsData && !statsLoading">
                        <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold mb-2">{{ __('Line score') }}</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-gray-500">
                                        <th class="text-left font-semibold pb-1 pr-2">{{ __('Equipo') }}</th>
                                        <template x-for="i in statsData?.total_innings || 7" :key="i">
                                            <th class="text-center font-semibold pb-1 px-1" x-text="i"></th>
                                        </template>
                                        <th class="text-center font-bold text-gray-800 pb-1 pl-2 border-l border-gray-300">{{ __('C') }}</th>
                                        <th class="text-center font-bold text-gray-800 pb-1 pl-1">{{ __('H') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-t border-gray-200">
                                        <td class="py-1 pr-2 font-bold" x-text="statsData?.away_team?.short || awayShort"></td>
                                        <template x-for="(r, idx) in lineScoreAway()" :key="'a' + idx">
                                            <td class="text-center py-1 px-1" x-text="r"></td>
                                        </template>
                                        <td class="text-center font-black text-base pl-2 border-l border-gray-300" x-text="lineScoreTotal('away')"></td>
                                        <td class="text-center font-bold pl-1" x-text="filteredBatting().filter(b => b.team_id === awayTeamId).reduce((s, b) => s + b.hits, 0)"></td>
                                    </tr>
                                    <tr class="border-t border-gray-200">
                                        <td class="py-1 pr-2 font-bold" x-text="statsData?.home_team?.short || homeShort"></td>
                                        <template x-for="(r, idx) in lineScoreHome()" :key="'h' + idx">
                                            <td class="text-center py-1 px-1" x-text="r"></td>
                                        </template>
                                        <td class="text-center font-black text-base pl-2 border-l border-gray-300" x-text="lineScoreTotal('home')"></td>
                                        <td class="text-center font-bold pl-1" x-text="filteredBatting().filter(b => b.team_id === homeTeamId).reduce((s, b) => s + b.hits, 0)"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tabs Pitcheo / Bateo + filtro de equipo --}}
                    <div class="border-b border-gray-200 flex items-center px-5 pt-3 flex-shrink-0">
                        <button type="button" @click="statsTab = 'batting'"
                                :class="statsTab === 'batting' ? 'border-b-2 border-indigo-600 text-indigo-700 font-bold' : 'text-gray-500'"
                                class="px-3 py-2 text-sm">{{ __('Bateo') }}</button>
                        <button type="button" @click="statsTab = 'pitching'"
                                :class="statsTab === 'pitching' ? 'border-b-2 border-indigo-600 text-indigo-700 font-bold' : 'text-gray-500'"
                                class="px-3 py-2 text-sm">{{ __('Pitcheo') }}</button>
                        <div class="ml-auto flex gap-1 pb-1">
                            <button type="button" @click="statsFilter = 'all'"
                                    :class="statsFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'"
                                    class="px-2 py-1 text-[10px] font-bold rounded">{{ __('Todos') }}</button>
                            <button type="button" @click="statsFilter = 'home'"
                                    :class="statsFilter === 'home' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'"
                                    class="px-2 py-1 text-[10px] font-bold rounded" x-text="homeShort"></button>
                            <button type="button" @click="statsFilter = 'away'"
                                    :class="statsFilter === 'away' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'"
                                    class="px-2 py-1 text-[10px] font-bold rounded" x-text="awayShort"></button>
                        </div>
                    </div>

                    {{-- Contenido scrollable --}}
                    <div class="flex-1 overflow-y-auto p-5">
                        <div x-show="statsLoading" class="text-center text-sm text-gray-500 py-8">{{ __('Cargando...') }}</div>

                        {{-- Bateo --}}
                        <div x-show="!statsLoading && statsTab === 'batting'" class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-gray-500 text-[10px] uppercase">
                                        <th class="text-left font-semibold pb-2 pr-2">#</th>
                                        <th class="text-left font-semibold pb-2 pr-2">{{ __('Bateador') }}</th>
                                        <th class="text-center font-semibold pb-2 px-1">AB</th>
                                        <th class="text-center font-semibold pb-2 px-1">H</th>
                                        <th class="text-center font-semibold pb-2 px-1">2B</th>
                                        <th class="text-center font-semibold pb-2 px-1">3B</th>
                                        <th class="text-center font-semibold pb-2 px-1">HR</th>
                                        <th class="text-center font-semibold pb-2 px-1">BB</th>
                                        <th class="text-center font-semibold pb-2 px-1">K</th>
                                        <th class="text-center font-semibold pb-2 px-1">RBI</th>
                                        <th class="text-center font-semibold pb-2 px-1">R</th>
                                        <th class="text-center font-semibold pb-2 px-1">AVG</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="b in filteredBatting()" :key="b.batter_id">
                                        <tr class="border-t border-gray-100">
                                            <td class="py-1.5 pr-2 font-bold text-indigo-600" x-text="b.number ?? '-'"></td>
                                            <td class="py-1.5 pr-2 font-medium" x-text="b.name"></td>
                                            <td class="text-center py-1.5 px-1" x-text="b.at_bats"></td>
                                            <td class="text-center py-1.5 px-1 font-bold" x-text="b.hits"></td>
                                            <td class="text-center py-1.5 px-1" x-text="b.doubles"></td>
                                            <td class="text-center py-1.5 px-1" x-text="b.triples"></td>
                                            <td class="text-center py-1.5 px-1" x-text="b.hr"></td>
                                            <td class="text-center py-1.5 px-1" x-text="b.walks"></td>
                                            <td class="text-center py-1.5 px-1" x-text="b.strikeouts"></td>
                                            <td class="text-center py-1.5 px-1" x-text="b.rbi"></td>
                                            <td class="text-center py-1.5 px-1" x-text="b.runs"></td>
                                            <td class="text-center py-1.5 px-1 font-bold" x-text="formatAvg(b.avg)"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredBatting().length === 0">
                                        <td colspan="12" class="text-center text-gray-400 py-4">{{ __('Sin bateadores aún') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Pitcheo --}}
                        <div x-show="!statsLoading && statsTab === 'pitching'" class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-gray-500 text-[10px] uppercase">
                                        <th class="text-left font-semibold pb-2 pr-2">#</th>
                                        <th class="text-left font-semibold pb-2 pr-2">{{ __('Pitcher') }}</th>
                                        <th class="text-center font-semibold pb-2 px-1">IP</th>
                                        <th class="text-center font-semibold pb-2 px-1">P</th>
                                        <th class="text-center font-semibold pb-2 px-1">S</th>
                                        <th class="text-center font-semibold pb-2 px-1">B</th>
                                        <th class="text-center font-semibold pb-2 px-1">K</th>
                                        <th class="text-center font-semibold pb-2 px-1">BB</th>
                                        <th class="text-center font-semibold pb-2 px-1">H</th>
                                        <th class="text-center font-semibold pb-2 px-1">R</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in filteredPitching()" :key="p.pitcher_id">
                                        <tr class="border-t border-gray-100">
                                            <td class="py-1.5 pr-2 font-bold text-indigo-600" x-text="p.number ?? '-'"></td>
                                            <td class="py-1.5 pr-2 font-medium" x-text="p.name"></td>
                                            <td class="text-center py-1.5 px-1 font-bold" x-text="p.ip"></td>
                                            <td class="text-center py-1.5 px-1" x-text="p.pitches"></td>
                                            <td class="text-center py-1.5 px-1" x-text="p.strikes"></td>
                                            <td class="text-center py-1.5 px-1" x-text="p.balls"></td>
                                            <td class="text-center py-1.5 px-1" x-text="p.strikeouts"></td>
                                            <td class="text-center py-1.5 px-1" x-text="p.walks_allowed"></td>
                                            <td class="text-center py-1.5 px-1" x-text="p.hits_allowed"></td>
                                            <td class="text-center py-1.5 px-1" x-text="p.runs_allowed"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredPitching().length === 0">
                                        <td colspan="10" class="text-center text-gray-400 py-4">{{ __('Sin pitchers aún') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 p-3 flex gap-2 flex-shrink-0">
                        <button type="button" @click="loadStats()"
                                class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-bold rounded-lg">
                            {{ __('Recargar') }}
                        </button>
                        <button type="button" @click="closeModal()"
                                class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg">
                            {{ __('Cerrar') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal: gestion de lineup (MEJ-4 + DISI-31) --}}
            <div x-show="modal === 'lineup'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
                    <div class="bg-emerald-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-90 font-semibold">{{ __('DISI-31') }}</div>
                            <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Gestion de lineup') }}</h3>
                        </div>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>

                    {{-- Sub-tabs Visitante / Local --}}
                    <div class="border-b border-gray-200 flex items-center px-3 flex-shrink-0">
                        <button type="button" @click="lineupTeam = 'away'; lineupDirty = false"
                                :class="lineupTeam === 'away' ? 'border-b-2 border-emerald-600 text-emerald-700 font-bold' : 'text-gray-500'"
                                class="px-3 py-2 text-sm" x-text="awayShort"></button>
                        <button type="button" @click="lineupTeam = 'home'; lineupDirty = false"
                                :class="lineupTeam === 'home' ? 'border-b-2 border-emerald-600 text-emerald-700 font-bold' : 'text-gray-500'"
                                class="px-3 py-2 text-sm" x-text="homeShort"></button>
                        <div class="ml-auto text-xs text-gray-500 px-2">
                            <span x-text="currentLineup().length"></span> / 9 titulares
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-3 space-y-4">
                        {{-- ============== SECCION TITULARES (9) ============== --}}
                        <div>
                            <div class="flex items-center justify-between mb-2 px-1">
                                <h4 class="text-xs uppercase tracking-wider text-gray-500 font-bold">{{ __('Titulares (lineup)') }}</h4>
                                <span class="text-[10px] text-gray-400">{{ __('Arrastra para reordenar bateo') }}</span>
                            </div>
                            <div class="space-y-1.5">
                                <template x-for="(a, i) in currentLineup()" :key="a.id">
                                    <div draggable="true"
                                         data-lineup-drop
                                         @dragstart="onDragStart($event, a.id)"
                                         @dragend="onDragEnd($event)"
                                         @dragover="onDragOver($event, a.id)"
                                         @dragleave="onDragLeave($event)"
                                         @drop="onDrop($event, a.id)"
                                         class="flex items-center gap-2 px-2 py-1.5 bg-white border border-gray-200 rounded-lg cursor-grab hover:border-emerald-400">
                                        <span class="w-7 h-7 flex items-center justify-center bg-emerald-600 text-white rounded-full text-xs font-black flex-shrink-0" x-text="a.lineup_order"></span>
                                        <span class="text-sm font-bold text-emerald-700 w-7 flex-shrink-0 text-center" x-text="'#' + (a.number ?? '-')"></span>
                                        <span class="text-sm font-medium text-gray-800 flex-1 truncate" x-text="a.first_name + ' ' + a.last_name"></span>
                                        {{-- Selector de posicion defensiva --}}
                                        <select @change="setLineupPosition(a.id, $event.target.value)"
                                                :value="a.position || ''"
                                                class="w-16 text-xs border border-gray-300 rounded px-1 py-0.5 flex-shrink-0">
                                            <option value="P">P</option>
                                            <option value="C">C</option>
                                            <option value="1B">1B</option>
                                            <option value="2B">2B</option>
                                            <option value="3B">3B</option>
                                            <option value="SS">SS</option>
                                            <option value="LF">LF</option>
                                            <option value="CF">CF</option>
                                            <option value="RF">RF</option>
                                        </select>
                                        {{-- Radio pitcher (solo 1 por equipo) --}}
                                        <label class="flex items-center gap-1 text-xs flex-shrink-0 cursor-pointer" title="Marcar como pitcher">
                                            <input type="radio"
                                                   :name="`pitcher-${lineupTeam}`"
                                                   :checked="a.is_pitcher === true"
                                                   @change="setLineupPitcher(a.id)"
                                                   class="rounded-full text-emerald-600 focus:ring-emerald-500">
                                            <span class="text-[10px] font-bold text-emerald-700">P</span>
                                        </label>
                                        {{-- Boton quitar --}}
                                        <button type="button" @click="removeFromLineup(a.id)"
                                                title="Quitar del lineup"
                                                class="w-7 h-7 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs font-black flex-shrink-0">&times;</button>
                                        <div class="flex flex-col gap-0.5 flex-shrink-0">
                                            <button type="button" @click="moveUp(a.id)" :disabled="i === 0"
                                                    class="w-6 h-4 bg-gray-100 hover:bg-gray-200 disabled:opacity-30 rounded text-[10px] font-bold leading-none">&uarr;</button>
                                            <button type="button" @click="moveDown(a.id)" :disabled="i === currentLineup().length - 1"
                                                    class="w-6 h-4 bg-gray-100 hover:bg-gray-200 disabled:opacity-30 rounded text-[10px] font-bold leading-none">&darr;</button>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="currentLineup().length === 0" class="text-center text-gray-400 italic py-3 text-sm">
                                    {{ __('Este equipo no tiene titulares. Agrega jugadores desde la lista de disponibles.') }}
                                </div>
                            </div>
                        </div>

                        {{-- ============== SECCION DISPONIBLES (roster no en lineup) ============== --}}
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex items-center justify-between mb-2 px-1">
                                <h4 class="text-xs uppercase tracking-wider text-gray-500 font-bold">{{ __('Disponibles (roster)') }}</h4>
                                <span class="text-[10px] text-gray-400" x-text="availableRoster().length + ' jugadores'"></span>
                            </div>
                            <div class="space-y-1">
                                <template x-for="a in availableRoster()" :key="a.id">
                                    <div class="flex items-center gap-2 px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg hover:border-emerald-300">
                                        <span class="text-sm font-bold text-gray-500 w-7 flex-shrink-0 text-center" x-text="'#' + (a.number ?? '-')"></span>
                                        <span class="text-sm font-medium text-gray-700 flex-1 truncate" x-text="a.first_name + ' ' + a.last_name"></span>
                                        <span class="text-[10px] text-gray-400 flex-shrink-0" x-text="a.position || ''"></span>
                                        <button type="button" @click="addToLineup(a.id)" :disabled="currentLineup().length >= 9"
                                                title="Agregar al lineup"
                                                class="px-2 py-1 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold rounded flex-shrink-0">
                                            + {{ __('Agregar') }}
                                        </button>
                                    </div>
                                </template>
                                <div x-show="availableRoster().length === 0" class="text-center text-gray-400 italic py-3 text-sm">
                                    {{ __('Todos los atletas del roster ya estan en el lineup.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 p-3 flex gap-2 flex-shrink-0">
                        <button type="button" @click="closeModal()"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-bold rounded-lg">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="button" @click="saveLineup()"
                                :disabled="!lineupDirty || currentLineup().length !== 9"
                                class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold rounded-lg">
                            <span x-show="currentLineup().length === 9">{{ __('Guardar lineup') }}</span>
                            <span x-show="currentLineup().length !== 9" x-text="'Faltan ' + (9 - currentLineup().length) + ' titulares'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal: sustituciones (Fase 4b) --}}
            <div x-show="modal === 'substitute'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                    <div class="bg-sky-600 text-white px-5 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Sustituir') }}</h3>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        {{-- Sub-tabs: Pitcher / Bateador / PR --}}
                        <div class="flex gap-1 border-b border-gray-200">
                            <button type="button" @click="subKind = 'pitcher'"
                                    :class="subKind === 'pitcher' ? 'border-b-2 border-sky-600 text-sky-700 font-bold' : 'text-gray-500'"
                                    class="px-3 py-2 text-sm">{{ __('Pitcher') }}</button>
                            <button type="button" @click="subKind = 'batter'"
                                    :class="subKind === 'batter' ? 'border-b-2 border-sky-600 text-sky-700 font-bold' : 'text-gray-500'"
                                    class="px-3 py-2 text-sm">{{ __('Bateador') }}</button>
                            <button type="button" @click="subKind = 'pr'"
                                    :class="subKind === 'pr' ? 'border-b-2 border-sky-600 text-sky-700 font-bold' : 'text-gray-500'"
                                    class="px-3 py-2 text-sm">{{ __('Pinch runner') }}</button>
                        </div>

                        {{-- Pitcher change --}}
                        <div x-show="subKind === 'pitcher'" class="space-y-3">
                            <p class="text-sm text-gray-600">{{ __('Reemplaza al pitcher actual por otro del roster.') }}</p>
                            <div>
                                <label class="text-xs text-gray-500 uppercase font-semibold">{{ __('Pitcher actual') }}</label>
                                <div class="text-base font-bold text-gray-800" x-text="currentPitcherLabel()"></div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase font-semibold">{{ __('Nuevo pitcher') }}</label>
                                <select x-model="subInId"
                                        class="w-full mt-1 px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-sky-500 focus:outline-none">
                                    <option value="">{{ __('Selecciona un atleta...') }}</option>
                                    <template x-for="a in rosterForBattingTeam()" :key="a.id">
                                        <option :value="a.id" x-text="`#${a.lineup_order ?? '-'} ${a.first_name} ${a.last_name}`"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        {{-- Batter change --}}
                        <div x-show="subKind === 'batter'" class="space-y-3">
                            <p class="text-sm text-gray-600">{{ __('Reemplaza al bateador actual por otro del roster.') }}</p>
                            <div>
                                <label class="text-xs text-gray-500 uppercase font-semibold">{{ __('Bateador actual') }}</label>
                                <div class="text-base font-bold text-gray-800" x-text="currentBatterLabel()"></div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase font-semibold">{{ __('Nuevo bateador') }}</label>
                                <select x-model="subInId"
                                        class="w-full mt-1 px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-sky-500 focus:outline-none">
                                    <option value="">{{ __('Selecciona un atleta...') }}</option>
                                    <template x-for="a in rosterForBattingTeam()" :key="a.id">
                                        <option :value="a.id" x-text="`#${a.lineup_order ?? '-'} ${a.first_name} ${a.last_name}`"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        {{-- Pinch runner --}}
                        <div x-show="subKind === 'pr'" class="space-y-3">
                            <p class="text-sm text-gray-600">{{ __('Reemplaza un corredor en base por otro atleta del roster.') }}</p>
                            <div>
                                <label class="text-xs text-gray-500 uppercase font-semibold">{{ __('Base') }}</label>
                                <select x-model="subBase"
                                        class="w-full mt-1 px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-sky-500 focus:outline-none">
                                    <option value="first" x-show="lastBases?.first">1B: <span x-text="runnerLabel(lastBases?.first)"></span></option>
                                    <option value="second" x-show="lastBases?.second">2B: <span x-text="runnerLabel(lastBases?.second)"></span></option>
                                    <option value="third" x-show="lastBases?.third">3B: <span x-text="runnerLabel(lastBases?.third)"></span></option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase font-semibold">{{ __('Nuevo corredor') }}</label>
                                <select x-model="subInId"
                                        class="w-full mt-1 px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-sky-500 focus:outline-none">
                                    <option value="">{{ __('Selecciona un atleta...') }}</option>
                                    <template x-for="a in rosterForBattingTeam()" :key="a.id">
                                        <option :value="a.id" x-text="`${a.first_name} ${a.last_name}`"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" @click="closeModal()"
                                    class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl">
                                {{ __('Cancelar') }}
                            </button>
                            <button type="button" @click="confirmSubstitute()" :disabled="!canConfirmSubstitute()"
                                    class="flex-1 py-3 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white font-bold rounded-xl">
                                {{ __('Sustituir') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DISI-20: Modal "GESTIONAR CORREDOR" --}}
            {{-- Se abre al hacer click en un corredor del diamond (data-base). --}}
            <div x-show="modal === 'runner'" x-cloak
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="closeModal()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
                    {{-- Header: nombre de la base --}}
                    <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-90 font-semibold">{{ __('Gestionar corredor') }}</div>
                            <h3 class="text-xl font-black uppercase tracking-wider" x-text="runnerModalTitle()"></h3>
                        </div>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                    </div>

                    {{-- Card del corredor --}}
                    <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex-shrink-0">
                        <div class="flex items-center gap-3" x-show="runnerModalRunner()">
                            <div class="w-14 h-14 bg-amber-500 text-white rounded-full flex items-center justify-center font-black text-xl flex-shrink-0"
                                 x-text="runnerModalRunner()?.number ?? '?'"></div>
                            <div class="min-w-0 flex-1">
                                <div class="text-base font-bold text-gray-900 truncate" x-text="(runnerModalRunner()?.first_name || '') + ' ' + (runnerModalRunner()?.last_name || '')"></div>
                                <div class="text-xs text-gray-600 mt-0.5">
                                    <span class="font-semibold" x-text="'#' + (runnerModalRunner()?.lineup_order || '-')"></span>
                                    <span class="text-gray-400">·</span>
                                    <span x-text="runnerModalRunner()?.position || '—'"></span>
                                </div>
                            </div>
                        </div>
                        <div x-show="!runnerModalRunner()" class="text-sm text-gray-500 italic">
                            {{ __('Sin corredor identificado en esta base.') }}
                        </div>
                    </div>

                    {{-- Grid de acciones --}}
                    <div class="p-4 overflow-y-auto flex-1">
                        {{-- DISI-27: el grid de acciones se muestra tambien cuando el corredor es un
                             placeholder (string en lugar de athlete_id). Solo la sustitucion (PR)
                             queda oculta porque requiere un atleta identificado. --}}
                        <div class="grid grid-cols-2 gap-2" x-show="runnerModalRunner() || runnerModalPlaceholder()">
                            {{-- Avanza a siguiente base --}}
                            <button type="button" @click="sendRunnerAction('advance')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">→</span>
                                    <span class="block text-xs">{{ __('Avanza siguiente base') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80" x-text="runnerAdvanceLabel()"></span>
                                </span>
                            </button>

                            {{-- Robo de base --}}
                            <button type="button" @click="sendRunnerAction('stolen_base')"
                                    :disabled="isPitching || runnerBase === 'third'"
                                    class="px-3 py-3 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">🏃</span>
                                    <span class="block text-xs">{{ __('Robo de base') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Steal') }}</span>
                                </span>
                            </button>

                            {{-- Avanza por error --}}
                            <button type="button" @click="sendRunnerAction('error_advance')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">⚠️</span>
                                    <span class="block text-xs">{{ __('Avanza por error') }}</span>
                                </span>
                            </button>

                            {{-- Wild pitch --}}
                            <button type="button" @click="sendRunnerAction('wild_pitch')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">⚾</span>
                                    <span class="block text-xs">{{ __('Wild Pitch') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Corredor avanza') }}</span>
                                </span>
                            </button>

                            {{-- Passed ball --}}
                            <button type="button" @click="sendRunnerAction('passed_ball')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-purple-500 hover:bg-purple-600 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">🥎</span>
                                    <span class="block text-xs">{{ __('Passed Ball') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Corredor avanza') }}</span>
                                </span>
                            </button>

                            {{-- OBS / Obstruccion --}}
                            <button type="button" @click="sendRunnerAction('obstruction')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">🚧</span>
                                    <span class="block text-xs">{{ __('OBS (Obstrucción)') }}</span>
                                </span>
                            </button>

                            {{-- Anota (RBI) --}}
                            <button type="button" @click="sendRunnerAction('score_rbi')"
                                    :disabled="isPitching || runnerBase === 'third'"
                                    class="px-3 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">🏃‍♂️‍➡️</span>
                                    <span class="block text-xs">{{ __('Anota (RBI)') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Carrera con RBI') }}</span>
                                </span>
                            </button>

                            {{-- Anota (sin RBI) --}}
                            <button type="button" @click="sendRunnerAction('score_no_rbi')"
                                    :disabled="isPitching || runnerBase === 'third'"
                                    class="px-3 py-3 bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">🏃‍♂️</span>
                                    <span class="block text-xs">{{ __('Anota (sin RBI)') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Sin credito al bateador') }}</span>
                                </span>
                            </button>

                            {{-- Out @ 2da --}}
                            <button type="button" @click="sendRunnerAction('out_at_2b')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">❌</span>
                                    <span class="block text-xs">{{ __('Out @ 2da') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Out en 2B') }}</span>
                                </span>
                            </button>

                            {{-- Out @ 3ra --}}
                            <button type="button" @click="sendRunnerAction('out_at_3b')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">❌</span>
                                    <span class="block text-xs">{{ __('Out @ 3ra') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Out en 3B') }}</span>
                                </span>
                            </button>

                            {{-- Caught Stealing --}}
                            <button type="button" @click="sendRunnerAction('caught_stealing')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">🛑</span>
                                    <span class="block text-xs">{{ __('Caught Stealing') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Out por robo') }}</span>
                                </span>
                            </button>

                            {{-- Viraje / Pickoff --}}
                            <button type="button" @click="sendRunnerAction('pickoff')"
                                    :disabled="isPitching"
                                    class="px-3 py-3 bg-red-700 hover:bg-red-800 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center text-center">
                                <span class="block leading-tight">
                                    <span class="block text-base">🖐️</span>
                                    <span class="block text-xs">{{ __('Viraje / Pickoff') }}</span>
                                    <span class="block text-[9px] font-normal opacity-80">{{ __('Out en la base') }}</span>
                                </span>
                            </button>
                        </div>

                        {{-- Sustitucion (PR): sub-modal con roster --}}
                        <div class="mt-3 border-t border-gray-200 pt-3" x-show="runnerModalRunner()">
                            <button type="button" @click="openSubstituteModal()"
                                    class="w-full py-3 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                                <span>🔄</span>
                                <span>{{ __('Sustituir corredor (Pinch Runner)') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                        <button type="button" @click="closeModal()"
                                class="w-full py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl">
                            {{ __('Cancelar') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Toast global (errores del pitch) --}}
            <div class="fixed top-4 right-4 z-[60] space-y-2" x-data="toastStack()" @toast.window="show($event.detail.message, $event.detail.level)">
                <template x-for="t in items" :key="t.id">
                    <div x-show="t.visible" x-transition
                         :class="{
                             'bg-emerald-500': t.level === 'success',
                             'bg-rose-500': t.level === 'error',
                             'bg-amber-500': t.level === 'warning',
                             'bg-sky-500': t.level === 'info',
                         }"
                         class="text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium max-w-xs">
                        <span x-text="t.message"></span>
                    </div>
                </template>
            </div>

        </div>
    </div>

</x-app-layout>

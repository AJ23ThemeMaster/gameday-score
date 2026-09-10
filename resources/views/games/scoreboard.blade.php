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

    <div
        class="py-6"
        x-data="scoreboardApp(@js([
            'gameId' => $game->id,
            'pollUrl' => route('games.scoreboard.poll', $game),
            'pitchUrl' => route('games.plays.pitch', $game),
            'endInningUrl' => route('games.plays.end-inning', $game),
            'endGameUrl' => route('games.plays.end-game', $game),
            'homeName' => $game->homeTeam->name,
            'awayName' => $game->awayTeam->name,
            'homeShort' => $game->homeTeam->short_name ?? $game->homeTeam->name,
            'awayShort' => $game->awayTeam->short_name ?? $game->awayTeam->name,
            'csrf' => csrf_token(),
        ]))"
        x-init="start()"
    >
        <div class="max-w-2xl mx-auto sm:px-4">

            {{-- ============ HEADER: LOGOS + SCORES + COUNT ============ --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

                {{-- Logos + scores (Local | Score | Visitante) --}}
                <div class="flex items-stretch border-b-2 border-gray-100">
                    {{-- Local --}}
                    <div class="flex-1 flex items-center justify-end gap-3 p-4">
                        <div class="text-right">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $game->homeTeam->name }}</div>
                            <div class="text-5xl font-black text-gray-900 leading-none" data-score="home">{{ $score['home'] }}</div>
                        </div>
                        @if ($game->homeTeam->logoUrl)
                            <img src="{{ $game->homeTeam->logoUrl }}" class="h-14 w-14 object-contain">
                        @else
                            <div class="h-14 w-14 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs">LOGO</div>
                        @endif
                    </div>

                    {{-- Inning indicator (centro) --}}
                    <div class="flex flex-col items-center justify-center px-3 py-2 bg-gray-50 min-w-[80px]" data-inning-indicator>
                        <div class="text-[10px] uppercase tracking-wider text-gray-500">{{ __('Inning') }}</div>
                        <div class="text-2xl font-black text-gray-900" data-inning-number>{{ $state['inning'] }}</div>
                        <div class="text-xs font-semibold uppercase text-gray-600" data-inning-half>{{ $state['half'] === 'top' ? '▲' : '▼' }}</div>
                    </div>

                    {{-- Visitante --}}
                    <div class="flex-1 flex items-center justify-start gap-3 p-4">
                        @if ($game->awayTeam->logoUrl)
                            <img src="{{ $game->awayTeam->logoUrl }}" class="h-14 w-14 object-contain">
                        @else
                            <div class="h-14 w-14 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs">LOGO</div>
                        @endif
                        <div class="text-left">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $game->awayTeam->name }}</div>
                            <div class="text-5xl font-black text-gray-900 leading-none" data-score="away">{{ $score['away'] }}</div>
                        </div>
                    </div>
                </div>

                {{-- Count: BOLAS / STRIKES / OUTS --}}
                <div class="grid grid-cols-3 border-b-2 border-gray-100 text-center">
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

                {{-- Pitcher + Batter --}}
                <div class="grid grid-cols-2 gap-0 border-b-2 border-gray-100">
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

                {{-- On-deck (Prevenido) --}}
                <div class="px-3 py-2 bg-gray-50 border-b border-gray-100 flex items-center gap-2" data-card="ondeck">
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

                {{-- Diamond: corredores en base --}}
                {{-- Contenedor: cuadrado verde con bases, home y pitcher mound posicionados dentro. --}}
                <div class="bg-emerald-700 px-3 py-4 relative" style="background-image: radial-gradient(ellipse at center, #15803d 0%, #14532d 100%);">
                    <div class="mx-auto relative" style="width: 240px; height: 240px;" data-diamond>
                        {{-- 2B (arriba) --}}
                        <div class="absolute top-2 left-1/2 -translate-x-1/2" data-base="second">
                            <div class="w-11 h-11 {{ ! empty($runners['second']) ? 'bg-amber-300 border-2 border-amber-500 shadow-md' : 'bg-emerald-50/90 border-2 border-white' }} rounded flex items-center justify-center font-bold text-xs {{ ! empty($runners['second']) ? 'text-amber-900' : 'text-emerald-700/40' }}">
                                @if (! empty($runners['second']))
                                    <div class="text-center leading-tight">
                                        <div class="text-[9px] font-bold">2B</div>
                                        <div class="text-[11px] font-black">{{ $runners['second']['number'] ?? '' }}</div>
                                    </div>
                                @else
                                    2B
                                @endif
                            </div>
                        </div>
                        {{-- 3B (izquierda) --}}
                        <div class="absolute top-1/2 left-2 -translate-y-1/2" data-base="third">
                            <div class="w-11 h-11 {{ ! empty($runners['third']) ? 'bg-amber-300 border-2 border-amber-500 shadow-md' : 'bg-emerald-50/90 border-2 border-white' }} rounded flex items-center justify-center font-bold text-xs {{ ! empty($runners['third']) ? 'text-amber-900' : 'text-emerald-700/40' }}">
                                @if (! empty($runners['third']))
                                    <div class="text-center leading-tight">
                                        <div class="text-[9px] font-bold">3B</div>
                                        <div class="text-[11px] font-black">{{ $runners['third']['number'] ?? '' }}</div>
                                    </div>
                                @else
                                    3B
                                @endif
                            </div>
                        </div>
                        {{-- 1B (derecha) --}}
                        <div class="absolute top-1/2 right-2 -translate-y-1/2" data-base="first">
                            <div class="w-11 h-11 {{ ! empty($runners['first']) ? 'bg-amber-300 border-2 border-amber-500 shadow-md' : 'bg-emerald-50/90 border-2 border-white' }} rounded flex items-center justify-center font-bold text-xs {{ ! empty($runners['first']) ? 'text-amber-900' : 'text-emerald-700/40' }}">
                                @if (! empty($runners['first']))
                                    <div class="text-center leading-tight">
                                        <div class="text-[9px] font-bold">1B</div>
                                        <div class="text-[11px] font-black">{{ $runners['first']['number'] ?? '' }}</div>
                                    </div>
                                @else
                                    1B
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

                {{-- ============ 3 TABS (PITCHEo / BATEo / EXTRAS) ============ --}}
                <div x-data="{ tab: 'pitch' }">
                    <div class="grid grid-cols-3 border-t-2 border-gray-100">
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

                    {{-- Tab content: PITCHEo (Fase 2 — funcional) --}}
                    <div x-show="tab === 'pitch'" x-cloak class="grid grid-cols-2 gap-3 p-4">
                        <button type="button" @click="sendBall()"
                                :disabled="isPitching"
                                class="py-8 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-2xl font-black rounded-2xl transition">
                            {{ __('Ball') }}
                        </button>
                        <button type="button" @click="openStrikeModal()"
                                :disabled="isPitching"
                                class="py-8 bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-2xl font-black rounded-2xl transition">
                            {{ __('Strike') }}
                        </button>
                        <button type="button" @click="sendFoul()"
                                :disabled="isPitching"
                                class="py-8 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-2xl font-black rounded-2xl transition">
                            {{ __('Foul') }}
                        </button>
                        <button type="button" @click="openOutStep1()"
                                :disabled="isPitching"
                                class="py-8 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white text-2xl font-black rounded-2xl transition">
                            {{ __('Out') }}
                        </button>
                    </div>

                    {{-- Tab content: BATEo (Fase 3 — hits) --}}
                    <div x-show="tab === 'hit'" x-cloak class="grid grid-cols-2 gap-3 p-4">
                        <button type="button" @click="openHitModal('single')"
                                :disabled="isPitching"
                                class="py-6 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-lg font-black rounded-2xl transition">
                            {{ __('Sencillo') }}
                            <div class="text-[10px] font-normal opacity-80 mt-1">1B</div>
                        </button>
                        <button type="button" @click="openHitModal('double')"
                                :disabled="isPitching"
                                class="py-6 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-lg font-black rounded-2xl transition">
                            {{ __('Doble') }}
                            <div class="text-[10px] font-normal opacity-80 mt-1">2B</div>
                        </button>
                        <button type="button" @click="openHitModal('triple')"
                                :disabled="isPitching"
                                class="py-6 bg-violet-500 hover:bg-violet-600 disabled:opacity-50 text-white text-lg font-black rounded-2xl transition">
                            {{ __('Triple') }}
                            <div class="text-[10px] font-normal opacity-80 mt-1">3B</div>
                        </button>
                        <button type="button" @click="openHitModal('hr')"
                                :disabled="isPitching"
                                class="py-6 bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-lg font-black rounded-2xl transition">
                            {{ __('HR') }}
                            <div class="text-[10px] font-normal opacity-80 mt-1">Home Run</div>
                        </button>
                        <button type="button" @click="openHitModal('inside_park')"
                                :disabled="isPitching"
                                class="col-span-2 py-5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-lg font-black rounded-2xl transition">
                            {{ __('HR de pierna') }}
                        </button>
                    </div>

                    {{-- Tab content: EXTRAS (Fase 4) --}}
                    <div x-show="tab === 'extra'" x-cloak class="grid grid-cols-1 gap-2 p-4">
                        <button type="button" disabled
                                class="py-3 bg-gray-100 text-gray-700 text-base font-bold rounded-lg opacity-60 cursor-not-allowed">
                            {{ __('Sustituir') }}
                            <div class="text-[10px] font-normal opacity-80 mt-1">{{ __('Disponible proximamente') }}</div>
                        </button>
                        <button type="button" @click="sendBalk()"
                                :disabled="isPitching"
                                class="py-3 bg-purple-500 hover:bg-purple-600 disabled:opacity-50 text-white text-base font-bold rounded-lg transition">
                            {{ __('Balk') }}
                            <div class="text-[10px] font-normal opacity-80 mt-1">{{ __('Corredores avanzan 1 base') }}</div>
                        </button>
                        <button type="button" @click="openBuntModal()"
                                :disabled="isPitching"
                                class="py-3 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-base font-bold rounded-lg transition">
                            {{ __('Toque de bolas') }}
                            <div class="text-[10px] font-normal opacity-80 mt-1">{{ __('Sacrifice o bunt single') }}</div>
                        </button>
                        <button type="button" @click="openEndInningModal()"
                                :disabled="isPitching"
                                class="py-3 bg-rose-50 hover:bg-rose-100 disabled:opacity-50 text-rose-700 text-base font-bold rounded-lg border border-rose-200 transition">
                            {{ __('Finalizar inning') }}
                        </button>
                        <button type="button" @click="openEndGameModal()"
                                :disabled="isPitching"
                                class="py-3 bg-rose-100 hover:bg-rose-200 disabled:opacity-50 text-rose-800 text-base font-bold rounded-lg border border-rose-300 transition">
                            {{ __('Finalizar juego') }}
                        </button>
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

                        {{-- Diamante SVG con los 9 fildeadores + bateador --}}
                        <div class="relative bg-emerald-700 rounded-xl mx-auto" style="width: 280px; height: 280px;">
                            {{-- Infield dirt --}}
                            <div class="absolute inset-6 bg-amber-100/30 rounded-full"></div>

                            {{-- Bases --}}
                            <div class="absolute top-2 left-1/2 -translate-x-1/2 w-10 h-10 bg-white border-2 border-gray-300 rounded rotate-45"></div>
                            <div class="absolute top-1/2 right-2 -translate-y-1/2 w-10 h-10 bg-white border-2 border-gray-300 rounded rotate-45"></div>
                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 w-10 h-10 bg-white border-2 border-gray-300 rounded rotate-45"></div>
                            <div class="absolute top-1/2 left-2 -translate-y-1/2 w-10 h-10 bg-white border-2 border-gray-300 rounded rotate-45"></div>

                            {{-- Pitcher mound --}}
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 bg-amber-200/90 rounded-full flex items-center justify-center text-xs font-black text-amber-900">P</div>

                            {{-- Posiciones clickeables --}}
                            <button type="button" @click="addFielder('C')"  class="absolute bottom-1 left-1/2 -translate-x-1/2 w-12 h-8 bg-slate-100 hover:bg-amber-200 rounded text-xs font-bold">C</button>
                            <button type="button" @click="addFielder('1B')" class="absolute top-1/2 right-1 -translate-y-1/2 w-12 h-8 bg-slate-100 hover:bg-amber-200 rounded text-xs font-bold">1B</button>
                            <button type="button" @click="addFielder('2B')" class="absolute top-2 right-12 w-12 h-8 bg-slate-100 hover:bg-amber-200 rounded text-xs font-bold">2B</button>
                            <button type="button" @click="addFielder('3B')" class="absolute top-1/2 left-1 -translate-y-1/2 w-12 h-8 bg-slate-100 hover:bg-amber-200 rounded text-xs font-bold">3B</button>
                            <button type="button" @click="addFielder('SS')" class="absolute top-2 left-12 w-12 h-8 bg-slate-100 hover:bg-amber-200 rounded text-xs font-bold">SS</button>
                            <button type="button" @click="addFielder('LF')" class="absolute top-1 left-1/4 w-12 h-8 bg-slate-100 hover:bg-amber-200 rounded text-xs font-bold">LF</button>
                            <button type="button" @click="addFielder('CF')" class="absolute top-0 left-1/2 -translate-x-1/2 w-12 h-8 bg-slate-100 hover:bg-amber-200 rounded text-xs font-bold">CF</button>
                            <button type="button" @click="addFielder('RF')" class="absolute top-1 right-1/4 w-12 h-8 bg-slate-100 hover:bg-amber-200 rounded text-xs font-bold">RF</button>
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

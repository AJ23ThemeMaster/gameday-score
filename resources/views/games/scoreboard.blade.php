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
            'homeName' => $game->homeTeam->name,
            'awayName' => $game->awayTeam->name,
            'homeShort' => $game->homeTeam->short_name ?? $game->homeTeam->name,
            'awayShort' => $game->awayTeam->short_name ?? $game->awayTeam->name,
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
                        <div class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                            P
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Pitcheando') }}</div>
                            @if ($pitcher)
                                <div class="text-sm font-bold text-gray-900 truncate">
                                    <span class="text-indigo-600">#{{ $pitcher->number ?? '?' }}</span>
                                    {{ $pitcher->full_name }}
                                </div>
                            @else
                                <div class="text-sm text-gray-400 italic">{{ __('Sin lanzador') }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Batter --}}
                    <div class="p-3 flex items-center gap-3" data-card="batter">
                        <div class="w-9 h-9 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                            AB
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Al bate') }}</div>
                            @if ($batter)
                                <div class="text-sm font-bold text-gray-900 truncate">
                                    <span class="text-amber-600">#{{ $batter->number ?? '?' }}</span>
                                    {{ $batter->full_name }}
                                </div>
                            @else
                                <div class="text-sm text-gray-400 italic">{{ __('Sin bateador') }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- On-deck (Prevenido) --}}
                @if ($onDeck)
                    <div class="px-3 py-2 bg-gray-50 border-b border-gray-100 flex items-center gap-2" data-card="ondeck">
                        <div class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Prevenido') }}</div>
                        <div class="text-sm font-bold text-gray-700">
                            <span class="text-gray-500">#{{ $onDeck['number'] ?? '?' }}</span>
                            {{ $onDeck['name'] }}
                        </div>
                    </div>
                @endif

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

                    {{-- Tab content: PITCHEo (Fase 2) --}}
                    <div x-show="tab === 'pitch'" x-cloak class="grid grid-cols-2 gap-3 p-4">
                        <button type="button" disabled
                                class="py-8 bg-emerald-500 text-white text-2xl font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('Ball') }}
                        </button>
                        <button type="button" disabled
                                class="py-8 bg-rose-500 text-white text-2xl font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('Strike') }}
                        </button>
                        <button type="button" disabled
                                class="py-8 bg-amber-500 text-white text-2xl font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('Foul') }}
                        </button>
                        <button type="button" disabled
                                class="py-8 bg-slate-700 text-white text-2xl font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('Out') }}
                        </button>
                        <p class="col-span-2 text-xs text-center text-gray-500 mt-2">
                            <span x-show="false"></span>
                            {{ __('Disponible en la Fase 2') }}
                        </p>
                    </div>

                    {{-- Tab content: BATEo (Fase 3) --}}
                    <div x-show="tab === 'hit'" x-cloak class="grid grid-cols-2 gap-3 p-4">
                        <button type="button" disabled
                                class="py-8 bg-sky-500 text-white text-xl font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('Sencillo') }}
                        </button>
                        <button type="button" disabled
                                class="py-8 bg-indigo-500 text-white text-xl font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('Doble') }}
                        </button>
                        <button type="button" disabled
                                class="py-8 bg-violet-500 text-white text-xl font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('Triple') }}
                        </button>
                        <button type="button" disabled
                                class="py-8 bg-rose-600 text-white text-xl font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('HR') }}
                        </button>
                        <button type="button" disabled
                                class="col-span-2 py-6 bg-fuchsia-600 text-white text-lg font-black rounded-2xl opacity-60 cursor-not-allowed">
                            {{ __('HR de pierna') }}
                        </button>
                        <p class="col-span-2 text-xs text-center text-gray-500 mt-2">
                            {{ __('Disponible en la Fase 3') }}
                        </p>
                    </div>

                    {{-- Tab content: EXTRAS (Fase 4) --}}
                    <div x-show="tab === 'extra'" x-cloak class="grid grid-cols-1 gap-2 p-4">
                        <button type="button" disabled
                                class="py-3 bg-gray-100 text-gray-700 text-base font-bold rounded-lg opacity-60 cursor-not-allowed">
                            {{ __('Sustituir') }}
                        </button>
                        <button type="button" disabled
                                class="py-3 bg-gray-100 text-gray-700 text-base font-bold rounded-lg opacity-60 cursor-not-allowed">
                            {{ __('Balk') }}
                        </button>
                        <button type="button" disabled
                                class="py-3 bg-gray-100 text-gray-700 text-base font-bold rounded-lg opacity-60 cursor-not-allowed">
                            {{ __('Toque de bolas') }}
                        </button>
                        <button type="button" disabled
                                class="py-3 bg-rose-50 text-rose-700 text-base font-bold rounded-lg border border-rose-200 opacity-60 cursor-not-allowed">
                            {{ __('Finalizar inning') }}
                        </button>
                        <button type="button" disabled
                                class="py-3 bg-rose-100 text-rose-800 text-base font-bold rounded-lg border border-rose-300 opacity-60 cursor-not-allowed">
                            {{ __('Finalizar juego') }}
                        </button>
                        <p class="text-xs text-center text-gray-500 mt-2">
                            {{ __('Disponible en la Fase 4') }}
                        </p>
                    </div>
                </div>

            </div>

            {{-- Indicador de conexion en vivo --}}
            <div class="mt-3 text-center text-xs text-gray-400" data-poll-indicator>
                <span x-text="pollStatus"></span>
            </div>

        </div>
    </div>

</x-app-layout>

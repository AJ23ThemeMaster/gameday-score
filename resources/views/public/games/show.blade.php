<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <title>{{ __('En vivo') }} · {{ $game->homeTeam->short_name ?? $game->homeTeam->name }} vs {{ $game->awayTeam->short_name ?? $game->awayTeam->name }} · Gameday Score</title>
    <meta name="description" content="{{ __('Marcador en vivo y avance del juego') }} {{ $game->homeTeam->name }} vs {{ $game->awayTeam->name }}.">
    <meta property="og:title" content="{{ $game->homeTeam->name }} vs {{ $game->awayTeam->name }} · Gameday Score">
    <meta property="og:description" content="{{ $game->category->name ?? '' }} · {{ $game->scheduled_at->format('d/m/Y H:i') }}">
    <meta property="og:type" content="website">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* DISI-49: ocultar paneles inactivos del play-by-play hasta que
           Alpine.js haya procesado x-show (evita flash de todos los innings
           apilados al cargar la pagina). */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white min-h-screen">

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        @php
            // DISI-51: configuracion del badge de estado (esquina superior derecha).
            // Cada entrada: [bg, text, border, label, animatePulse].
            // - in_progress: verde (badge sustituye al banner rojo grande)
            // - scheduled: indigo "Programado"
            // - paused: ambar "Juego Pausado"
            // - completed: verde "Finalizado"
            // - suspended: rojo "Suspendido"
            // - cancelled: rojo "Cancelado"
            $badgeConfig = [
                'in_progress' => ['bg-emerald-600', 'text-white',           'border-emerald-500', 'En vivo',        true],
                'scheduled'   => ['bg-indigo-600',   'text-white',           'border-indigo-500',   'Programado',     false],
                'paused'      => ['bg-amber-600',    'text-white',           'border-amber-500',    'Juego Pausado',  false],
                'completed'   => ['bg-green-600',    'text-white',           'border-green-500',    'Finalizado',     false],
                'suspended'   => ['bg-red-600',      'text-white',           'border-red-500',      'Suspendido',     false],
                'cancelled'   => ['bg-red-600',      'text-white',           'border-red-500',      'Cancelado',      false],
            ];
            [$badgeBg, $badgeText, $badgeBorder, $badgeLabel, $badgePulse] =
                // DISI-51: trim() porque en algunos juegos historicos el status
                // se guardo con un trailing space (ej. 'scheduled ' en vez de
                // 'scheduled'), lo que rompe el lookup del array.
                $badgeConfig[trim($game->status)] ?? ['bg-slate-600', 'text-white', 'border-slate-500', ucfirst(trim($game->status)), false];

            // DISI-51: clases del banner grande (mostrado solo para estados NO en vivo,
            // porque el badge de la esquina ya hace ese trabajo cuando in_progress).
            $statusLabels = [
                'scheduled'   => 'Programado',
                'in_progress' => 'En vivo',
                'paused'      => 'Pausado',
                'completed'   => 'Finalizado',
                'suspended'   => 'Suspendido',
                'cancelled'   => 'Cancelado',
            ];
            $statusClasses = [
                'scheduled' => 'bg-indigo-500/20 text-indigo-200 border border-indigo-400/40',
                'paused'    => 'bg-amber-500/20 text-amber-200 border border-amber-400/40',
                'completed' => 'bg-green-500/20 text-green-200 border border-green-400/40',
                'suspended' => 'bg-red-500/20 text-red-200 border border-red-400/40',
                'cancelled' => 'bg-red-700/20 text-red-200 border border-red-400/40',
            ];
        @endphp

        {{-- Header: logo a la izquierda, badge de estado + boton refresh a la derecha --}}
        <div class="flex items-center justify-between mb-4">
            <a href="/" class="flex items-center gap-2 text-white hover:text-indigo-300 transition">
                <span class="text-2xl">⚾</span>
                <span class="font-bold">Gameday Score</span>
            </a>
            <div class="flex items-center gap-2">
                {{-- DISI-51: boton de refresh manual a la izquierda del badge --}}
                <button type="button"
                        onclick="location.reload()"
                        title="{{ __('Refrescar') }}"
                        aria-label="{{ __('Refrescar') }}"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-700/50 hover:bg-slate-600 text-slate-300 hover:text-white transition border border-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>

                {{-- DISI-51: badge con color + texto segun status del juego --}}
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full {{ $badgeBg }} {{ $badgeText }} border {{ $badgeBorder }}">
                    @if ($badgePulse)
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    @endif
                    {{ __($badgeLabel) }}
                </span>
            </div>
        </div>

        {{-- Status banner: solo se muestra cuando NO esta en vivo (en ese caso
             el badge de la esquina ya indica "En vivo" en verde). --}}
        @if (trim($game->status) !== 'in_progress')
            <div class="rounded-lg p-3 mb-6 text-center {{ $statusClasses[trim($game->status)] ?? 'bg-slate-700/50' }}">
                <span class="text-sm font-semibold uppercase tracking-wider">
                    {{ __($statusLabels[trim($game->status)] ?? $game->status) }}
                </span>
                @if ($game->started_at)
                    <span class="text-xs opacity-75 ml-2">{{ __('desde') }} {{ $game->started_at->format('H:i') }}</span>
                @endif
            </div>
        @endif

        {{-- Scoreboard --}}
        <div class="bg-slate-800/60 backdrop-blur rounded-2xl shadow-2xl border border-slate-700 p-6 mb-6">
            <div class="flex items-center justify-between">
                {{-- Home --}}
                <div class="flex-1 text-center">
                    @if ($game->homeTeam->logoUrl)
                        <img src="{{ $game->homeTeam->logoUrl }}" alt="{{ $game->homeTeam->name }}" class="h-20 w-20 mx-auto object-contain mb-2">
                    @endif
                    <h2 class="font-bold text-base sm:text-lg">{{ $game->homeTeam->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1">{{ __('Local') }}</p>
                </div>

                {{-- Score --}}
                <div class="px-4 text-center">
                    <div class="text-5xl sm:text-6xl font-black tracking-tighter">
                        <span id="home-score-display">{{ $game->home_score }}</span>
                        <span class="text-slate-500 mx-2">-</span>
                        <span id="away-score-display">{{ $game->away_score }}</span>
                    </div>
                    @if ($game->isInProgress() || $game->isCompleted())
                        <div class="mt-2 text-xs text-slate-400">
                            {{ __('Inning') }} {{ $game->current_inning }}
                            @if ($game->inning_half === 'top') ▲ @else ▼ @endif
                        </div>
                    @endif
                </div>

                {{-- Away --}}
                <div class="flex-1 text-center">
                    @if ($game->awayTeam->logoUrl)
                        <img src="{{ $game->awayTeam->logoUrl }}" alt="{{ $game->awayTeam->name }}" class="h-20 w-20 mx-auto object-contain mb-2">
                    @endif
                    <h2 class="font-bold text-base sm:text-lg">{{ $game->awayTeam->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1">{{ __('Visitante') }}</p>
                </div>
            </div>

            {{-- DISI-53: Pitcher + Batter cards (solo mientras el juego esta en curso) --}}
            @if ($game->isInProgress())
                <div class="mt-5 pt-5 border-t border-slate-700 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Pitcher card --}}
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-900/50 border border-slate-700/50">
                        <div class="w-11 h-11 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[12px] flex-shrink-0 overflow-hidden">
                            @if ($currentPitcher && ($currentPitcher->photo_path ?? null))
                                <img src="{{ $currentPitcher->photoUrl }}" class="w-full h-full object-cover" alt="{{ $currentPitcher->full_name }}">
                            @elseif ($currentPitcher)
                                <span>{{ mb_strtoupper(mb_substr($currentPitcher->first_name ?? '', 0, 1)) }}{{ mb_strtoupper(mb_substr($currentPitcher->last_name ?? '', 0, 1)) }}</span>
                            @else
                                <span>?</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] uppercase tracking-wider text-indigo-300 font-bold">{{ __('Pitcheando') }}</div>
                            @if ($currentPitcher)
                                <div class="text-sm font-bold text-white truncate">
                                    <span class="text-indigo-400">#{{ $currentPitcher->number ?? '?' }}</span>
                                    {{ $currentPitcher->full_name }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $pitcherStats['pitches'] }} {{ __('lanz.') }} ({{ $pitcherStats['strikes'] }}S / {{ $pitcherStats['balls'] }}B) · K: {{ $pitcherStats['strikeouts'] }} · H: {{ $pitcherStats['hits'] }}
                                </div>
                            @else
                                <div class="text-sm text-slate-500 italic">{{ __('Sin lanzador') }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Batter card --}}
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-900/50 border border-slate-700/50">
                        <div class="w-11 h-11 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-[12px] flex-shrink-0 overflow-hidden">
                            @if ($currentBatter && ($currentBatter->photo_path ?? null))
                                <img src="{{ $currentBatter->photoUrl }}" class="w-full h-full object-cover" alt="{{ $currentBatter->full_name }}">
                            @elseif ($currentBatter)
                                <span>{{ mb_strtoupper(mb_substr($currentBatter->first_name ?? '', 0, 1)) }}{{ mb_strtoupper(mb_substr($currentBatter->last_name ?? '', 0, 1)) }}</span>
                            @else
                                <span>?</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] uppercase tracking-wider text-amber-300 font-bold">{{ __('Al bate') }}</div>
                            @if ($currentBatter)
                                <div class="text-sm font-bold text-white truncate">
                                    <span class="text-amber-400">#{{ $currentBatter->number ?? '?' }}</span>
                                    {{ $currentBatter->full_name }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    AB: {{ $batterStats['at_bats'] }} · H: {{ $batterStats['hits'] }} · AVG: {{ number_format($batterStats['avg'], 3, '.', '') }} · BB: {{ $batterStats['walks'] }} · K: {{ $batterStats['strikeouts'] }}
                                </div>
                            @else
                                <div class="text-sm text-slate-500 italic">{{ __('Sin bateador') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- B-S-O --}}
            @if ($game->isInProgress())
                <div class="mt-5 pt-5 border-t border-slate-700">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold">{{ $game->balls }}-{{ $game->strikes }}</div>
                            <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Bolas y strikes') }}</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ $game->outs }}</div>
                            <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Outs') }}</div>
                        </div>
                        <div>
                            <div class="flex items-center justify-center gap-1.5">
                                @php $b = $game->bases ?? []; @endphp
                                <span id="base-3-display" class="w-3 h-3 rounded-full {{ ! empty($b['third']) ? 'bg-yellow-400' : 'bg-slate-600' }}"></span>
                                <span id="base-2-display" class="w-3 h-3 rounded-full {{ ! empty($b['second']) ? 'bg-yellow-400' : 'bg-slate-600' }}"></span>
                                <span id="base-1-display" class="w-3 h-3 rounded-full {{ ! empty($b['first']) ? 'bg-yellow-400' : 'bg-slate-600' }}"></span>
                            </div>
                            <div class="text-xs text-slate-400 uppercase tracking-wider mt-1">{{ __('Bases') }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Game info --}}
        <div class="bg-slate-800/40 backdrop-blur rounded-xl border border-slate-700 p-4 mb-6 text-sm">
            <dl class="grid grid-cols-2 gap-3">
                <div>
                    <dt class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Fecha y hora') }}</dt>
                    <dd class="font-medium mt-0.5">{{ $game->scheduled_at->format('d/m/Y · H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Categoría') }}</dt>
                    <dd class="font-medium mt-0.5">{{ $game->category->name ?? '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Estadio') }}</dt>
                    <dd class="font-medium mt-0.5">{{ $game->stadium->name ?? '—' }}{{ $game->stadium?->city ? ' · ' . $game->stadium->city : '' }}</dd>
                </div>
                @if ($game->scorekeepers->count())
                    <div class="col-span-2">
                        <dt class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Anotadores') }}</dt>
                        <dd class="font-medium mt-0.5">{{ $game->scorekeepers->pluck('full_name')->join(', ') }}</dd>
                    </div>
                @endif
                @if ($game->referees->count())
                    <div class="col-span-2">
                        <dt class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Árbitros') }}</dt>
                        <dd class="font-medium mt-0.5">{{ $game->referees->pluck('full_name')->join(', ') }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Play by play (DISI-48 + DISI-49 + DISI-50 + DISI-52) --}}
        @if (! empty($playByPlay))
            @php
                $inningNumbers = array_column($playByPlay, 'inning');
                // DISI-50: solo mostrar tabs para innings con jugadas registradas
                // o el current_inning (para que el usuario pueda ver "Este inning
                // aun no se ha jugado" en vivo).
                $maxInningPlayed = ! empty($inningNumbers) ? max($inningNumbers) : 0;
                $tabInnings = range(1, max($maxInningPlayed, (int) $game->current_inning ?: 1));

                // DISI-52: tab activo por defecto = el inning que se esta jugando
                // actualmente (current_inning del Game). Si por algun motivo no
                // existe en la lista de tabs (caso edge: current_inning=0 o
                // mayor al maximo), caemos al primer tab disponible.
                $maxTab = max($tabInnings);
                $defaultInning = (int) $game->current_inning ?: $maxTab;
                if ($defaultInning > $maxTab) {
                    $defaultInning = $maxTab;
                }
                if ($defaultInning < 1) {
                    $defaultInning = $maxTab;
                }
            @endphp

            <div x-data="{ activeInning: {{ $defaultInning }} }"
                 class="bg-slate-800/60 backdrop-blur rounded-2xl shadow-2xl border border-slate-700 p-4 sm:p-6 mb-6">

                <h2 class="text-base sm:text-lg font-bold mb-4 flex items-center gap-2">
                    <span class="text-xl">📋</span>
                    {{ __('Jugada por jugada') }}
                </h2>

                {{-- Tab strip: una pestana por inning --}}
                <div class="flex flex-wrap gap-1 mb-4 border-b border-slate-700 overflow-x-auto">
                    @foreach ($tabInnings as $inningN)
                        @php
                            $hasPlays = collect($playByPlay)->firstWhere('inning', $inningN);
                            $isActive = $inningN === $defaultInning;
                        @endphp
                        <button type="button"
                                @click="activeInning = {{ $inningN }}"
                                :class="activeInning === {{ $inningN }}
                                    ? 'bg-slate-700 text-white border-b-2 border-emerald-400'
                                    : 'bg-slate-800/40 text-slate-400 hover:text-slate-200 hover:bg-slate-700/50'"
                                class="px-4 py-2 text-xs sm:text-sm font-bold uppercase tracking-wider rounded-t-lg transition-colors cursor-pointer whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            {{ __('Inning') }} {{ $inningN }}
                        </button>
                    @endforeach
                </div>

                {{-- Panels: uno por inning (x-show) --}}
                @foreach ($tabInnings as $inningN)
                    @php
                        $inningBlock = collect($playByPlay)->firstWhere('inning', $inningN);
                        $topPlays = $inningBlock['top'] ?? [];
                        $bottomPlays = $inningBlock['bottom'] ?? [];
                    @endphp

                    <div x-show="activeInning === {{ $inningN }}"
                         x-cloak
                         x-transition.opacity.duration.150ms
                         class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">

                        {{-- Columna izquierda: LOCAL (home, bottom half) --}}
                        <div class="bg-slate-900/40 rounded-lg border border-slate-700/50 p-3">
                            <h4 class="text-[11px] sm:text-xs font-bold text-emerald-300 uppercase tracking-wider mb-2 pb-1 border-b border-slate-700/50 flex items-center gap-1">
                                <span>▼</span>
                                <span>{{ $game->homeTeam->short_name ?? $game->homeTeam->name }}</span>
                                <span class="text-slate-500 font-normal normal-case tracking-normal">({{ __('Local') }})</span>
                            </h4>
                            <div class="space-y-0.5">
                                @forelse ($bottomPlays as $play)
                                    @include('public.games._play-line', ['play' => $play])
                                @empty
                                    <p class="text-xs text-slate-500 italic py-2">
                                        @if ($inningBlock)
                                            {{ __('Aun no hay jugadas del local en este inning.') }}
                                        @else
                                            {{ __('Este inning aun no se ha jugado.') }}
                                        @endif
                                    </p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Columna derecha: VISITANTE (away, top half) --}}
                        <div class="bg-slate-900/40 rounded-lg border border-slate-700/50 p-3">
                            <h4 class="text-[11px] sm:text-xs font-bold text-sky-300 uppercase tracking-wider mb-2 pb-1 border-b border-slate-700/50 flex items-center gap-1">
                                <span>▲</span>
                                <span>{{ $game->awayTeam->short_name ?? $game->awayTeam->name }}</span>
                                <span class="text-slate-500 font-normal normal-case tracking-normal">({{ __('Visitante') }})</span>
                            </h4>
                            <div class="space-y-0.5">
                                @forelse ($topPlays as $play)
                                    @include('public.games._play-line', ['play' => $play])
                                @empty
                                    <p class="text-xs text-slate-500 italic py-2">
                                        @if ($inningBlock)
                                            {{ __('Aun no hay jugadas del visitante en este inning.') }}
                                        @else
                                            {{ __('Este inning aun no se ha jugado.') }}
                                        @endif
                                    </p>
                                @endforelse
                            </div>
                        </div>

                    </div>
                @endforeach

            </div>
        @endif

        {{-- Rules reminder --}}
        <div class="bg-slate-800/40 backdrop-blur rounded-xl border border-slate-700 p-4 mb-6 text-xs text-slate-400">
            <p class="font-semibold text-slate-300 mb-1">{{ __('Reglas del juego') }}</p>
            <p>
                {{ $game->innings_count }} {{ __('innings') }},
                {{ __('Nocaut') }} -{{ $game->mercy_rule_difference }}
                {{ __('desde el inning') }} {{ $game->mercy_rule_inning }}
                @if ($game->pitch_limit)
                    · {{ __('Pitch limit') }}: {{ $game->pitch_limit }}
                @endif
            </p>
        </div>

        {{-- Footer --}}
        <div class="text-center text-xs text-slate-500 mt-8">
            <p>
                <a href="/" class="hover:text-slate-300 underline">{{ __('Gameday Score') }}</a>
                · {{ __('Vista pública sin login') }}
                · {{ __('Actualizado') }} {{ now()->format('H:i') }}
            </p>
        </div>
    </div>

    <script>
        // DISI-51: auto-refresh cada 30 segundos para mantener el marcador
        // y las jugadas actualizadas. Antes era cada 10s; subido a 30s porque
        // la vista publica suele ser informativa y los usuarios no necesitan
        // una frecuencia tan alta (ademas reduce carga al server). El usuario
        // puede forzar una actualizacion inmediata con el boton refresh del
        // header (esquina superior derecha).
        setTimeout(() => location.reload(), 30000);
    </script>

</body>
</html>

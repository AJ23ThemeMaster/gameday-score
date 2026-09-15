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
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white min-h-screen">

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <a href="/" class="flex items-center gap-2 text-white hover:text-indigo-300 transition">
                <span class="text-2xl">⚾</span>
                <span class="font-bold">Gameday Score</span>
            </a>
            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-indigo-600/30 text-indigo-200 border border-indigo-500/50">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-300 animate-pulse"></span>
                {{ __('En vivo') }}
            </span>
        </div>

        {{-- Status banner --}}
        @php
            $statusLabels = ['scheduled' => 'Programado', 'in_progress' => 'En vivo', 'paused' => 'Pausado', 'completed' => 'Finalizado', 'suspended' => 'Suspendido', 'cancelled' => 'Cancelado'];
            $statusClasses = ['scheduled' => 'bg-blue-500/20 text-blue-200', 'in_progress' => 'bg-red-500/30 text-red-100 border border-red-400/50', 'paused' => 'bg-yellow-500/20 text-yellow-200', 'completed' => 'bg-green-500/20 text-green-200', 'suspended' => 'bg-gray-500/20 text-gray-300', 'cancelled' => 'bg-gray-700/30 text-gray-400'];
        @endphp
        <div class="rounded-lg p-3 mb-6 text-center {{ $statusClasses[$game->status] ?? 'bg-slate-700/50' }}">
            <span class="text-sm font-semibold uppercase tracking-wider">
                {{ __($statusLabels[$game->status] ?? $game->status) }}
            </span>
            @if ($game->started_at)
                <span class="text-xs opacity-75 ml-2">{{ __('desde') }} {{ $game->started_at->format('H:i') }}</span>
            @endif
        </div>

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

            {{-- B-S-O --}}
            @if ($game->isInProgress())
                <div class="mt-6 pt-6 border-t border-slate-700">
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

        {{-- Play by play (DISI-48) --}}
        @if (! empty($playByPlay))
            <div class="bg-slate-800/60 backdrop-blur rounded-2xl shadow-2xl border border-slate-700 p-4 sm:p-6 mb-6">
                <h2 class="text-base sm:text-lg font-bold mb-4 flex items-center gap-2">
                    <span class="text-xl">📋</span>
                    {{ __('Jugada por jugada') }}
                </h2>

                @foreach ($playByPlay as $inningBlock)
                    <div class="mb-5 last:mb-0">
                        <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-300 mb-2 border-b border-slate-700 pb-1">
                            {{ __('Inning') }} {{ $inningBlock['inning'] }}
                        </h3>

                        @if (count($inningBlock['top']))
                            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 mt-2">
                                ▲ {{ $game->awayTeam->short_name ?? $game->awayTeam->name }}
                                <span class="text-slate-500 font-normal normal-case tracking-normal">({{ __('Visitante') }})</span>
                            </h4>
                            <div class="space-y-0.5">
                                @foreach ($inningBlock['top'] as $play)
                                    @include('public.games._play-line', ['play' => $play])
                                @endforeach
                            </div>
                        @endif

                        @if (count($inningBlock['bottom']))
                            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 mt-3">
                                ▼ {{ $game->homeTeam->short_name ?? $game->homeTeam->name }}
                                <span class="text-slate-500 font-normal normal-case tracking-normal">({{ __('Local') }})</span>
                            </h4>
                            <div class="space-y-0.5">
                                @foreach ($inningBlock['bottom'] as $play)
                                    @include('public.games._play-line', ['play' => $play])
                                @endforeach
                            </div>
                        @endif
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
        // Auto-refresh de la pagina cada 10 segundos para mantener el marcador actualizado.
        // (Usamos location.reload en vez de fetch+textContent para evitar conflictos con
        //  Alpine.js que carga el bundle de Breeze app.js.)
        setTimeout(() => location.reload(), 10000);
    </script>

</body>
</html>

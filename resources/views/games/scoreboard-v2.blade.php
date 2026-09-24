<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                    Scoreboard <span class="text-wv-accent">v2</span> · {{ $game->homeTeam->name }} <span class="text-wv-text-secondary">vs</span> {{ $game->awayTeam->name }}
                </h2>
                <p class="text-sm text-wv-text-secondary mt-1">
                    Vista paralela experimental · {{ $game->category->name ?? '' }} · {{ $game->stadium->name ?? '' }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('games.scoreboard', $game) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">
                    ← {{ __('Scoreboard clasico') }}
                </a>
                <a href="{{ route('games.show', $game) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">
                    {{ __('Detalle') }}
                </a>
            </div>
        </div>
    </x-slot>

    <style>
        :root {
            --sb-bg: #0b0f1a;
            --sb-bg-soft: #131826;
            --sb-card: #1a2030;
            --sb-border: #2a3247;
            --sb-text: #f1f5f9;
            --sb-text-dim: #94a3b8;
            --sb-accent: #10b981;
            --sb-accent-dim: #064e3b;
            --sb-warn: #f59e0b;
            --sb-alert: #ef4444;
            --sb-info: #3b82f6;
        }
        .sb-shell {
            background: radial-gradient(circle at 20% 0%, #15203a 0%, var(--sb-bg) 60%);
            color: var(--sb-text);
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 30px 60px -20px rgba(0,0,0,0.55);
            border: 1px solid var(--sb-border);
        }
        .sb-card {
            background: var(--sb-card);
            border: 1px solid var(--sb-border);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            transition: transform .25s ease, border-color .25s ease;
        }
        .sb-card.is-batting {
            border-color: var(--sb-accent);
            box-shadow: 0 0 0 1px var(--sb-accent), 0 0 28px -10px var(--sb-accent);
            transform: translateY(-2px);
        }
        .sb-card.is-batting .sb-batting-tag {
            opacity: 1;
        }
        .sb-batting-tag {
            opacity: 0;
            transition: opacity .25s ease;
        }
        .sb-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .65rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--sb-border);
            font-size: .72rem;
            color: var(--sb-text-dim);
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 600;
        }
        .sb-pill.accent { color: var(--sb-accent); border-color: rgba(16,185,129,0.35); background: rgba(16,185,129,0.1); }
        .sb-pill.warn { color: var(--sb-warn); border-color: rgba(245,158,11,0.35); background: rgba(245,158,11,0.1); }
        .sb-score {
            font-family: 'Inter', system-ui, sans-serif;
            font-variant-numeric: tabular-nums;
            font-weight: 800;
            font-size: 3.25rem;
            line-height: 1;
            color: var(--sb-text);
        }
        .sb-inning-line {
            display: grid;
            grid-template-columns: 1fr repeat({{ max(9, (int) ($state['inning'] ?? 1)) }}, minmax(2rem, 1fr)) 1fr;
            gap: .35rem;
            align-items: center;
            background: var(--sb-bg-soft);
            border: 1px solid var(--sb-border);
            border-radius: .75rem;
            padding: .65rem .9rem;
        }
        .sb-inning-line > div {
            text-align: center;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            font-size: .95rem;
            color: var(--sb-text-dim);
            padding: .25rem 0;
            border-radius: .35rem;
        }
        .sb-inning-line > div.is-active {
            background: var(--sb-accent);
            color: #02190f;
            font-weight: 800;
            box-shadow: 0 0 18px -4px var(--sb-accent);
        }
        .sb-inning-line > div.label {
            color: var(--sb-text-dim);
            font-weight: 700;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            background: transparent;
        }
        .sb-inning-line > div.is-r { color: var(--sb-warn); }
        /* Diamond */
        .sb-diamond-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            background: var(--sb-bg-soft);
            border-radius: 1rem;
            border: 1px solid var(--sb-border);
            padding: 1rem;
        }
        .sb-diamond-wrap svg {
            width: 100%;
            height: 100%;
        }
        .sb-diamond-base {
            fill: rgba(255,255,255,0.04);
            stroke: var(--sb-border);
            stroke-width: 1.2;
            transition: fill .3s ease;
        }
        .sb-diamond-base.is-on {
            fill: var(--sb-accent);
            stroke: var(--sb-accent);
        }
        .sb-diamond-base.is-on + text {
            fill: #02190f;
            font-weight: 800;
        }
        .sb-diamond-base text { fill: var(--sb-text-dim); font-size: 10px; font-weight: 600; }

        /* Player card */
        .sb-player {
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .sb-player .avatar {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            background: linear-gradient(135deg, #1f2937, #0f172a);
            color: var(--sb-text);
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--sb-border);
            flex-shrink: 0;
            text-transform: uppercase;
        }
        .sb-player .meta { line-height: 1.2; min-width: 0; }
        .sb-player .name { font-weight: 700; color: var(--sb-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-player .role { font-size: .72rem; color: var(--sb-text-dim); text-transform: uppercase; letter-spacing: .05em; }

        /* Stats grid */
        .sb-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: .35rem;
            margin-top: .85rem;
        }
        .sb-stat {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--sb-border);
            border-radius: .5rem;
            padding: .45rem .6rem;
        }
        .sb-stat .v { font-weight: 800; font-variant-numeric: tabular-nums; font-size: 1.1rem; }
        .sb-stat .l { font-size: .65rem; color: var(--sb-text-dim); text-transform: uppercase; letter-spacing: .08em; }

        .sb-meter {
            position: relative;
            background: rgba(255,255,255,0.04);
            border-radius: 999px;
            overflow: hidden;
            height: 6px;
        }
        .sb-meter > span { display: block; height: 100%; background: linear-gradient(90deg, var(--sb-accent), var(--sb-info)); }

        .sb-zone-summary {
            display: flex; gap: .5rem; flex-wrap: wrap;
        }
    </style>

    @php
        $inningCount = max(9, (int) ($state['inning'] ?? 1));
        $lineScore = $score['line'] ?? [];
        $homeRuns = (int) ($score['home'] ?? 0);
        $awayRuns = (int) ($score['away'] ?? 0);
        $hitsH = (int) ($score['home_hits'] ?? 0);
        $hitsA = (int) ($score['away_hits'] ?? 0);
        $errH = (int) ($score['home_errors'] ?? 0);
        $errA = (int) ($score['away_errors'] ?? 0);
        $isHomeBatting = ($state['half'] ?? 'top') === 'bottom';
        $onFirst = !empty($runners['first']);
        $onSecond = !empty($runners['second']);
        $onThird = !empty($runners['third']);
        $outs = (int) ($state['outs'] ?? 0);
        $balls = (int) ($state['balls'] ?? 0);
        $strikes = (int) ($state['strikes'] ?? 0);
        $inningNum = (int) ($state['inning'] ?? 1);
        $halfLabel = ($state['half'] ?? 'top') === 'top' ? 'TOP' : 'BOTTOM';
        $homeShort = $game->homeTeam->short_name ?? $game->homeTeam->name;
        $awayShort = $game->awayTeam->short_name ?? $game->awayTeam->name;
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <div class="sb-shell">

                {{-- Top: inning line score --}}
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="sb-pill accent">Live</span>
                        <span class="sb-pill warn">{{ $halfLabel }} · Inning {{ $inningNum }}</span>
                        <span class="sb-pill">{{ $outs }} {{ $outs === 1 ? 'out' : 'outs' }}</span>
                        <span class="sb-pill">{{ $balls }}-{{ $strikes }}</span>
                        <span class="sb-pill" style="margin-left:auto;">{{ $game->category->name ?? '—' }} · {{ $game->stadium->name ?? '—' }}</span>
                    </div>
                    <div class="sb-inning-line">
                        <div class="label">{{ $awayShort }}</div>
                        @for ($i = 1; $i <= $inningCount; $i++)
                            @php
                                $cellAway = $lineScore[$i]['away'] ?? null;
                                $cellHome = $lineScore[$i]['home'] ?? null;
                                $isCurrent = $i === $inningNum && !($lineScore[$i]['final'] ?? false);
                                $isFinal = ($lineScore[$i]['final'] ?? false);
                            @endphp
                            <div class="{{ $isCurrent ? 'is-active' : '' }} {{ $isFinal ? 'is-r' : '' }}" title="Inning {{ $i }}">
                                {{ $isFinal ? (string)($cellAway ?? 0) : ($isCurrent ? '●' : '·') }}
                            </div>
                        @endfor
                        <div class="label">R</div>
                    </div>
                </div>

                {{-- Team cards + Diamond --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

                    {{-- Away team --}}
                    <div class="sb-card {{ !$isHomeBatting ? 'is-batting' : '' }}">
                        <div class="flex justify-between items-center mb-2">
                            <span class="sb-pill">{{ $isHomeBatting ? 'DEF' : 'BAT' }}</span>
                            <span class="sb-batting-tag sb-pill accent">▶ Bateando</span>
                        </div>
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-1">{{ $awayShort }} · Visitante</div>
                        <div class="flex items-baseline gap-3 mb-3">
                            <span class="sb-score">{{ $awayRuns }}</span>
                            <span class="text-sm text-wv-text-secondary">Carreras</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-wv-text-secondary">
                            <div>Hits: <strong class="text-wv-text">{{ $hitsA }}</strong></div>
                            <div>Errores: <strong class="text-wv-text">{{ $errA }}</strong></div>
                        </div>
                    </div>

                    {{-- Diamond (center) --}}
                    <div class="sb-card flex flex-col">
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-2 text-center">Bases</div>
                        <div class="sb-diamond-wrap">
                            <svg viewBox="0 0 120 120" preserveAspectRatio="xMidYMid meet">
                                {{-- Diamond shape: home at bottom, 1B right, 2B top, 3B left --}}
                                @php
                                    $second = ['cx' => 60, 'cy' => 22];
                                    $first  = ['cx' => 102, 'cy' => 60];
                                    $third  = ['cx' => 18, 'cy' => 60];
                                    $home   = ['cx' => 60, 'cy' => 98];
                                @endphp
                                <line x1="{{ $second['cx'] }}" y1="{{ $second['cy'] }}" x2="{{ $first['cx'] }}" y2="{{ $first['cy'] }}" stroke="#2a3247" stroke-width="1.2"/>
                                <line x1="{{ $first['cx'] }}" y1="{{ $first['cy'] }}" x2="{{ $home['cx'] }}" y2="{{ $home['cy'] }}" stroke="#2a3247" stroke-width="1.2"/>
                                <line x1="{{ $home['cx'] }}" y1="{{ $home['cy'] }}" x2="{{ $third['cx'] }}" y2="{{ $third['cy'] }}" stroke="#2a3247" stroke-width="1.2"/>
                                <line x1="{{ $third['cx'] }}" y1="{{ $third['cy'] }}" x2="{{ $second['cx'] }}" y2="{{ $second['cy'] }}" stroke="#2a3247" stroke-width="1.2"/>

                                <g>
                                    <circle class="sb-diamond-base {{ $onSecond ? 'is-on' : '' }}" cx="{{ $second['cx'] }}" cy="{{ $second['cy'] }}" r="10"/>
                                    <text x="{{ $second['cx'] }}" y="{{ $second['cy'] + 4 }}" text-anchor="middle">2B</text>
                                </g>
                                <g>
                                    <circle class="sb-diamond-base {{ $onFirst ? 'is-on' : '' }}" cx="{{ $first['cx'] }}" cy="{{ $first['cy'] }}" r="10"/>
                                    <text x="{{ $first['cx'] }}" y="{{ $first['cy'] + 4 }}" text-anchor="middle">1B</text>
                                </g>
                                <g>
                                    <circle class="sb-diamond-base {{ $onThird ? 'is-on' : '' }}" cx="{{ $third['cx'] }}" cy="{{ $third['cy'] }}" r="10"/>
                                    <text x="{{ $third['cx'] }}" y="{{ $third['cy'] + 4 }}" text-anchor="middle">3B</text>
                                </g>
                                <g>
                                    <circle class="sb-diamond-base" cx="{{ $home['cx'] }}" cy="{{ $home['cy'] }}" r="10"/>
                                    <text x="{{ $home['cx'] }}" y="{{ $home['cy'] + 4 }}" text-anchor="middle">H</text>
                                </g>
                            </svg>
                        </div>
                        <div class="flex justify-center gap-1.5 mt-2">
                            @for ($i = 0; $i < 3; $i++)
                                <span class="inline-block w-3 h-3 rounded-full border" style="background: {{ $i < $outs ? '#ef4444' : 'transparent' }}; border-color: #ef4444;"></span>
                            @endfor
                        </div>
                    </div>

                    {{-- Home team --}}
                    <div class="sb-card {{ $isHomeBatting ? 'is-batting' : '' }}">
                        <div class="flex justify-between items-center mb-2">
                            <span class="sb-pill">{{ $isHomeBatting ? 'BAT' : 'DEF' }}</span>
                            <span class="sb-batting-tag sb-pill accent">▶ Bateando</span>
                        </div>
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-1">{{ $homeShort }} · Local</div>
                        <div class="flex items-baseline gap-3 mb-3">
                            <span class="sb-score">{{ $homeRuns }}</span>
                            <span class="text-sm text-wv-text-secondary">Carreras</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-wv-text-secondary">
                            <div>Hits: <strong class="text-wv-text">{{ $hitsH }}</strong></div>
                            <div>Errores: <strong class="text-wv-text">{{ $errH }}</strong></div>
                        </div>
                    </div>
                </div>

                {{-- Pitcher + Batter --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">

                    {{-- Pitcher --}}
                    <div class="sb-card">
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-3">Pitcher</div>
                        @if ($pitcher)
                            <div class="sb-player mb-2">
                                <span class="avatar">{{ mb_strtoupper(mb_substr($pitcher->first_name ?? '', 0, 1) . mb_substr($pitcher->last_name ?? '', 0, 1)) }}</span>
                                <div class="meta">
                                    <div class="name">{{ $pitcher->full_name }}</div>
                                    <div class="role">#{{ $pitcher->number ?? '—' }} · {{ $pitcher->position ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="sb-stats">
                                <div class="sb-stat"><div class="v">{{ $pitcherStats['pitches'] ?? 0 }}</div><div class="l">Pitches</div></div>
                                <div class="sb-stat"><div class="v">{{ $pitcherStats['strikes'] ?? 0 }}</div><div class="l">Strikes</div></div>
                                <div class="sb-stat"><div class="v">{{ $pitcherStats['balls'] ?? 0 }}</div><div class="l">Balls</div></div>
                                <div class="sb-stat"><div class="v">{{ $pitcherStats['strikeouts'] ?? 0 }}</div><div class="l">K</div></div>
                            </div>
                            @php
                                $pitchesTotal = max(1, (int)($pitcherStats['pitches'] ?? 1));
                                $strikePct = round(((int)($pitcherStats['strikes'] ?? 0)) / $pitchesTotal * 100);
                            @endphp
                            <div class="mt-3">
                                <div class="text-xs text-wv-text-secondary mb-1">Strike %: {{ $strikePct }}%</div>
                                <div class="sb-meter"><span style="width: {{ $strikePct }}%"></span></div>
                            </div>
                        @else
                            <div class="text-sm text-wv-text-secondary italic">Sin pitcher asignado</div>
                        @endif
                    </div>

                    {{-- Batter + on deck --}}
                    <div class="sb-card">
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-3">Bateador</div>
                        @if ($batter)
                            <div class="sb-player mb-2">
                                <span class="avatar">{{ mb_strtoupper(mb_substr($batter->first_name ?? '', 0, 1) . mb_substr($batter->last_name ?? '', 0, 1)) }}</span>
                                <div class="meta">
                                    <div class="name">{{ $batter->full_name }}</div>
                                    <div class="role">#{{ $batter->number ?? '—' }} · {{ $batter->position ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="sb-stats">
                                <div class="sb-stat"><div class="v">{{ $batterStats['at_bats'] ?? 0 }}</div><div class="l">AB</div></div>
                                <div class="sb-stat"><div class="v">{{ $batterStats['hits'] ?? 0 }}</div><div class="l">H</div></div>
                                <div class="sb-stat"><div class="v">{{ $batterStats['walks'] ?? 0 }}</div><div class="l">BB</div></div>
                                <div class="sb-stat"><div class="v">{{ number_format((float)($batterStats['avg'] ?? 0), 3, '.', '') }}</div><div class="l">AVG</div></div>
                            </div>
                            <div class="mt-3 text-xs text-wv-text-secondary">
                                <span class="sb-pill">Count {{ $balls }}-{{ $strikes }}</span>
                                @if ($onDeck)
                                    <span class="sb-pill" style="margin-left:.35rem;">On deck: {{ $onDeck->full_name }}</span>
                                @endif
                            </div>
                        @else
                            <div class="text-sm text-wv-text-secondary italic">Sin bateador en turno</div>
                        @endif
                    </div>
                </div>

                <div class="text-center text-xs text-wv-text-secondary mt-4">
                    Vista paralela (scoreboard-v2). Mismos datos que el scoreboard clásico; diseño experimental.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

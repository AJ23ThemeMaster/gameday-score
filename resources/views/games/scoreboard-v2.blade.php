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
            grid-template-columns: 1fr repeat(9, minmax(2rem, 1fr)) 1fr;
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

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <div class="sb-shell"
                 x-data="scoreboardV2App({
                    ...@js($initialState),
                    homeShort: @js($game->homeTeam->short_name ?? $game->homeTeam->name),
                    awayShort: @js($game->awayTeam->short_name ?? $game->awayTeam->name),
                    categoryName: @js($game->category->name ?? ''),
                    stadiumName: @js($game->stadium->name ?? ''),
                    pitchUrl: @js(route('games.plays.pitch', $game)),
                    endInningUrl: @js(route('games.plays.end-inning', $game)),
                    endGameUrl: @js(route('games.plays.end-game', $game)),
                    pollUrl: @js(route('games.scoreboard.poll', $game)),
                    csrf: @js(csrf_token()),
                 })">

                {{-- Top: inning line score --}}
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="sb-pill accent">Live</span>
                        <span class="sb-pill warn" x-text="halfLabel() + ' · Inning ' + inning"></span>
                        <span class="sb-pill" x-text="outsLabel()"></span>
                        <span class="sb-pill" x-text="balls + '-' + strikes"></span>
                        <span class="sb-pill" style="margin-left:auto;" x-text="categoryStadium()"></span>
                    </div>
                    <div class="sb-inning-line" :style="inningLineStyle()">
                        <div class="label" x-text="awayShort"></div>
                        <template x-for="i in lineInnings()" :key="i">
                            <div :class="lineCellClass(i)" :title="'Inning ' + i" x-text="lineCellText(i)"></div>
                        </template>
                        <div class="label">R</div>
                    </div>
                </div>

                {{-- Team cards + Diamond --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

                    {{-- Away team --}}
                    <div class="sb-card" :class="{ 'is-batting': !isHomeBatting() }">
                        <div class="flex justify-between items-center mb-2">
                            <span class="sb-pill" x-text="isHomeBatting() ? 'DEF' : 'BAT'"></span>
                            <span class="sb-batting-tag sb-pill accent">▶ Bateando</span>
                        </div>
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-1" x-text="awayShort + ' · Visitante'"></div>
                        <div class="flex items-baseline gap-3 mb-3">
                            <span class="sb-score" x-text="awayRuns"></span>
                            <span class="text-sm text-wv-text-secondary">Carreras</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-wv-text-secondary">
                            <div>Hits: <strong class="text-wv-text" x-text="awayHits"></strong></div>
                            <div>Errores: <strong class="text-wv-text" x-text="awayErrors"></strong></div>
                        </div>
                    </div>

                    {{-- Diamond (center) --}}
                    <div class="sb-card flex flex-col">
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-2 text-center">Bases</div>
                        <div class="sb-diamond-wrap">
                            <svg viewBox="0 0 120 120" preserveAspectRatio="xMidYMid meet">
                                <line x1="60" y1="22" x2="102" y2="60" stroke="#2a3247" stroke-width="1.2"/>
                                <line x1="102" y1="60" x2="60" y2="98" stroke="#2a3247" stroke-width="1.2"/>
                                <line x1="60" y1="98" x2="18" y2="60" stroke="#2a3247" stroke-width="1.2"/>
                                <line x1="18" y1="60" x2="60" y2="22" stroke="#2a3247" stroke-width="1.2"/>

                                <g>
                                    <circle :class="onSecond() ? 'sb-diamond-base is-on' : 'sb-diamond-base'" cx="60" cy="22" r="10"/>
                                    <text x="60" y="26" text-anchor="middle">2B</text>
                                </g>
                                <g>
                                    <circle :class="onFirst() ? 'sb-diamond-base is-on' : 'sb-diamond-base'" cx="102" cy="60" r="10"/>
                                    <text x="102" y="64" text-anchor="middle">1B</text>
                                </g>
                                <g>
                                    <circle :class="onThird() ? 'sb-diamond-base is-on' : 'sb-diamond-base'" cx="18" cy="60" r="10"/>
                                    <text x="18" y="64" text-anchor="middle">3B</text>
                                </g>
                                <g>
                                    <circle class="sb-diamond-base" cx="60" cy="98" r="10"/>
                                    <text x="60" y="102" text-anchor="middle">H</text>
                                </g>
                            </svg>
                        </div>
                        <div class="flex justify-center gap-1.5 mt-2">
                            <template x-for="i in [0,1,2]" :key="i">
                                <span class="inline-block w-3 h-3 rounded-full border" :style="outsDotStyle(i)"></span>
                            </template>
                        </div>
                    </div>

                    {{-- Home team --}}
                    <div class="sb-card" :class="{ 'is-batting': isHomeBatting() }">
                        <div class="flex justify-between items-center mb-2">
                            <span class="sb-pill" x-text="isHomeBatting() ? 'BAT' : 'DEF'"></span>
                            <span class="sb-batting-tag sb-pill accent">▶ Bateando</span>
                        </div>
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-1" x-text="homeShort + ' · Local'"></div>
                        <div class="flex items-baseline gap-3 mb-3">
                            <span class="sb-score" x-text="homeRuns"></span>
                            <span class="text-sm text-wv-text-secondary">Carreras</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-wv-text-secondary">
                            <div>Hits: <strong class="text-wv-text" x-text="homeHits"></strong></div>
                            <div>Errores: <strong class="text-wv-text" x-text="homeErrors"></strong></div>
                        </div>
                    </div>
                </div>

                {{-- Pitcher + Batter --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">

                    {{-- Pitcher --}}
                    <div class="sb-card">
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-3">Pitcher</div>
                        <template x-if="pitcher">
                            <div>
                                <div class="sb-player mb-2">
                                    <span class="avatar" x-text="initials(pitcher.name)"></span>
                                    <div class="meta">
                                        <div class="name" x-text="pitcher.name"></div>
                                        <div class="role">
                                            <span x-text="'#' + (pitcher.number ?? '—')"></span>
                                            <span> · </span>
                                            <span x-text="pitcher.position ?? '—'"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="sb-stats">
                                    <div class="sb-stat"><div class="v" x-text="pitcherStats.pitches ?? 0"></div><div class="l">Pitches</div></div>
                                    <div class="sb-stat"><div class="v" x-text="pitcherStats.strikes ?? 0"></div><div class="l">Strikes</div></div>
                                    <div class="sb-stat"><div class="v" x-text="pitcherStats.balls ?? 0"></div><div class="l">Balls</div></div>
                                    <div class="sb-stat"><div class="v" x-text="pitcherStats.strikeouts ?? 0"></div><div class="l">K</div></div>
                                </div>
                                <div class="mt-3">
                                    <div class="text-xs text-wv-text-secondary mb-1" x-text="'Strike %: ' + strikePct() + '%'"></div>
                                    <div class="sb-meter"><span :style="'width: ' + strikePct() + '%'"></span></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!pitcher">
                            <div class="text-sm text-wv-text-secondary italic">Sin pitcher asignado</div>
                        </template>
                    </div>

                    {{-- Batter + on deck --}}
                    <div class="sb-card">
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mb-3">Bateador</div>
                        <template x-if="batter">
                            <div>
                                <div class="sb-player mb-2">
                                    <span class="avatar" x-text="initials(batter.name)"></span>
                                    <div class="meta">
                                        <div class="name" x-text="batter.name"></div>
                                        <div class="role">
                                            <span x-text="'#' + (batter.number ?? '—')"></span>
                                            <span> · </span>
                                            <span x-text="batter.position ?? '—'"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="sb-stats">
                                    <div class="sb-stat"><div class="v" x-text="batterStats.at_bats ?? 0"></div><div class="l">AB</div></div>
                                    <div class="sb-stat"><div class="v" x-text="batterStats.hits ?? 0"></div><div class="l">H</div></div>
                                    <div class="sb-stat"><div class="v" x-text="batterStats.walks ?? 0"></div><div class="l">BB</div></div>
                                    <div class="sb-stat"><div class="v" x-text="fmtAvg(batterStats.avg)"></div><div class="l">AVG</div></div>
                                </div>
                                <div class="mt-3 text-xs text-wv-text-secondary">
                                    <span class="sb-pill" x-text="'Count ' + balls + '-' + strikes"></span>
                                    <template x-if="onDeck">
                                        <span class="sb-pill" style="margin-left:.35rem;" x-text="'On deck: ' + onDeck.name"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="!batter">
                            <div class="text-sm text-wv-text-secondary italic">Sin bateador en turno</div>
                        </template>
                    </div>
                </div>

                {{-- Actions: same endpoints as the base scoreboard, condensed panel. --}}
                <div class="mt-4">

                    {{-- Tabs --}}
                    <div class="flex gap-1 border-b border-wv-border mb-3">
                        <button type="button" @click="tab='pitch'"
                                :class="tab==='pitch' ? 'border-wv-accent text-wv-accent' : 'border-transparent text-wv-text-secondary hover:text-wv-text'"
                                class="px-4 py-2 text-sm font-bold border-b-2 transition">
                            {{ __('Pitcheo') }}
                        </button>
                        <button type="button" @click="tab='hit'"
                                :class="tab==='hit' ? 'border-wv-accent text-wv-accent' : 'border-transparent text-wv-text-secondary hover:text-wv-text'"
                                class="px-4 py-2 text-sm font-bold border-b-2 transition">
                            {{ __('Bateo') }}
                        </button>
                        <button type="button" @click="tab='extra'"
                                :class="tab==='extra' ? 'border-wv-accent text-wv-accent' : 'border-transparent text-wv-text-secondary hover:text-wv-text'"
                                class="px-4 py-2 text-sm font-bold border-b-2 transition">
                            {{ __('Extras') }}
                        </button>
                    </div>

                    {{-- PITCH tab --}}
                    <div x-show="tab==='pitch' && !isFinalized && gameStatus==='in_progress'" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button" @click="sendPitch('ball')" :disabled="busy"
                                class="py-4 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-lg font-black rounded-card transition">
                            {{ __('Ball') }}
                        </button>
                        <button type="button" @click="openStrikeModal()" :disabled="busy"
                                class="py-4 bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-lg font-black rounded-card transition">
                            {{ __('Strike') }}
                        </button>
                        <button type="button" @click="sendPitch('foul')" :disabled="busy"
                                class="py-4 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-lg font-black rounded-card transition">
                            {{ __('Foul') }}
                        </button>
                        <button type="button" @click="openOutStep1()" :disabled="busy"
                                class="py-4 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white text-lg font-black rounded-card transition">
                            {{ __('Out') }}
                        </button>
                    </div>

                    {{-- HIT tab --}}
                    <div x-show="tab==='hit' && !isFinalized && gameStatus==='in_progress'" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <button type="button" @click="openHitModal('single')" :disabled="busy"
                                class="py-4 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-base font-black rounded-card transition">
                            {{ __('Sencillo') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">1B</div>
                        </button>
                        <button type="button" @click="openHitModal('double')" :disabled="busy"
                                class="py-4 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-base font-black rounded-card transition">
                            {{ __('Doble') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">2B</div>
                        </button>
                        <button type="button" @click="openHitModal('triple')" :disabled="busy"
                                class="py-4 bg-violet-500 hover:bg-violet-600 disabled:opacity-50 text-white text-base font-black rounded-card transition">
                            {{ __('Triple') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">3B</div>
                        </button>
                        <button type="button" @click="openHitModal('hr')" :disabled="busy"
                                class="py-4 bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-base font-black rounded-card transition">
                            {{ __('HR') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">Home Run</div>
                        </button>
                        <button type="button" @click="openHitModal('inside_park')" :disabled="busy"
                                class="col-span-2 py-4 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-base font-black rounded-card transition">
                            {{ __('HR de pierna') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">Inside-the-park</div>
                        </button>
                        <button type="button" @click="openBuntModal()" :disabled="busy"
                                class="col-span-3 py-4 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white text-base font-black rounded-card transition">
                            {{ __('Toque de bolas') }}
                            <div class="text-[10px] font-normal opacity-80 mt-0.5">{{ __('Sacrificio o bunt single') }}</div>
                        </button>
                    </div>

                    {{-- EXTRAS tab --}}
                    <div x-show="tab==='extra'">
                        <div x-show="!isFinalized && gameStatus==='in_progress'" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button type="button" @click="sendPitch('balk')" :disabled="busy"
                                    class="py-3 bg-purple-500 hover:bg-purple-600 disabled:opacity-50 text-white text-sm font-bold rounded-card transition">
                                {{ __('Balk') }}
                                <div class="text-[9px] font-normal opacity-80 mt-0.5">{{ __('Corredores avanzan 1 base') }}</div>
                            </button>
                            <button type="button" @click="openEndInningModal()" :disabled="busy"
                                    class="py-3 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white text-sm font-bold rounded-card transition">
                                {{ __('Cerrar inning') }}
                                <div class="text-[9px] font-normal opacity-80 mt-0.5">{{ __('Terminar la media entrada actual') }}</div>
                            </button>
                            <button type="button" @click="openEndGameModal()" :disabled="busy"
                                    class="py-3 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-bold rounded-card transition">
                                {{ __('Finalizar juego') }}
                                <div class="text-[9px] font-normal opacity-80 mt-0.5">{{ __('Cerrar el juego por completo') }}</div>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                            <a href="{{ route('games.box-score', $game) }}"
                               class="py-3 bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold rounded-card transition text-center block">
                                📋 {{ __('Box Score') }}
                            </a>
                            <a href="{{ route('games.roster.index', $game) }}"
                               class="py-3 bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold rounded-card transition text-center block">
                                👥 {{ __('Roster del juego') }}
                            </a>
                            <a href="{{ route('games.scoreboard', $game) }}"
                               class="py-3 bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold rounded-card transition text-center block">
                                ⚙️ {{ __('Scoreboard clasico') }}
                            </a>
                        </div>
                    </div>

                    {{-- ===== MODALES (mismas acciones que el scoreboard base) ===== --}}

                    {{-- Modal: tipo de ponche --}}
                    <div x-show="modal==='strike'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
                         @keydown.escape.window="closeModal()">
                        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                            <div class="bg-rose-600 text-white px-5 py-3 flex items-center justify-between">
                                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Tipo de ponche') }}</h3>
                                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                            </div>
                            <div class="p-5 space-y-3">
                                <button type="button" @click="sendStrike('looking')"
                                        class="w-full py-4 bg-rose-700 hover:bg-rose-800 text-white text-lg font-bold rounded-card transition">
                                    {{ __('Mirando') }}
                                </button>
                                <button type="button" @click="sendStrike('swinging')"
                                        class="w-full py-4 bg-rose-800 hover:bg-rose-900 text-white text-lg font-bold rounded-card transition">
                                    {{ __('Swing') }}
                                </button>
                                <button type="button" @click="sendStrike('foul_tip')"
                                        class="w-full py-4 bg-rose-900 hover:bg-black text-white text-lg font-bold rounded-card transition">
                                    {{ __('Foul Tip') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Modal: tipo de out (paso 1) --}}
                    <div x-show="modal==='out-step1'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
                         @keydown.escape.window="closeModal()">
                        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                            <div class="bg-slate-700 text-white px-5 py-3 flex items-center justify-between">
                                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Tipo de out') }}</h3>
                                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                            </div>
                            <div class="p-5 space-y-3">
                                <button type="button" @click="openOutStep2('fly')"
                                        class="w-full py-4 bg-sky-600 hover:bg-sky-700 text-white text-lg font-bold rounded-card transition">
                                    {{ __('Fly (elevado)') }}
                                </button>
                                <button type="button" @click="openOutStep2('line')"
                                        class="w-full py-4 bg-sky-700 hover:bg-sky-800 text-white text-lg font-bold rounded-card transition">
                                    {{ __('Línea') }}
                                </button>
                                <button type="button" @click="openOutStep2('ground')"
                                        class="w-full py-4 bg-amber-600 hover:bg-amber-700 text-white text-lg font-bold rounded-card transition">
                                    {{ __('Roletazo') }}
                                </button>
                                <button type="button" @click="confirmOut()"
                                        class="w-full py-4 bg-slate-600 hover:bg-slate-700 text-white text-lg font-bold rounded-card transition">
                                    {{ __('De reglamento') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Modal: jugada defensiva (paso 2) --}}
                    <div x-show="modal==='out-step2'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
                         @keydown.escape.window="closeModal()">
                        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" @click.outside="closeModal()">
                            <div class="bg-slate-800 text-white px-5 py-3 flex items-center justify-between">
                                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Jugada defensiva') }}</h3>
                                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                            </div>
                            <div class="p-5">
                                <p class="text-sm text-wv-text-secondary mb-3">{{ __('Toca los fildeadores en el orden que participaron.') }}</p>

                                {{-- Diamante con los 9 fildeadores --}}
                                <div class="relative bg-emerald-700 rounded-card mx-auto" style="width: 320px; height: 320px;">
                                    <div class="absolute rounded-full bg-amber-200/30" style="top: 90px; left: 40px; right: 40px; bottom: 90px;"></div>
                                    <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 70px; left: 50%; transform: translateX(-50%) rotate(45deg);" title="2da base"></div>
                                    <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 50%; right: 8px; transform: translateY(-50%) rotate(45deg);" title="1ra base"></div>
                                    <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="bottom: 8px; left: 50%; transform: translateX(-50%) rotate(45deg);" title="Home"></div>
                                    <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 50%; left: 8px; transform: translateY(-50%) rotate(45deg);" title="3ra base"></div>
                                    <div class="absolute flex items-center justify-center text-xs font-black text-amber-900 bg-amber-200/90 rounded-full" style="top: 138px; left: 50%; transform: translate(-50%, -50%); width: 44px; height: 44px;">P</div>

                                    <button type="button" @click="addFielder('LF')" style="top: 14px; left: 16px;"
                                            :class="isFielderSelected('LF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                            class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">LF</button>
                                    <button type="button" @click="addFielder('CF')" style="top: 10px; left: 50%; transform: translateX(-50%);"
                                            :class="isFielderSelected('CF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                            class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">CF</button>
                                    <button type="button" @click="addFielder('RF')" style="top: 14px; right: 16px;"
                                            :class="isFielderSelected('RF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                            class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">RF</button>
                                    <button type="button" @click="addFielder('SS')" style="top: 102px; left: 50px;"
                                            :class="isFielderSelected('SS') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                            class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">SS</button>
                                    <button type="button" @click="addFielder('2B')" style="top: 102px; right: 50px;"
                                            :class="isFielderSelected('2B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                            class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">2B</button>
                                    <button type="button" @click="addFielder('3B')" style="top: 178px; left: 28px;"
                                            :class="isFielderSelected('3B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                            class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">3B</button>
                                    <button type="button" @click="addFielder('1B')" style="top: 178px; right: 28px;"
                                            :class="isFielderSelected('1B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                            class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">1B</button>
                                    <button type="button" @click="addFielder('C')" style="bottom: 56px; left: 50%; transform: translateX(-50%);"
                                            :class="isFielderSelected('C') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'"
                                            class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">C</button>
                                </div>

                                <div class="mt-4 p-3 bg-wv-bg rounded-card min-h-[60px]">
                                    <div class="text-xs text-wv-text-secondary uppercase font-semibold mb-1">{{ __('Secuencia') }}</div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <template x-for="(f, i) in defensiveSequence" :key="i">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-200 text-amber-900 rounded-full text-sm font-bold">
                                                <span x-text="f"></span>
                                                <button type="button" @click="removeFielder(i)" class="text-amber-700 hover:text-red-600 font-black">&times;</button>
                                            </span>
                                        </template>
                                        <span x-show="defensiveSequence.length === 0" class="text-sm text-wv-text-secondary italic">{{ __('Selecciona los fildeadores') }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 flex gap-2">
                                    <button type="button" @click="closeModal()"
                                            class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">
                                        {{ __('Cancelar') }}
                                    </button>
                                    <button type="button" @click="confirmOut()" :disabled="defensiveSequence.length === 0"
                                            class="flex-1 py-3 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white font-bold rounded-card">
                                        {{ __('Registrar out') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal: confirmacion de hit --}}
                    <div x-show="modal==='hit'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
                         @keydown.escape.window="closeModal()">
                        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                            <div class="bg-emerald-600 text-white px-5 py-3 flex items-center justify-between">
                                <h3 class="text-lg font-black uppercase tracking-wider" x-text="hitConfig.label"></h3>
                                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                            </div>
                            <div class="p-5 space-y-4">
                                <p class="text-sm text-wv-text-secondary" x-text="hitConfig.description"></p>
                                <div class="bg-wv-bg rounded-card p-3 text-sm">
                                    <div class="text-xs text-wv-text-secondary uppercase font-semibold mb-1">{{ __('Resultado esperado') }}</div>
                                    <div class="text-wv-text" x-text="hitConfig.preview"></div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" @click="closeModal()"
                                            class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">
                                        {{ __('Cancelar') }}
                                    </button>
                                    <button type="button" @click="confirmHit()"
                                            class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-card">
                                        {{ __('Registrar hit') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal: tipo de bunt --}}
                    <div x-show="modal==='bunt'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
                         @keydown.escape.window="closeModal()">
                        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                            <div class="bg-amber-600 text-white px-5 py-3 flex items-center justify-between">
                                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Toque de bolas') }}</h3>
                                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                            </div>
                            <div class="p-5 space-y-3">
                                <p class="text-sm text-wv-text-secondary">{{ __('Elige el resultado del toque:') }}</p>
                                <button type="button" @click="sendBunt('sacrifice')"
                                        class="w-full py-4 bg-amber-700 hover:bg-amber-800 text-white text-lg font-bold rounded-card transition">
                                    {{ __('Toque de sacrificio') }}
                                    <div class="text-xs font-normal opacity-80 mt-1">{{ __('Bateador out, corredores avanzan') }}</div>
                                </button>
                                <button type="button" @click="sendBunt('bunt_single')"
                                        class="w-full py-4 bg-emerald-700 hover:bg-emerald-800 text-white text-lg font-bold rounded-card transition">
                                    {{ __('Bunt single') }}
                                    <div class="text-xs font-normal opacity-80 mt-1">{{ __('Bateador a 1B, corredores avanzan') }}</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Modal: confirmar cerrar inning --}}
                    <div x-show="modal==='end-inning'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
                         @keydown.escape.window="closeModal()">
                        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                            <div class="bg-orange-600 text-white px-5 py-3 flex items-center justify-between">
                                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Cerrar inning') }}</h3>
                                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                            </div>
                            <div class="p-5 space-y-3">
                                <p class="text-sm text-wv-text-secondary">
                                    {{ __('Vas a cerrar la entrada actual antes de los 3 outs. Esta accion no se puede deshacer.') }}
                                </p>
                                <div class="flex gap-2">
                                    <button type="button" @click="closeModal()"
                                            class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">
                                        {{ __('Cancelar') }}
                                    </button>
                                    <button type="button" @click="confirmEndInning()"
                                            class="flex-1 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-card">
                                        {{ __('Cerrar inning') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal: confirmar finalizar juego --}}
                    <div x-show="modal==='end-game'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
                         @keydown.escape.window="closeModal()">
                        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
                            <div class="bg-red-700 text-white px-5 py-3 flex items-center justify-between">
                                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Finalizar juego') }}</h3>
                                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
                            </div>
                            <div class="p-5 space-y-3">
                                <p class="text-sm text-wv-text-secondary">
                                    {{ __('Vas a finalizar el juego por completo. Esta accion no se puede deshacer.') }}
                                </p>
                                <div class="flex gap-2">
                                    <button type="button" @click="closeModal()"
                                            class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">
                                        {{ __('Cancelar') }}
                                    </button>
                                    <button type="button" @click="confirmEndGame()"
                                            class="flex-1 py-3 bg-red-700 hover:bg-red-800 text-white font-bold rounded-card">
                                        {{ __('Finalizar') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Toast feedback --}}
                    <div class="fixed top-4 right-4 z-[60] space-y-2 pointer-events-none" x-data="toastStack()" @toast.window="show($event.detail.message, $event.detail.level || 'success', $event.detail.timeout)">
                        <template x-for="t in items" :key="t.id">
                            <div x-show="t.visible" x-transition
                                 :class="t.level === 'error' ? 'bg-red-600 text-white' : 'bg-emerald-500 text-white'"
                                 class="px-4 py-2 rounded-card shadow-lg text-sm font-semibold pointer-events-auto">
                                <span x-text="t.message"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="text-center text-xs text-wv-text-secondary mt-4">
                    Vista paralela (scoreboard-v2). Mismos datos que el scoreboard clásico; diseño experimental.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
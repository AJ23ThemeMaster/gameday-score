@extends('layouts.clean')

@section('content')
@push('head')
<style>
    :root {
        --sb-bg: #0b0f1a;
        --sb-bg-soft: #131826;
        --sb-card: #1a2030;
        --sb-border: #2a3247;
        --sb-text: #f1f5f9;
        --sb-text-dim: #94a3b8;
        --sb-accent: #10b981;
        --sb-warn: #f59e0b;
        --sb-alert: #ef4444;
    }
    .sb-shell {
        background: radial-gradient(circle at 20% 0%, #15203a 0%, var(--sb-bg) 60%);
        color: var(--sb-text);
        border-radius: 1.25rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 30px 60px -20px rgba(0,0,0,0.55);
        border: 1px solid var(--sb-border);
        max-width: 720px;
        margin: 0 auto;
    }
    .sb-card {
        background: var(--sb-card);
        border: 1px solid var(--sb-border);
        border-radius: 0.9rem;
        padding: 0.75rem 1rem;
    }
    .sb-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .25rem .65rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--sb-border);
        font-size: .7rem;
        color: var(--sb-text-dim);
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 600;
    }
    .sb-pill.accent { color: var(--sb-accent); border-color: rgba(16,185,129,0.4); background: rgba(16,185,129,0.12); }
    .sb-pill.warn { color: var(--sb-warn); border-color: rgba(245,158,11,0.4); background: rgba(245,158,11,0.12); }
    .sb-pill.alert { color: var(--sb-alert); border-color: rgba(239,68,68,0.4); background: rgba(239,68,68,0.12); }

    .sb-score {
        font-variant-numeric: tabular-nums;
        font-weight: 800;
        font-size: 3rem;
        line-height: 1;
        color: var(--sb-text);
    }

    .sb-team-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .4rem;
    }
    .sb-team-zone.is-batting {
        background: linear-gradient(180deg, rgba(16,185,129,0.18), rgba(16,185,129,0.04));
        border-radius: 0.75rem;
        box-shadow: inset 0 0 0 1px rgba(16,185,129,0.5);
        padding: .5rem .25rem;
    }
    .sb-team-logo {
        width: 72px; height: 72px;
        border-radius: 999px;
        object-fit: contain;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--sb-border);
    }
    .sb-team-logo-fallback {
        width: 72px; height: 72px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.06);
        color: var(--sb-text-dim);
        font-size: .65rem;
        border: 1px dashed var(--sb-border);
    }
    .sb-team-name {
        font-weight: 700;
        color: var(--sb-text);
        text-align: center;
        line-height: 1.15;
        font-size: .9rem;
    }
    .sb-team-label {
        font-size: .65rem;
        color: var(--sb-text-dim);
        text-transform: uppercase;
        letter-spacing: .1em;
    }

    /* Player card */
    .sb-player-card {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .5rem .25rem;
    }
    .sb-player-photo {
        width: 44px;
        height: 44px;
        border-radius: 999px;
        object-fit: cover;
        background: rgba(255,255,255,0.05);
        border: 2px solid var(--sb-border);
        flex-shrink: 0;
    }
    .sb-player-initials {
        width: 44px;
        height: 44px;
        border-radius: 999px;
        background: linear-gradient(135deg, #1f2937, #0f172a);
        color: var(--sb-text);
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--sb-border);
        flex-shrink: 0;
        text-transform: uppercase;
        font-size: .9rem;
    }
    .sb-player-meta { line-height: 1.2; min-width: 0; }
    .sb-player-name { font-weight: 700; color: var(--sb-text); }
    .sb-player-role {
        font-size: .65rem;
        color: var(--sb-text-dim);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    /* Count dots */
    .sb-count-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .5rem;
    }
    .sb-count-cell {
        text-align: center;
        padding: .5rem .25rem;
        border-right: 1px solid var(--sb-border);
    }
    .sb-count-cell:last-child { border-right: 0; }
    .sb-count-cell .label {
        font-size: .65rem;
        color: var(--sb-text-dim);
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 600;
        margin-bottom: .35rem;
    }
    .sb-count-dots {
        display: flex;
        justify-content: center;
        gap: .35rem;
    }
    .sb-dot {
        width: .85rem;
        height: .85rem;
        border-radius: 999px;
        border: 1px solid var(--sb-border);
        background: rgba(255,255,255,0.04);
        display: inline-block;
    }
    .sb-dot.is-on.ball { background: #10b981; border-color: #059669; }
    .sb-dot.is-on.strike { background: #f59e0b; border-color: #d97706; }
    .sb-dot.is-on.out { background: #ef4444; border-color: #dc2626; }

    /* Diamond field (matches base) */
    .sb-field {
        background: #15803d;
        background-image: radial-gradient(ellipse at center, #15803d 0%, #14532d 100%);
        border-radius: 0.75rem;
        padding: 1rem 0.75rem;
        position: relative;
    }
    .sb-field-grid {
        position: relative;
        width: 280px;
        max-width: 100%;
        height: 280px;
        margin: 0 auto;
    }
    .sb-base {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .25rem;
    }
    .sb-base-tag {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: .35rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .65rem;
        font-weight: 700;
        border: 2px solid #ffffff;
        background: rgba(236, 252, 232, 0.9);
        color: #047857;
    }
    .sb-base-tag.is-on {
        background: #fcd34d;
        border-color: #f59e0b;
        color: #78350f;
        box-shadow: 0 0 12px rgba(252, 211, 77, 0.6);
    }
    .sb-base-tag span {
        font-size: .55rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .sb-base-tag strong {
        font-size: .85rem;
        font-weight: 900;
        line-height: 1;
    }
    .sb-base-label {
        background: rgba(255,255,255,0.95);
        color: #0f172a;
        border-radius: .25rem;
        padding: .15rem .35rem;
        font-size: .65rem;
        font-weight: 700;
        max-width: 84px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sb-pitcher-mound {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 2.6rem; height: 2.6rem;
        background: #fcd34d;
        border: 2px solid #ffffff;
        border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        color: #78350f;
        font-weight: 900;
        font-size: .8rem;
    }

    /* Action panel buttons */
    .sb-action-btn {
        padding: .85rem 1rem;
        border-radius: .65rem;
        font-size: 1.1rem;
        font-weight: 800;
        color: white;
        text-align: center;
        border: 0;
        cursor: pointer;
        transition: filter .15s ease;
    }
    .sb-action-btn:hover { filter: brightness(1.1); }
    .sb-action-btn:disabled { opacity: .5; cursor: not-allowed; }
    .sb-action-btn.ball   { background: #10b981; }
    .sb-action-btn.strike  { background: #f43f5e; }
    .sb-action-btn.foul    { background: #f59e0b; }
    .sb-action-btn.out     { background: #334155; }

    .sb-tab {
        padding: .5rem 1rem;
        font-weight: 700;
        font-size: .8rem;
        border-bottom: 2px solid transparent;
        color: var(--sb-text-dim);
        text-transform: uppercase;
        letter-spacing: .05em;
        background: transparent;
        border-top: 0; border-left: 0; border-right: 0;
        cursor: pointer;
    }
    .sb-tab.is-active { color: var(--sb-accent); border-bottom-color: var(--sb-accent); }
</style>
@endpush

<div class="py-6">

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

    {{-- Top header: liga / torneo / categoria a la izquierda, 4 acciones a la derecha --}}
    <div class="flex items-center justify-between gap-2 mb-4 flex-wrap">
        {{-- Izquierda: liga · torneo · categoria --}}
        <div class="flex items-center gap-1.5 text-xs flex-wrap" style="color: var(--sb-text-dim);">
            @if ($game->tournament?->league)
                <a href="{{ route('leagues.show', $game->tournament->league) }}"
                   class="inline-flex items-center px-2 py-1 rounded-md bg-wv-surface border border-wv-border hover:border-wv-accent transition"
                   style="color: var(--sb-text);">
                    {{ $game->tournament->league->short_name ?? $game->tournament->league->name }}
                </a>
                <span style="opacity:.5;">/</span>
            @endif
            @if ($game->tournament)
                <a href="{{ route('tournaments.show', $game->tournament) }}"
                   class="inline-flex items-center px-2 py-1 rounded-md bg-wv-surface border border-wv-border hover:border-wv-accent transition"
                   style="color: var(--sb-text);">
                    {{ $game->tournament->name }}
                </a>
                <span style="opacity:.5;">/</span>
            @endif
            <span class="inline-flex items-center px-2 py-1 rounded-md bg-wv-surface border border-wv-border"
                  style="color: var(--sb-text);">
                {{ $game->category->name ?? '' }}
            </span>
        </div>

        {{-- Derecha: 4 acciones con colores del index --}}
        <div class="inline-flex items-center gap-1">
            @if ($game->is_public && $game->public_token)
                <a href="{{ route('games.live.public', $game->public_token) }}"
                   target="_blank" rel="noopener"
                   title="{{ __('En vivo (vista publica)') }}"
                   aria-label="{{ __('En vivo (vista publica)') }}"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-wv-bg border-wv-alert/40 text-wv-alert hover:bg-wv-alert/15 hover:border-wv-alert focus:ring-wv-alert">
                    <span class="material-symbols-outlined" style="font-size:18px;">live_tv</span>
                </a>
            @endif
            <a href="{{ route('games.show', $game) }}"
               title="{{ __('Detalle del juego') }}"
               aria-label="{{ __('Detalle del juego') }}"
               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-wv-bg border-wv-accent/40 text-wv-accent hover:bg-wv-accent/15 hover:border-wv-accent focus:ring-wv-accent">
                <span class="material-symbols-outlined" style="font-size:18px;">visibility</span>
            </a>
            <a href="{{ route('games.roster.index', $game) }}"
               title="{{ __('Roster del juego') }}"
               aria-label="{{ __('Roster del juego') }}"
               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-wv-bg border-wv-success/40 text-wv-success hover:bg-wv-success/15 hover:border-wv-success focus:ring-wv-success">
                <span class="material-symbols-outlined" style="font-size:18px;">groups</span>
            </a>
            <a href="{{ route('games.index') }}"
               title="{{ __('Salir al listado') }}"
               aria-label="{{ __('Salir al listado') }}"
               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-wv-bg border-wv-text-secondary/40 text-wv-text-secondary hover:bg-wv-text-secondary/15 hover:border-wv-text-secondary focus:ring-wv-text-secondary">
                <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
            </a>
        </div>
    </div>

    {{-- HEADER: Local | Score | Visitante (estructura del scoreboard base) --}}
    <div class="grid grid-cols-3 items-center gap-3 border-b-2 border-wv-border py-4">
        {{-- Local --}}
        <div class="sb-team-zone" :class="{ 'is-batting': isHomeBatting() }">
            <template x-if="homeLogoUrl">
                <img :src="homeLogoUrl" class="sb-team-logo" alt="Home logo">
            </template>
            <template x-if="!homeLogoUrl">
                <div class="sb-team-logo-fallback">LOGO</div>
            </template>
            <div class="sb-team-name" x-text="homeShort"></div>
            <div class="sb-team-label">{{ __('Local') }}</div>
        </div>

        {{-- Centro: score + Inning --}}
        <div class="flex flex-col items-center justify-center gap-1">
            <div class="flex items-baseline gap-2 text-3xl sm:text-4xl font-black leading-none" style="color: var(--sb-text);">
                <span data-score="home" x-text="homeRuns"></span>
                <span style="color: var(--sb-text-dim);">-</span>
                <span data-score="away" x-text="awayRuns"></span>
            </div>
            <div class="text-xs" style="color: var(--sb-text-dim);">
                {{ __('Inning') }}
                <strong x-text="inning"></strong>
                <span x-text="half === 'top' ? '▲' : '▼'"></span>
            </div>
            <div class="mt-1 inline-flex items-center gap-1 px-3 py-1 bg-emerald-900 border border-emerald-500 rounded-full text-emerald-200 text-xs font-black uppercase tracking-wider"
                 x-show="isFinalized" x-cloak>
                {{ __('JUEGO FINALIZADO') }}
            </div>
        </div>

        {{-- Visitante --}}
        <div class="sb-team-zone" :class="{ 'is-batting': !isHomeBatting() }">
            <template x-if="awayLogoUrl">
                <img :src="awayLogoUrl" class="sb-team-logo" alt="Away logo">
            </template>
            <template x-if="!awayLogoUrl">
                <div class="sb-team-logo-fallback">LOGO</div>
            </template>
            <div class="sb-team-name" x-text="awayShort"></div>
            <div class="sb-team-label">{{ __('Visitante') }}</div>
        </div>
    </div>

    {{-- Pitcher + Batter cards (con foto) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
        {{-- Pitcher --}}
        <div class="sb-card">
            <div class="text-[10px] uppercase tracking-wider mb-1" style="color: var(--sb-text-dim);">{{ __('Pitcheando') }}</div>
            <template x-if="pitcher">
                <div class="sb-player-card">
                    <template x-if="pitcher.photo_url">
                        <img :src="pitcher.photo_url" class="sb-player-photo" alt="">
                    </template>
                    <template x-if="!pitcher.photo_url">
                        <span class="sb-player-initials" x-text="initials(pitcher.name)"></span>
                    </template>
                    <div class="sb-player-meta">
                        <div class="sb-player-name" x-text="pitcher.name"></div>
                        <div class="sb-player-role">
                            <span x-text="'#' + (pitcher.number ?? '—')"></span>
                            <span> · </span>
                            <span x-text="pitcher.position ?? '—'"></span>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="!pitcher">
                <div class="text-sm italic" style="color: var(--sb-text-dim);">{{ __('Sin lanzador asignado') }}</div>
            </template>
        </div>

        {{-- Batter --}}
        <div class="sb-card">
            <div class="text-[10px] uppercase tracking-wider mb-1" style="color: var(--sb-text-dim);">{{ __('Al bate') }}</div>
            <template x-if="batter">
                <div class="sb-player-card">
                    <template x-if="batter.photo_url">
                        <img :src="batter.photo_url" class="sb-player-photo" alt="">
                    </template>
                    <template x-if="!batter.photo_url">
                        <span class="sb-player-initials" x-text="initials(batter.name)"></span>
                    </template>
                    <div class="sb-player-meta">
                        <div class="sb-player-name">
                            <span x-text="'#' + (batter.number ?? '—')"></span>
                            <span x-text="batter.name"></span>
                        </div>
                        <div class="sb-player-role">
                            <span>AB <span x-text="batterStats.at_bats ?? 0"></span> · H <span x-text="batterStats.hits ?? 0"></span> · AVG <span x-text="fmtAvg(batterStats.avg)"></span> · BB <span x-text="batterStats.walks ?? 0"></span> · K <span x-text="batterStats.strikeouts ?? 0"></span></span>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="!batter">
                <div class="text-sm italic" style="color: var(--sb-text-dim);">{{ __('Sin bateador en turno') }}</div>
            </template>
        </div>
    </div>

    {{-- On deck --}}
    <div class="sb-card mt-3" x-show="onDeck" x-cloak>
        <div class="text-[10px] uppercase tracking-wider mb-1" style="color: var(--sb-text-dim);">{{ __('Prevenido') }}</div>
        <div class="sb-player-card" x-show="onDeck">
            <template x-if="onDeck?.photo_url">
                <img :src="onDeck.photo_url" class="sb-player-photo" alt="">
            </template>
            <template x-if="!onDeck?.photo_url">
                <span class="sb-player-initials" x-text="initials(onDeck?.name)"></span>
            </template>
            <div class="sb-player-meta">
                <div class="sb-player-name">
                    <span x-text="'#' + (onDeck?.number ?? '—')"></span>
                    <span x-text="onDeck?.name"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- DIAMOND: bases con corredores --}}
    <div class="sb-field mt-4" x-show="!isFinalized" x-cloak>
        <div class="sb-field-grid">
            {{-- 2B (arriba) --}}
            <button type="button"
                    @click="openRunnerModal('second')"
                    :disabled="!onSecond() || !isHomeBatting() && isFinalized"
                    :class="onSecond() ? 'sb-base sb-base-btn is-on' : 'sb-base sb-base-btn'"
                    style="top: 0; left: 50%; transform: translateX(-50%); background: transparent; border: 0; padding: 0; cursor: pointer;">
                <div :class="onSecond() ? 'sb-base-tag is-on' : 'sb-base-tag'">
                    <span>2B</span>
                    <strong x-show="onSecond()" x-text="base2?.number"></strong>
                </div>
                <div class="sb-base-label" x-show="onSecond()" x-text="base2?.name"></div>
            </button>
            {{-- 3B (izquierda) --}}
            <button type="button"
                    @click="openRunnerModal('third')"
                    :disabled="!onThird()"
                    :class="onThird() ? 'sb-base sb-base-btn is-on' : 'sb-base sb-base-btn'"
                    style="top: 50%; left: 0; transform: translateY(-50%); background: transparent; border: 0; padding: 0; cursor: pointer;">
                <div :class="onThird() ? 'sb-base-tag is-on' : 'sb-base-tag'">
                    <span>3B</span>
                    <strong x-show="onThird()" x-text="base3?.number"></strong>
                </div>
                <div class="sb-base-label" x-show="onThird()" x-text="base3?.name"></div>
            </button>
            {{-- P (centro) --}}
            <div class="sb-pitcher-mound">P</div>
            {{-- 1B (derecha) --}}
            <button type="button"
                    @click="openRunnerModal('first')"
                    :disabled="!onFirst()"
                    :class="onFirst() ? 'sb-base sb-base-btn is-on' : 'sb-base sb-base-btn'"
                    style="top: 50%; right: 0; transform: translateY(-50%); background: transparent; border: 0; padding: 0; cursor: pointer;">
                <div :class="onFirst() ? 'sb-base-tag is-on' : 'sb-base-tag'">
                    <span>1B</span>
                    <strong x-show="onFirst()" x-text="base1?.number"></strong>
                </div>
                <div class="sb-base-label" x-show="onFirst()" x-text="base1?.name"></div>
            </button>
            {{-- HOME (abajo) --}}
            <div class="sb-base" style="bottom: 0; left: 50%; transform: translateX(-50%);">
                <div class="sb-base-tag">
                    <span>HOME</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Conteo: Bolas / Strikes / Outs (3 columnas, debajo del diamante) --}}
    <div class="sb-count-grid sb-card mt-3">
        <div class="sb-count-cell">
            <div class="label">{{ __('Bolas') }}</div>
            <div class="sb-count-dots" data-balls>
                <template x-for="i in [0,1,2,3]" :key="'b'+i">
                    <span :class="i < balls ? 'sb-dot is-on ball' : 'sb-dot'"></span>
                </template>
            </div>
        </div>
        <div class="sb-count-cell">
            <div class="label">{{ __('Strikes') }}</div>
            <div class="sb-count-dots" data-strikes>
                <template x-for="i in [0,1,2]" :key="'s'+i">
                    <span :class="i < strikes ? 'sb-dot is-on strike' : 'sb-dot'"></span>
                </template>
            </div>
        </div>
        <div class="sb-count-cell">
            <div class="label">{{ __('Outs') }}</div>
            <div class="sb-count-dots" data-outs>
                <template x-for="i in [0,1,2]" :key="'o'+i">
                    <span :class="i < outs ? 'sb-dot is-on out' : 'sb-dot'"></span>
                </template>
            </div>
        </div>
    </div>

    {{-- Action panel + modals --}}
    <div class="mt-4">
        <div class="flex border-b border-wv-border mb-3">
            <button type="button" @click="tab='pitch'"
                    :class="tab==='pitch' ? 'sb-tab is-active' : 'sb-tab'"
                    class="flex-1 text-center">
                {{ __('Pitcheo') }}
            </button>
            <button type="button" @click="tab='hit'"
                    :class="tab==='hit' ? 'sb-tab is-active' : 'sb-tab'"
                    class="flex-1 text-center">
                {{ __('Bateo') }}
            </button>
            <button type="button" @click="tab='extra'"
                    :class="tab==='extra' ? 'sb-tab is-active' : 'sb-tab'"
                    class="flex-1 text-center">
                {{ __('Extras') }}
            </button>
        </div>

        {{-- PITCH --}}
        <div x-show="tab==='pitch' && !isFinalized && gameStatus==='in_progress'" class="grid grid-cols-4 gap-2">
            <button type="button" @click="sendPitch('ball')" :disabled="busy" class="sb-action-btn ball">{{ __('Ball') }}</button>
            <button type="button" @click="openStrikeModal()" :disabled="busy" class="sb-action-btn strike">{{ __('Strike') }}</button>
            <button type="button" @click="sendPitch('foul')" :disabled="busy" class="sb-action-btn foul">{{ __('Foul') }}</button>
            <button type="button" @click="openOutStep1()" :disabled="busy" class="sb-action-btn out">{{ __('Out') }}</button>
        </div>

        {{-- BATEO: Sencillo | Doble | Triple | HR  (fila 1, 4 cols)
                    HR de pierna | Toque de bolas (fila 2, 2 cols) --}}
        <div x-show="tab==='hit' && !isFinalized && gameStatus==='in_progress'">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button type="button" @click="openHitModal('single')" :disabled="busy" class="sb-action-btn ball">{{ __('Sencillo') }}<div class="text-[10px] font-normal opacity-80 mt-0.5">1B</div></button>
                <button type="button" @click="openHitModal('double')" :disabled="busy" class="sb-action-btn" style="background:#0ea5e9;">{{ __('Doble') }}<div class="text-[10px] font-normal opacity-80 mt-0.5">2B</div></button>
                <button type="button" @click="openHitModal('triple')" :disabled="busy" class="sb-action-btn" style="background:#8b5cf6;">{{ __('Triple') }}<div class="text-[10px] font-normal opacity-80 mt-0.5">3B</div></button>
                <button type="button" @click="openHitModal('hr')" :disabled="busy" class="sb-action-btn strike">{{ __('HR') }}<div class="text-[10px] font-normal opacity-80 mt-0.5">Home Run</div></button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-2 gap-2 mt-2">
                <button type="button" @click="openHitModal('inside_park')" :disabled="busy" class="sb-action-btn foul">{{ __('HR de pierna') }}</button>
                <button type="button" @click="openBuntModal()" :disabled="busy" class="sb-action-btn" style="background:#eab308;">{{ __('Toque de bolas') }}<div class="text-[10px] font-normal opacity-80 mt-0.5">{{ __('Sacrificio o sencillo') }}</div></button>
            </div>
        </div>

        {{-- EXTRAS: 4 arriba (Sustituir, Balk, Reordenar Lineup, Stats)
                      4 abajo (Roster, Cerrar Inning, Finalizar Juego, Box Score) --}}
        <div x-show="tab==='extra'">
            <div x-show="!isFinalized && gameStatus==='in_progress'" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button type="button" @click="openSubstituteModal('pitcher')" :disabled="busy" class="sb-action-btn" style="background:#0ea5e9;">{{ __('Sustitución') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Pitcher, bateador o corredor') }}</div></button>
                <button type="button" @click="sendPitch('balk')" :disabled="busy" class="sb-action-btn" style="background:#a855f7;">{{ __('Balk') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Corredores avanzan 1 base') }}</div></button>
                <button type="button" @click="openLineupModal()" :disabled="busy" class="sb-action-btn" style="background:#10b981;">{{ __('Lineup') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Titulares, posiciones y pitcher') }}</div></button>
                <button type="button" @click="openStatsModal()" :disabled="busy" class="sb-action-btn" style="background:#6366f1;">{{ __('Stats del juego') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Box score completo') }}</div></button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                <a href="{{ route('games.roster.index', $game) }}" class="sb-action-btn out text-center block text-sm">{{ __('Roster') }}</a>
                <button type="button" @click="openEndInningModal()" :disabled="busy" class="sb-action-btn" style="background:#f97316;">{{ __('Cerrar inning') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Terminar la media entrada actual') }}</div></button>
                <button type="button" @click="openEndGameModal()" :disabled="busy" class="sb-action-btn" style="background:#b91c1c;">{{ __('Finalizar juego') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Cerrar el juego por completo') }}</div></button>
                <a href="{{ route('games.box-score', $game) }}" class="sb-action-btn out text-center block text-sm">{{ __('Box Score') }}</a>
            </div>
        </div>
    </div>

    {{-- MODALES (mismas acciones que el scoreboard base) --}}

    <div x-show="modal==='strike'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
            <div class="bg-rose-600 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Tipo de ponche') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-3">
                <button type="button" @click="sendStrike('looking')" class="w-full py-4 bg-rose-700 hover:bg-rose-800 text-white text-lg font-bold rounded-card transition">{{ __('Mirando') }}</button>
                <button type="button" @click="sendStrike('swinging')" class="w-full py-4 bg-rose-800 hover:bg-rose-900 text-white text-lg font-bold rounded-card transition">{{ __('Swing') }}</button>
                <button type="button" @click="sendStrike('foul_tip')" class="w-full py-4 bg-rose-900 hover:bg-black text-white text-lg font-bold rounded-card transition">{{ __('Foul Tip') }}</button>
            </div>
        </div>
    </div>

    <div x-show="modal==='out-step1'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
            <div class="bg-slate-700 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Tipo de out') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-3">
                <button type="button" @click="openOutStep2('fly')" class="w-full py-4 bg-sky-600 hover:bg-sky-700 text-white text-lg font-bold rounded-card transition">{{ __('Fly (elevado)') }}</button>
                <button type="button" @click="openOutStep2('line')" class="w-full py-4 bg-sky-700 hover:bg-sky-800 text-white text-lg font-bold rounded-card transition">{{ __('Línea') }}</button>
                <button type="button" @click="openOutStep2('ground')" class="w-full py-4 bg-amber-600 hover:bg-amber-700 text-white text-lg font-bold rounded-card transition">{{ __('Roletazo') }}</button>
                <button type="button" @click="confirmOut()" class="w-full py-4 bg-slate-600 hover:bg-slate-700 text-white text-lg font-bold rounded-card transition">{{ __('De reglamento') }}</button>
            </div>
        </div>
    </div>

    <div x-show="modal==='out-step2'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden" @click.outside="closeModal()">
            <div class="bg-slate-800 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Jugada defensiva') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5">
                <p class="text-sm text-wv-text-secondary mb-3">{{ __('Toca los fildeadores en el orden que participaron.') }}</p>
                <div class="relative mx-auto w-full max-w-[520px] aspect-[3/2] rounded-card overflow-hidden shadow-inner">
                    {{-- Campo SVG en forma de abanico amplio (aspect 3:2):
                         arco convexo arriba (el green del outfield), dos
                         foul-lines rectas bajando al vertice (home plate),
                         infield brown con el mismo patron de arco + foul-lines,
                         bases cuadradas y un monticulo del pitcher. Las
                         posiciones se renderizan como botones absolutos encima.
                         viewBox 480x320 (proporcion 3:2) le da mas amplitud
                         horizontal que el cuadrado 320x320 del intento anterior,
                         siguiendo la referencia SVG. --}}
                    <svg viewBox="0 0 480 320" class="absolute inset-0 w-full h-full" preserveAspectRatio="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="grassGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#65a30d"/>
                                <stop offset="100%" stop-color="#4d7c0f"/>
                            </linearGradient>
                            <pattern id="grassStripes" patternUnits="userSpaceOnUse" width="40" height="320">
                                <rect width="40" height="320" fill="url(#grassGrad)"/>
                                <rect x="0" width="20" height="320" fill="#84cc16" fill-opacity="0.6"/>
                            </pattern>
                            <linearGradient id="infieldGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#e8c3a0"/>
                                <stop offset="100%" stop-color="#c79c75"/>
                            </linearGradient>
                        </defs>

                        {{-- =====================================================
                             FORMA DEL CAMPO (abanico amplio, aspect 3:2):
                               M (30,90)   -> esquina izquierda superior del green
                               A 250x85 0 0 1 (450,90) -> arco convexo arriba
                                                          (semicirculo amplio)
                               L (240,300) -> vertice inferior (home plate)
                               Z
                             Esto entrega el "abanico" caracteristico del campo
                             de beisbol visto desde home: arco arriba amplio +
                             lineas rectas hacia abajo convergiendo al vertice.
                             ===================================================== --}}
                        <path d="M 30 90 A 250 85 0 0 1 450 90 L 240 300 Z"
                              fill="url(#grassStripes)"
                              stroke="#a17a55" stroke-width="2.5"/>

                        {{-- INFIELD con la misma forma (abanico) a menor escala
                             y un poco mas adentro. Tan/brown claro.
                             Centered horizontalmente en x=240. --}}
                        <path d="M 130 170 A 130 60 0 0 1 350 170 L 240 290 Z"
                              fill="url(#infieldGrad)"
                              stroke="#8a6233" stroke-width="1.5"/>

                        {{-- FOUL LINES blancas: dos lineas desde home hasta
                             los extremos del campo, separando el foul del fair. --}}
                        <line x1="240" y1="300" x2="30"  y2="90" stroke="white" stroke-width="2"/>
                        <line x1="240" y1="300" x2="450" y2="90" stroke="white" stroke-width="2"/>

                        {{-- BASES cuadradas (estilo referencia: home y 1B/3B
                             como cuadrados pequenos; 2B como diamante).
                             Coordenadas en el viewBox 480x320. --}}
                        <rect x="234" y="294" width="12" height="6" fill="white"/>             {{-- Home --}}
                        <rect x="350" y="217" width="9"  height="9" fill="white"/>             {{-- 1B --}}
                        <rect x="120" y="217" width="9"  height="9" fill="white"/>             {{-- 3B --}}
                        <rect x="236" y="108" width="8"  height="8" fill="white" transform="rotate(45 240 112)"/> {{-- 2B (diamante) --}}

                        {{-- MONTICULO del pitcher: circulo tan claro + pequena
                             goma blanca rectangular en el centro (la "rubber"). --}}
                        <circle cx="240" cy="225" r="11" fill="#d4a574"/>
                        <rect x="236" y="223" width="8" height="4" fill="#faf6ee"/>
                    </svg>

                    {{-- ============================================================
                         POSICIONES (botones clickeables superpuestos al SVG).
                         Cada boton usa `transform: translate(-50%, -50%)`
                         para que el % sea del CENTRO del boton (no de la
                         esquina superior izquierda). Los porcentajes se
                         calculan a partir de las coordenadas reales del
                         viewBox 480x320 para que el centro de cada boton
                         caiga justo sobre la base o el monticulo:
                            1B (350/480 = 73%)  (217/320 = 68%)
                            3B (120/480 = 25%)  (217/320 = 68%)
                            2B (240/480 = 50%)  (112/320 = 35%)
                            P  (240/480 = 50%)  (225/320 = 70%)
                            Home (240/480 = 50%) (297/320 = 93%)
                         El arco verde del SVG va de y=5 (centro) a y=90
                         (esquinas de foul-line), por eso LF/CF/RF quedan
                         en top ~12-15% (dentro del green, no afuera).
                         ============================================================ --}}

                    {{-- Outfield dentro del green (arco superior) --}}
                    <button type="button" @click="addFielder('LF')" :class="isFielderSelected('LF') ? 'ring-2 ring-amber-400 bg-amber-100' : 'bg-slate-50 hover:bg-amber-100'"
                            class="absolute w-12 h-9 rounded-md text-[11px] font-black text-slate-800 shadow-md border border-slate-200 flex items-center justify-center"
                            style="top: 22%; left: 21%; transform: translate(-50%, -50%);">LF</button>
                    <button type="button" @click="addFielder('CF')" :class="isFielderSelected('CF') ? 'ring-2 ring-amber-400 bg-amber-100' : 'bg-slate-50 hover:bg-amber-100'"
                            class="absolute w-12 h-9 rounded-md text-[11px] font-black text-slate-800 shadow-md border border-slate-200 flex items-center justify-center"
                            style="top: 14%; left: 50%; transform: translate(-50%, -50%);">CF</button>
                    <button type="button" @click="addFielder('RF')" :class="isFielderSelected('RF') ? 'ring-2 ring-amber-400 bg-amber-100' : 'bg-slate-50 hover:bg-amber-100'"
                            class="absolute w-12 h-9 rounded-md text-[11px] font-black text-slate-800 shadow-md border border-slate-200 flex items-center justify-center"
                            style="top: 22%; right: 21%; transform: translate(-50%, -50%);">RF</button>

                    {{-- Infield interior: SS / 2B (sobre el monticulo, en el
                         brown claro del diamante, justo entre 3B/1B y P) --}}
                    <button type="button" @click="addFielder('SS')" :class="isFielderSelected('SS') ? 'ring-2 ring-amber-400 bg-amber-100' : 'bg-slate-50 hover:bg-amber-100'"
                            class="absolute w-12 h-9 rounded-md text-[11px] font-black text-slate-800 shadow-md border border-slate-200 flex items-center justify-center"
                            style="top: 50%; left: 39%; transform: translate(-50%, -50%);">SS</button>
                    <button type="button" @click="addFielder('2B')" :class="isFielderSelected('2B') ? 'ring-2 ring-amber-400 bg-amber-100' : 'bg-slate-50 hover:bg-amber-100'"
                            class="absolute w-12 h-9 rounded-md text-[11px] font-black text-slate-800 shadow-md border border-slate-200 flex items-center justify-center"
                            style="top: 50%; right: 39%; transform: translate(-50%, -50%);">2B</button>

                    {{-- Esquinas del infield: 3B / 1B (sobre las bases
                         cuadradas en x=120/350, y=217 del viewBox) --}}
                    <button type="button" @click="addFielder('3B')" :class="isFielderSelected('3B') ? 'ring-2 ring-amber-400 bg-amber-100' : 'bg-slate-50 hover:bg-amber-100'"
                            class="absolute w-12 h-9 rounded-md text-[11px] font-black text-slate-800 shadow-md border border-slate-200 flex items-center justify-center"
                            style="top: 68%; left: 27%; transform: translate(-50%, -50%);">3B</button>
                    <button type="button" @click="addFielder('1B')" :class="isFielderSelected('1B') ? 'ring-2 ring-amber-400 bg-amber-100' : 'bg-slate-50 hover:bg-amber-100'"
                            class="absolute w-12 h-9 rounded-md text-[11px] font-black text-slate-800 shadow-md border border-slate-200 flex items-center justify-center"
                            style="top: 68%; right: 27%; transform: translate(-50%, -50%);">1B</button>

                    {{-- Catcher (home plate, en el vertice inferior del
                         diamante, x=240 y=297 del viewBox) --}}
                    <button type="button" @click="addFielder('C')" :class="isFielderSelected('C') ? 'ring-2 ring-amber-400 bg-amber-100' : 'bg-slate-50 hover:bg-amber-100'"
                            class="absolute w-12 h-9 rounded-md text-[11px] font-black text-slate-800 shadow-md border border-slate-200 flex items-center justify-center"
                            style="top: 92%; left: 50%; transform: translate(-50%, -50%);">C</button>

                    {{-- Pitcher (P, amarillo sobre el monticulo x=240 y=225) --}}
                    <button type="button" @click="addFielder('P')" :class="isFielderSelected('P') ? 'ring-4 ring-amber-300 bg-yellow-200' : 'bg-yellow-300 hover:bg-yellow-400'"
                            class="absolute w-11 h-11 rounded-full text-sm font-black text-amber-900 shadow-md border-2 border-yellow-500 flex items-center justify-center"
                            style="top: 70%; left: 50%; transform: translate(-50%, -50%);">P</button>
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
                    <button type="button" @click="closeModal()" class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">{{ __('Cancelar') }}</button>
                    <button type="button" @click="confirmOut()" :disabled="defensiveSequence.length === 0" class="flex-1 py-3 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white font-bold rounded-card">{{ __('Registrar out') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="modal==='hit'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
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
                    <button type="button" @click="closeModal()" class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">{{ __('Cancelar') }}</button>
                    <button type="button" @click="confirmHit()" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-card">{{ __('Registrar hit') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="modal==='bunt'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
            <div class="bg-amber-600 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Toque de bolas') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-3">
                <p class="text-sm text-wv-text-secondary">{{ __('Elige el resultado del toque:') }}</p>
                <button type="button" @click="sendBunt('sacrifice')" class="w-full py-4 bg-amber-700 hover:bg-amber-800 text-white text-lg font-bold rounded-card transition">
                    {{ __('Toque de sacrificio') }}
                    <div class="text-xs font-normal opacity-80 mt-1">{{ __('Bateador out, corredores avanzan') }}</div>
                </button>
                <button type="button" @click="sendBunt('bunt_single')" class="w-full py-4 bg-emerald-700 hover:bg-emerald-800 text-white text-lg font-bold rounded-card transition">
                    {{ __('Bunt single') }}
                    <div class="text-xs font-normal opacity-80 mt-1">{{ __('Bateador a 1B, corredores avanzan') }}</div>
                </button>
            </div>
        </div>
    </div>

    <div x-show="modal==='end-inning'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
            <div class="bg-orange-600 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Cerrar inning') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-3">
                <p class="text-sm text-wv-text-secondary">{{ __('Vas a cerrar la entrada actual antes de los 3 outs. Esta acción no se puede deshacer.') }}</p>
                <div class="flex gap-2">
                    <button type="button" @click="closeModal()" class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">{{ __('Cancelar') }}</button>
                    <button type="button" @click="confirmEndInning()" class="flex-1 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-card">{{ __('Cerrar inning') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="modal==='end-game'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
            <div class="bg-red-700 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Finalizar juego') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-3">
                <p class="text-sm text-wv-text-secondary">{{ __('Vas a finalizar el juego por completo. Esta acción no se puede deshacer.') }}</p>
                <div class="flex gap-2">
                    <button type="button" @click="closeModal()" class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">{{ __('Cancelar') }}</button>
                    <button type="button" @click="confirmEndGame()" class="flex-1 py-3 bg-red-700 hover:bg-red-800 text-white font-bold rounded-card">{{ __('Finalizar') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Gestionar corredor (DISI-20 migrado al v2) --}}
    <div x-show="modal==='inning-summary'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Inning finalizado') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-3" x-show="inningSummary">
                {{-- Cabecera del inning + carreras --}}
                <div class="bg-emerald-900/40 border border-emerald-500/40 rounded-card p-4 text-center">
                    <div class="text-xs text-emerald-300 uppercase font-bold tracking-wider mb-1">
                        Inning <span class="text-emerald-100" x-text="inningSummary?.inning"></span>
                        · <span class="text-emerald-100" x-text="inningSummary?.half === 'top' ? 'TOP' : 'BOTTOM'"></span>
                    </div>
                    <div class="text-4xl font-black text-white my-1">
                        <span x-text="inningSummary?.runs ?? 0"></span>
                        <span class="text-base font-normal opacity-80">carrera<span x-show="(inningSummary?.runs ?? 0) !== 1">s</span></span>
                    </div>
                    <div class="text-xs text-emerald-200">
                        <span x-text="inningSummary?.hits ?? 0"></span> hits ·
                        <span x-text="inningSummary?.walks ?? 0"></span> BB ·
                        <span x-text="inningSummary?.strikeouts ?? 0"></span> K ·
                        <span x-text="inningSummary?.errors ?? 0"></span> E
                    </div>
                </div>

                {{-- Grid de metricas adicionales (LOB + lanzamientos del pitcher) --}}
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3 text-center">
                        <div class="text-3xl font-black text-wv-accent leading-none" x-text="inningSummary?.lob ?? 0"></div>
                        <div class="text-[10px] text-wv-text-secondary uppercase font-bold tracking-wider mt-1.5">{{ __('Dejados en base') }}</div>
                        <div class="text-[9px] text-wv-text-secondary mt-0.5 opacity-70">LOB</div>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3 text-center">
                        <div class="text-3xl font-black text-wv-accent leading-none" x-text="inningSummary?.pitcher_pitches ?? 0"></div>
                        <div class="text-[10px] text-wv-text-secondary uppercase font-bold tracking-wider mt-1.5">{{ __('Lanzamientos') }}</div>
                        <div class="text-[9px] text-wv-text-secondary mt-0.5 opacity-70" x-text="inningSummary?.pitcher_name ? 'de ' + inningSummary.pitcher_name : 'del pitcher'"></div>
                    </div>
                </div>

                <button type="button" @click="closeModal()"
                        class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-card transition">
                    {{ __('Continuar') }}
                </button>
            </div>
            <div x-show="!inningSummary" class="p-6 text-center text-wv-text-secondary">
                {{ __('Sin resumen disponible.') }}
            </div>
        </div>
    </div>

    {{-- Modal: Gestionar corredor (DISI-20 migrado al v2) --}}
    <div x-show="modal==='runner'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeRunnerModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeRunnerModal()">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-black uppercase tracking-wider" x-text="runnerModalTitle()"></h3>
                <button type="button" @click="closeRunnerModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            {{-- Card del corredor --}}
            <div class="px-5 py-4 bg-wv-bg border-b border-wv-border flex-shrink-0">
                <div class="flex items-center gap-3" x-show="runnerModalRunner()">
                    <template x-if="runnerModalRunner()?.photo_url">
                        <img :src="runnerModalRunner().photo_url" class="w-14 h-14 rounded-full object-cover" alt="">
                    </template>
                    <template x-if="!runnerModalRunner()?.photo_url">
                        <div class="w-14 h-14 bg-amber-500 text-white rounded-full flex items-center justify-center font-black text-xl flex-shrink-0"
                             x-text="runnerModalRunner()?.number ?? '?'"></div>
                    </template>
                    <div class="min-w-0 flex-1">
                        <div class="text-base font-bold text-wv-text truncate" x-text="runnerModalRunner()?.name"></div>
                        <div class="text-xs text-wv-text-secondary mt-0.5">
                            <span class="font-semibold" x-text="'#' + (runnerModalRunner()?.number ?? '—')"></span>
                            <span class="text-wv-text-secondary"> · </span>
                            <span x-text="runnerModalRunner()?.position ?? '—'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grid de acciones --}}
            <div class="p-4 overflow-y-auto flex-1">
                <div class="grid grid-cols-2 gap-2">
                    {{-- Avanza a siguiente base --}}
                    <button type="button" @click="sendRunnerAction('advance')" :disabled="runnerActionBusy"
                            class="px-3 py-3 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">⬆️</span>
                            <span class="block text-xs" x-text="runnerBase === 'first' ? 'Avanza a 2B' : (runnerBase === 'second' ? 'Avanza a 3B' : 'Avanza a Home')"></span>
                        </span>
                    </button>

                    {{-- Robo de base --}}
                    <button type="button" @click="sendRunnerAction('stolen_base')" :disabled="runnerActionBusy || isRunnerOnThird()"
                            class="px-3 py-3 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">🏃</span>
                            <span class="block text-xs">{{ __('Robo de base') }}</span>
                            <span class="block text-[9px] font-normal opacity-80">{{ __('Steal') }}</span>
                        </span>
                    </button>

                    {{-- Anota (RBI) --}}
                    <button type="button" @click="sendRunnerAction('score_rbi')" :disabled="runnerActionBusy || isRunnerOnThird()"
                            class="px-3 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">🏃‍♂️‍➡️</span>
                            <span class="block text-xs">{{ __('Anota (RBI)') }}</span>
                            <span class="block text-[9px] font-normal opacity-80">{{ __('Carrera con RBI') }}</span>
                        </span>
                    </button>

                    {{-- Anota (sin RBI) --}}
                    <button type="button" @click="sendRunnerAction('score_no_rbi')" :disabled="runnerActionBusy || isRunnerOnThird()"
                            class="px-3 py-3 bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">🏃‍♂️</span>
                            <span class="block text-xs">{{ __('Anota (sin RBI)') }}</span>
                            <span class="block text-[9px] font-normal opacity-80">{{ __('Sin credito al bateador') }}</span>
                        </span>
                    </button>

                    {{-- Wild pitch --}}
                    <button type="button" @click="sendRunnerAction('wild_pitch')" :disabled="runnerActionBusy"
                            class="px-3 py-3 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">⚾</span>
                            <span class="block text-xs">{{ __('Wild pitch') }}</span>
                            <span class="block text-[9px] font-normal opacity-80">{{ __('Lanzamiento desviado') }}</span>
                        </span>
                    </button>

                    {{-- Passed ball --}}
                    <button type="button" @click="sendRunnerAction('passed_ball')" :disabled="runnerActionBusy"
                            class="px-3 py-3 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">🥎</span>
                            <span class="block text-xs">{{ __('Passed ball') }}</span>
                            <span class="block text-[9px] font-normal opacity-80">{{ __('Error del receptor') }}</span>
                        </span>
                    </button>

                    {{-- OBS (obstruccion) --}}
                    <button type="button" @click="sendRunnerAction('obstruction')" :disabled="runnerActionBusy"
                            class="px-3 py-3 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">🚧</span>
                            <span class="block text-xs">{{ __('Obstrucción (OBS)') }}</span>
                            <span class="block text-[9px] font-normal opacity-80">{{ __('Interferencia defensiva') }}</span>
                        </span>
                    </button>

                    {{-- Pickoff --}}
                    <button type="button" @click="sendRunnerAction('pickoff')" :disabled="runnerActionBusy"
                            class="px-3 py-3 bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">🫳</span>
                            <span class="block text-xs">{{ __('Pickoff') }}</span>
                            <span class="block text-[9px] font-normal opacity-80">{{ __('Atrapado en base') }}</span>
                        </span>
                    </button>

                    {{-- Out al intentar avanzar --}}
                    <button type="button" @click="sendRunnerAction('out_at_advance')" :disabled="runnerActionBusy"
                            class="col-span-2 px-3 py-3 bg-slate-700 hover:bg-slate-800 disabled:opacity-50 text-white text-sm font-bold rounded-card transition text-center">
                        <span class="block leading-tight">
                            <span class="block text-base">❌</span>
                            <span class="block text-xs">{{ __('Out al intentar avanzar') }}</span>
                            <span class="block text-[9px] font-normal opacity-80">{{ __('Out atrapado robando o intentando base extra') }}</span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="p-4 border-t border-wv-border">
                <button type="button" @click="closeRunnerModal()" class="w-full py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">
                    {{ __('Cancelar') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Sustituir (alterna pitcher/bateador/corredor) --}}
    {{-- Modal: Sustituir (migrado exacto del scoreboard base: dropdowns del roster + labels del atleta actual) --}}
    <div x-show="modal==='substitute'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
            <div class="bg-sky-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Sustituir') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-4 overflow-y-auto">
                {{-- Sub-tabs: Pitcher / Bateador / Corredor --}}
                <div class="flex gap-1 border-b border-wv-border">
                    <button type="button" @click="subKind = 'pitcher'"
                            :class="subKind === 'pitcher' ? 'border-b-2 border-sky-500 text-wv-text font-bold' : 'text-wv-text-secondary'"
                            class="px-3 py-2 text-sm">{{ __('Pitcher') }}</button>
                    <button type="button" @click="subKind = 'batter'"
                            :class="subKind === 'batter' ? 'border-b-2 border-sky-500 text-wv-text font-bold' : 'text-wv-text-secondary'"
                            class="px-3 py-2 text-sm">{{ __('Bateador') }}</button>
                    <button type="button" @click="subKind = 'pr'"
                            :class="subKind === 'pr' ? 'border-b-2 border-sky-500 text-wv-text font-bold' : 'text-wv-text-secondary'"
                            class="px-3 py-2 text-sm">{{ __('Corredor') }}</button>
                </div>

                {{-- Pitcher change --}}
                <div x-show="subKind === 'pitcher'" class="space-y-3">
                    <p class="text-sm text-wv-text-secondary">{{ __('Reemplaza al pitcher actual por otro del roster.') }}</p>
                    <div>
                        <label class="text-xs text-wv-text-secondary uppercase font-semibold">{{ __('Pitcher actual') }}</label>
                        <div class="text-base font-bold text-wv-text" x-text="currentPitcherLabel()"></div>
                    </div>
                    <div>
                        <label class="text-xs text-wv-text-secondary uppercase font-semibold">{{ __('Nuevo pitcher') }}</label>
                        <select x-model="subInId"
                                class="w-full mt-1 px-3 py-2 bg-wv-bg border border-wv-border rounded-lg focus:border-sky-500 focus:outline-none text-wv-text">
                            <option value="">{{ __('Selecciona un atleta...') }}</option>
                            <template x-for="a in rosterForBattingTeam()" :key="a.id">
                                <option :value="a.id" x-text="`#${a.lineup_order ?? '-'} ${a.first_name} ${a.last_name}`"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Batter change --}}
                <div x-show="subKind === 'batter'" class="space-y-3">
                    <p class="text-sm text-wv-text-secondary">{{ __('Reemplaza al bateador actual por otro del roster.') }}</p>
                    <div>
                        <label class="text-xs text-wv-text-secondary uppercase font-semibold">{{ __('Bateador actual') }}</label>
                        <div class="text-base font-bold text-wv-text" x-text="currentBatterLabel()"></div>
                    </div>
                    <div>
                        <label class="text-xs text-wv-text-secondary uppercase font-semibold">{{ __('Nuevo bateador') }}</label>
                        <select x-model="subInId"
                                class="w-full mt-1 px-3 py-2 bg-wv-bg border border-wv-border rounded-lg focus:border-sky-500 focus:outline-none text-wv-text">
                            <option value="">{{ __('Selecciona un atleta...') }}</option>
                            <template x-for="a in rosterForBattingTeam()" :key="a.id">
                                <option :value="a.id" x-text="`#${a.lineup_order ?? '-'} ${a.first_name} ${a.last_name}`"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Pinch runner (corredor) --}}
                <div x-show="subKind === 'pr'" class="space-y-3">
                    <p class="text-sm text-wv-text-secondary">{{ __('Reemplaza un corredor en base por otro atleta del roster.') }}</p>
                    <div>
                        <label class="text-xs text-wv-text-secondary uppercase font-semibold">{{ __('Base') }}</label>
                        <select x-model="subBase"
                                class="w-full mt-1 px-3 py-2 bg-wv-bg border border-wv-border rounded-lg focus:border-sky-500 focus:outline-none text-wv-text">
                            <option value="first" x-show="base1">1B: <span x-text="runnerLabel('first')"></span></option>
                            <option value="second" x-show="base2">2B: <span x-text="runnerLabel('second')"></span></option>
                            <option value="third" x-show="base3">3B: <span x-text="runnerLabel('third')"></span></option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-wv-text-secondary uppercase font-semibold">{{ __('Nuevo corredor') }}</label>
                        <select x-model="subInId"
                                class="w-full mt-1 px-3 py-2 bg-wv-bg border border-wv-border rounded-lg focus:border-sky-500 focus:outline-none text-wv-text">
                            <option value="">{{ __('Selecciona un atleta...') }}</option>
                            <template x-for="a in rosterForBattingTeam()" :key="a.id">
                                <option :value="a.id" x-text="`${a.first_name} ${a.last_name}`"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-wv-border flex gap-2 flex-shrink-0">
                <button type="button" @click="closeModal()" class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface-deep text-wv-text font-bold rounded-card">
                    {{ __('Cancelar') }}
                </button>
                <button type="button" @click="confirmSubstitute()" :disabled="!canConfirmSubstitute()"
                        class="flex-1 py-3 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white font-bold rounded-card">
                    {{ __('Sustituir') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Gestion de Lineup (MEJ-4 + DISI-31, migrado exacto del scoreboard base) --}}
    <div x-show="modal==='lineup'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
            <div class="bg-emerald-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Gestion de lineup') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            {{-- Sub-tabs Visitante / Local + contador --}}
            <div class="border-b border-wv-border flex items-center px-3 flex-shrink-0">
                <button type="button" @click="lineupTeam = 'away'; lineupDirty = false"
                        :class="lineupTeam === 'away' ? 'border-b-2 border-emerald-500 text-wv-text font-bold' : 'text-wv-text-secondary'"
                        class="px-3 py-2 text-sm" x-text="awayShort"></button>
                <button type="button" @click="lineupTeam = 'home'; lineupDirty = false"
                        :class="lineupTeam === 'home' ? 'border-b-2 border-emerald-500 text-wv-text font-bold' : 'text-wv-text-secondary'"
                        class="px-3 py-2 text-sm" x-text="homeShort"></button>
                <div class="ml-auto text-xs text-wv-text-secondary px-2">
                    <span x-text="currentLineup().length"></span> / 9 {{ __('titulares') }}
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-4">
                {{-- ============== SECCION TITULARES (9) ============== --}}
                <div>
                    <div class="flex items-center justify-between mb-2 px-1">
                        <h4 class="text-xs uppercase tracking-wider text-wv-text-secondary font-bold">{{ __('Titulares (lineup)') }}</h4>
                        <span class="text-[10px] text-wv-text-secondary">{{ __('Arrastra para reordenar bateo') }}</span>
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
                                 class="flex items-center gap-2 px-2 py-1.5 bg-wv-bg border border-wv-border rounded-lg cursor-grab hover:border-emerald-500">
                                <span class="w-7 h-7 flex items-center justify-center bg-emerald-600 text-white rounded-full text-xs font-black flex-shrink-0" x-text="a.lineup_order"></span>
                                <span class="text-sm font-bold text-emerald-400 w-7 flex-shrink-0 text-center" x-text="'#' + (a.number ?? '-')"></span>
                                <span class="text-sm font-medium text-wv-text flex-1 truncate" x-text="(a.first_name || '') + ' ' + (a.last_name || '')"></span>
                                {{-- Selector de posicion defensiva --}}
                                <select @change="setLineupPosition(a.id, $event.target.value)"
                                        :value="a.position || ''"
                                        class="w-16 text-xs border border-wv-border rounded px-1 py-0.5 flex-shrink-0 bg-wv-surface text-wv-text">
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
                                <label class="flex items-center gap-1 text-xs flex-shrink-0 cursor-pointer" title="{{ __('Marcar como pitcher') }}">
                                    <input type="radio"
                                           :name="`pitcher-${lineupTeam}`"
                                           :checked="a.is_pitcher === true"
                                           @change="setLineupPitcher(a.id)"
                                           class="rounded-full text-emerald-500 focus:ring-emerald-500">
                                    <span class="text-[10px] font-bold text-emerald-400">P</span>
                                </label>
                                {{-- Boton quitar --}}
                                <button type="button" @click="removeFromLineup(a.id)"
                                        title="{{ __('Quitar del lineup') }}"
                                        class="w-7 h-7 bg-red-900/40 hover:bg-red-900/70 text-red-300 rounded text-xs font-black flex-shrink-0">&times;</button>
                                <div class="flex flex-col gap-0.5 flex-shrink-0">
                                    <button type="button" @click="moveUp(a.id)" :disabled="i === 0"
                                            class="w-6 h-4 bg-wv-surface-hover hover:bg-wv-border disabled:opacity-30 rounded text-[10px] font-bold leading-none text-wv-text">&uarr;</button>
                                    <button type="button" @click="moveDown(a.id)" :disabled="i === currentLineup().length - 1"
                                            class="w-6 h-4 bg-wv-surface-hover hover:bg-wv-border disabled:opacity-30 rounded text-[10px] font-bold leading-none text-wv-text">&darr;</button>
                                </div>
                            </div>
                        </template>
                        <div x-show="currentLineup().length === 0" class="text-center text-wv-text-secondary italic py-3 text-sm">
                            {{ __('Este equipo no tiene titulares. Agrega jugadores desde la lista de disponibles.') }}
                        </div>
                    </div>
                </div>

                {{-- ============== SECCION DISPONIBLES (roster no en lineup) ============== --}}
                <div class="border-t border-wv-border pt-3">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <h4 class="text-xs uppercase tracking-wider text-wv-text-secondary font-bold">{{ __('Disponibles (roster)') }}</h4>
                        <span class="text-[10px] text-wv-text-secondary" x-text="availableRoster().length + ' jugadores'"></span>
                    </div>
                    <div class="space-y-1">
                        <template x-for="a in availableRoster()" :key="a.id">
                            <div class="flex items-center gap-2 px-2 py-1.5 bg-wv-surface-hover border border-wv-border rounded-lg hover:border-emerald-500">
                                <span class="text-sm font-bold text-wv-text-secondary w-7 flex-shrink-0 text-center" x-text="'#' + (a.number ?? '-')"></span>
                                <span class="text-sm font-medium text-wv-text flex-1 truncate" x-text="(a.first_name || '') + ' ' + (a.last_name || '')"></span>
                                <span class="text-[10px] text-wv-text-secondary flex-shrink-0" x-text="a.position || ''"></span>
                                <button type="button" @click="addToLineup(a.id)" :disabled="currentLineup().length >= 9"
                                        title="{{ __('Agregar al lineup') }}"
                                        class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold rounded flex-shrink-0">
                                    + {{ __('Agregar') }}
                                </button>
                            </div>
                        </template>
                        <div x-show="availableRoster().length === 0" class="text-center text-wv-text-secondary italic py-3 text-sm">
                            {{ __('Todos los atletas del roster ya estan en el lineup.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-wv-border p-3 flex gap-2 flex-shrink-0">
                <button type="button" @click="closeModal()"
                        class="px-4 py-2 bg-wv-surface-hover hover:bg-wv-border text-wv-text text-sm font-bold rounded-lg">
                    {{ __('Cancelar') }}
                </button>
                <button type="button" @click="saveLineup()"
                        :disabled="currentLineup().length !== 9 || lineupLoading"
                        class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold rounded-lg">
                    <span x-show="currentLineup().length === 9 && !lineupLoading">{{ __('Guardar lineup') }}</span>
                    <span x-show="currentLineup().length !== 9" x-text="'Faltan ' + (9 - currentLineup().length) + ' titulares'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Stats del juego (migrado exacto del scoreboard base, MEJ-3) --}}
    <div x-show="modal==='stats'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-surface rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
            <div class="bg-indigo-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Stats del juego') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            {{-- Line score (carreras por inning) --}}
            <div class="px-5 py-3 bg-wv-bg border-b border-wv-border flex-shrink-0" x-show="statsData && !statsLoading">
                <div class="text-[10px] uppercase tracking-wider text-wv-text-secondary font-semibold mb-2">{{ __('Line score') }}</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-wv-text-secondary">
                                <th class="text-left font-semibold pb-1 pr-2">{{ __('Equipo') }}</th>
                                <template x-for="i in statsData?.total_innings || 7" :key="i">
                                    <th class="text-center font-semibold pb-1 px-1" x-text="i"></th>
                                </template>
                                <th class="text-center font-bold text-wv-text pb-1 pl-2 border-l border-wv-border">{{ __('C') }}</th>
                                <th class="text-center font-bold text-wv-text pb-1 pl-1">{{ __('H') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-wv-border">
                                <td class="py-1 pr-2 font-bold text-wv-text" x-text="statsData?.away_team?.short || awayShort"></td>
                                <template x-for="(r, idx) in lineScoreAway()" :key="'a' + idx">
                                    <td class="text-center py-1 px-1 text-wv-text" x-text="r"></td>
                                </template>
                                <td class="text-center font-black text-base pl-2 border-l border-wv-border text-wv-text" x-text="lineScoreTotal('away')"></td>
                                <td class="text-center font-bold pl-1 text-wv-text" x-text="filteredBatting().filter(b => b.team_id === awayTeamId).reduce((s, b) => s + b.hits, 0)"></td>
                            </tr>
                            <tr class="border-t border-wv-border">
                                <td class="py-1 pr-2 font-bold text-wv-text" x-text="statsData?.home_team?.short || homeShort"></td>
                                <template x-for="(r, idx) in lineScoreHome()" :key="'h' + idx">
                                    <td class="text-center py-1 px-1 text-wv-text" x-text="r"></td>
                                </template>
                                <td class="text-center font-black text-base pl-2 border-l border-wv-border text-wv-text" x-text="lineScoreTotal('home')"></td>
                                <td class="text-center font-bold pl-1 text-wv-text" x-text="filteredBatting().filter(b => b.team_id === homeTeamId).reduce((s, b) => s + b.hits, 0)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabs Pitcheo / Bateo + filtro de equipo --}}
            <div class="border-b border-wv-border flex items-center px-5 pt-3 flex-shrink-0 bg-wv-surface">
                <button type="button" @click="statsTab='batting'"
                        :class="statsTab==='batting' ? 'border-b-2 border-indigo-500 text-wv-text font-bold' : 'text-wv-text-secondary'"
                        class="px-3 py-2 text-sm">{{ __('Bateo') }}</button>
                <button type="button" @click="statsTab='pitching'"
                        :class="statsTab==='pitching' ? 'border-b-2 border-indigo-500 text-wv-text font-bold' : 'text-wv-text-secondary'"
                        class="px-3 py-2 text-sm">{{ __('Pitcheo') }}</button>
                <div class="ml-auto flex gap-1 pb-1">
                    <button type="button" @click="statsFilter='all'"
                            :class="statsFilter==='all' ? 'bg-indigo-600 text-white' : 'bg-wv-surface-hover text-wv-text'"
                            class="px-2 py-1 text-[10px] font-bold rounded">{{ __('Todos') }}</button>
                    <button type="button" @click="statsFilter='home'"
                            :class="statsFilter==='home' ? 'bg-indigo-600 text-white' : 'bg-wv-surface-hover text-wv-text'"
                            class="px-2 py-1 text-[10px] font-bold rounded" x-text="homeShort"></button>
                    <button type="button" @click="statsFilter='away'"
                            :class="statsFilter==='away' ? 'bg-indigo-600 text-white' : 'bg-wv-surface-hover text-wv-text'"
                            class="px-2 py-1 text-[10px] font-bold rounded" x-text="awayShort"></button>
                </div>
            </div>

            {{-- Contenido scrollable --}}
            <div class="flex-1 overflow-y-auto p-5 bg-wv-surface">
                <div x-show="statsLoading" class="text-center text-sm text-wv-text-secondary py-8">{{ __('Cargando...') }}</div>
                <div x-show="!statsLoading && statsError" class="text-center text-sm text-wv-alert py-8" x-text="statsError"></div>

                {{-- Bateo --}}
                <div x-show="!statsLoading && statsTab==='batting' && !statsError" class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-wv-text-secondary text-[10px] uppercase">
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
                                <tr class="border-t border-wv-border">
                                    <td class="py-1.5 pr-2 font-bold text-indigo-400" x-text="b.number ?? '-'"></td>
                                    <td class="py-1.5 pr-2 font-medium text-wv-text" x-text="b.name"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="b.at_bats"></td>
                                    <td class="text-center py-1.5 px-1 font-bold text-wv-text" x-text="b.hits"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="b.doubles"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="b.triples"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="b.hr"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="b.walks"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="b.strikeouts"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="b.rbi"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="b.runs"></td>
                                    <td class="text-center py-1.5 px-1 font-bold text-wv-text" x-text="formatAvg(b.avg)"></td>
                                </tr>
                            </template>
                            <tr x-show="!statsLoading && filteredBatting().length === 0">
                                <td colspan="12" class="text-center text-wv-text-secondary py-4">{{ __('Sin bateadores aun') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pitcheo --}}
                <div x-show="!statsLoading && statsTab==='pitching' && !statsError" class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-wv-text-secondary text-[10px] uppercase">
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
                                <tr class="border-t border-wv-border">
                                    <td class="py-1.5 pr-2 font-bold text-indigo-400" x-text="p.number ?? '-'"></td>
                                    <td class="py-1.5 pr-2 font-medium text-wv-text" x-text="p.name"></td>
                                    <td class="text-center py-1.5 px-1 font-bold text-wv-text" x-text="p.ip"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="p.pitches"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="p.strikes"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="p.balls"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="p.strikeouts"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="p.walks_allowed"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="p.hits_allowed"></td>
                                    <td class="text-center py-1.5 px-1 text-wv-text" x-text="p.runs_allowed"></td>
                                </tr>
                            </template>
                            <tr x-show="!statsLoading && filteredPitching().length === 0">
                                <td colspan="10" class="text-center text-wv-text-secondary py-4">{{ __('Sin pitchers aun') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="border-t border-wv-border p-3 flex gap-2 flex-shrink-0 bg-wv-surface">
                <button type="button" @click="loadStats()"
                        class="px-3 py-2 bg-wv-surface-hover hover:bg-wv-surface-deep text-wv-text text-sm font-bold rounded-lg">
                    {{ __('Recargar') }}
                </button>
                <button type="button" @click="closeModal()"
                        class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg">
                    {{ __('Cerrar') }}
                </button>
            </div>
        </div>
    </div>

    <div class="text-center text-xs mt-3" style="color: var(--sb-text-dim);">
        Vista paralela · scoreboard-v2 · mismos datos que el scoreboard clásico, diseño experimental
    </div>
</div>
</div>
@endsection
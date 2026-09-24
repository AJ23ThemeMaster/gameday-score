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
                <button type="button" @click="openHitModal('inside_park')" :disabled="busy" class="sb-action-btn foul">{{ __('HR de pierna') }}<div class="text-[10px] font-normal opacity-80 mt-0.5">Inside-the-park</div></button>
                <button type="button" @click="openBuntModal()" :disabled="busy" class="sb-action-btn" style="background:#eab308;">{{ __('Toque de bolas') }}<div class="text-[10px] font-normal opacity-80 mt-0.5">{{ __('Sacrificio o bunt single') }}</div></button>
            </div>
        </div>

        {{-- EXTRAS: 4 arriba (Sustituir, Balk, Reordenar Lineup, Stats)
                      4 abajo (Roster, Cerrar Inning, Finalizar Juego, Box Score) --}}
        <div x-show="tab==='extra'">
            <div x-show="!isFinalized && gameStatus==='in_progress'" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button type="button" @click="openSubstituteModal('pitcher')" :disabled="busy" class="sb-action-btn" style="background:#0ea5e9;">{{ __('Sustituir') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Pitcher, bateador o corredor') }}</div></button>
                <button type="button" @click="sendPitch('balk')" :disabled="busy" class="sb-action-btn" style="background:#a855f7;">{{ __('Balk') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Corredores avanzan 1 base') }}</div></button>
                <button type="button" @click="openLineupModal()" :disabled="busy" class="sb-action-btn" style="background:#10b981;">{{ __('Reordenar lineup') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Drag &amp; drop para cambiar el orden') }}</div></button>
                <button type="button" @click="openStatsModal()" :disabled="busy" class="sb-action-btn" style="background:#6366f1;">{{ __('Stats del juego') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Box score completo: pitcheo y bateo') }}</div></button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                <a href="{{ route('games.roster.index', $game) }}" class="sb-action-btn out text-center block text-sm">👥 {{ __('Roster del juego') }}</a>
                <button type="button" @click="openEndInningModal()" :disabled="busy" class="sb-action-btn" style="background:#f97316;">{{ __('Cerrar inning') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Terminar la media entrada actual') }}</div></button>
                <button type="button" @click="openEndGameModal()" :disabled="busy" class="sb-action-btn" style="background:#b91c1c;">{{ __('Finalizar juego') }}<div class="text-[9px] font-normal opacity-80 mt-0.5 leading-tight">{{ __('Cerrar el juego por completo') }}</div></button>
                <a href="{{ route('games.box-score', $game) }}" class="sb-action-btn out text-center block text-sm">📋 {{ __('Box Score') }}</a>
            </div>
        </div>
    </div>

    {{-- MODALES (mismas acciones que el scoreboard base) --}}

    <div x-show="modal==='strike'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
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

    <div x-show="modal==='out-step1'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
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

    <div x-show="modal==='out-step2'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" @click.outside="closeModal()">
            <div class="bg-slate-800 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Jugada defensiva') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5">
                <p class="text-sm text-wv-text-secondary mb-3">{{ __('Toca los fildeadores en el orden que participaron.') }}</p>
                <div class="relative bg-emerald-700 rounded-card mx-auto" style="width: 320px; height: 320px;">
                    <div class="absolute rounded-full bg-amber-200/30" style="top: 90px; left: 40px; right: 40px; bottom: 90px;"></div>
                    <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 70px; left: 50%; transform: translateX(-50%) rotate(45deg);"></div>
                    <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 50%; right: 8px; transform: translateY(-50%) rotate(45deg);"></div>
                    <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="bottom: 8px; left: 50%; transform: translateX(-50%) rotate(45deg);"></div>
                    <div class="absolute w-9 h-9 bg-white border-2 border-gray-300 rounded rotate-45" style="top: 50%; left: 8px; transform: translateY(-50%) rotate(45deg);"></div>
                    <div class="absolute flex items-center justify-center text-xs font-black text-amber-900 bg-amber-200/90 rounded-full" style="top: 138px; left: 50%; transform: translate(-50%, -50%); width: 44px; height: 44px;">P</div>
                    <button type="button" @click="addFielder('LF')" style="top: 14px; left: 16px;" :class="isFielderSelected('LF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'" class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">LF</button>
                    <button type="button" @click="addFielder('CF')" style="top: 10px; left: 50%; transform: translateX(-50%);" :class="isFielderSelected('CF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'" class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">CF</button>
                    <button type="button" @click="addFielder('RF')" style="top: 14px; right: 16px;" :class="isFielderSelected('RF') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'" class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">RF</button>
                    <button type="button" @click="addFielder('SS')" style="top: 102px; left: 50px;" :class="isFielderSelected('SS') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'" class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">SS</button>
                    <button type="button" @click="addFielder('2B')" style="top: 102px; right: 50px;" :class="isFielderSelected('2B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'" class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">2B</button>
                    <button type="button" @click="addFielder('3B')" style="top: 178px; left: 28px;" :class="isFielderSelected('3B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'" class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">3B</button>
                    <button type="button" @click="addFielder('1B')" style="top: 178px; right: 28px;" :class="isFielderSelected('1B') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'" class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">1B</button>
                    <button type="button" @click="addFielder('C')" style="bottom: 56px; left: 50%; transform: translateX(-50%);" :class="isFielderSelected('C') ? 'bg-amber-300 border-2 border-amber-500' : 'bg-slate-100 hover:bg-amber-200'" class="absolute w-14 h-10 rounded text-xs font-bold text-slate-800 shadow">C</button>
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
                    <button type="button" @click="closeModal()" class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">{{ __('Cancelar') }}</button>
                    <button type="button" @click="confirmHit()" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-card">{{ __('Registrar hit') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="modal==='bunt'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
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

    <div x-show="modal==='end-inning'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
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

    <div x-show="modal==='end-game'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
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
    <div x-show="modal==='inning-summary'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="closeModal()">
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
                        <div class="text-3xl font-black text-wv-info leading-none" x-text="inningSummary?.pitcher_pitches ?? 0"></div>
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
    <div x-show="modal==='runner'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeRunnerModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeRunnerModal()">
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
                            <span class="text-wv-text-dim"> · </span>
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
    <div x-show="modal==='substitute'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
            <div class="bg-sky-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Sustituir') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-3 overflow-y-auto">
                <div class="flex gap-2 text-xs">
                    <button type="button" @click="substituteKind='pitcher'"
                            :class="substituteKind==='pitcher' ? 'bg-sky-600 text-white' : 'bg-wv-surface text-wv-text-secondary'"
                            class="flex-1 py-2 rounded font-bold uppercase tracking-wider">{{ __('Pitcher') }}</button>
                    <button type="button" @click="substituteKind='batter'"
                            :class="substituteKind==='batter' ? 'bg-sky-600 text-white' : 'bg-wv-surface text-wv-text-secondary'"
                            class="flex-1 py-2 rounded font-bold uppercase tracking-wider">{{ __('Bateador') }}</button>
                    <button type="button" @click="substituteKind='runner'"
                            :class="substituteKind==='runner' ? 'bg-sky-600 text-white' : 'bg-wv-surface text-wv-text-secondary'"
                            class="flex-1 py-2 rounded font-bold uppercase tracking-wider">{{ __('Corredor') }}</button>
                </div>
                <div x-show="substituteKind==='runner'">
                    <label class="block text-xs text-wv-text-secondary uppercase font-semibold mb-1">{{ __('Base del corredor saliente') }}</label>
                    <select x-model="substituteBase" class="w-full bg-wv-bg border border-wv-border rounded-card text-wv-text px-3 py-2">
                        <option value="first">{{ __('1ra Base') }}</option>
                        <option value="second">{{ __('2da Base') }}</option>
                        <option value="third">{{ __('3ra Base') }}</option>
                    </select>
                </div>
                <p class="text-xs text-wv-text-secondary">
                    {{ __('Indica el atleta saliente y el atleta entrante (mismo equipo). Esta vista captura los IDs y los envia al endpoint de sustitucion.') }}
                </p>
                <div>
                    <label class="block text-xs text-wv-text-secondary uppercase font-semibold mb-1">{{ __('Atleta saliente (ID)') }}</label>
                    <input type="number" min="1" x-model.number="substituteOutId" class="w-full bg-wv-bg border border-wv-border rounded-card text-wv-text px-3 py-2" placeholder="ID">
                </div>
                <div>
                    <label class="block text-xs text-wv-text-secondary uppercase font-semibold mb-1">{{ __('Atleta entrante (ID)') }}</label>
                    <input type="number" min="1" x-model.number="substituteInId" class="w-full bg-wv-bg border border-wv-border rounded-card text-wv-text px-3 py-2" placeholder="ID">
                </div>
                <div x-show="substituteError" class="text-sm text-wv-alert" x-text="substituteError"></div>
            </div>
            <div class="p-4 border-t border-wv-border flex gap-2 flex-shrink-0">
                <button type="button" @click="closeModal()" class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">{{ __('Cancelar') }}</button>
                <button type="button" @click="sendSubstitute()" :disabled="substituteBusy" class="flex-1 py-3 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white font-bold rounded-card">
                    <span x-show="!substituteBusy">{{ __('Sustituir') }}</span>
                    <span x-show="substituteBusy">{{ __('Procesando...') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Reordenar lineup --}}
    <div x-show="modal==='lineup'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
            <div class="bg-emerald-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Reordenar lineup') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-3 overflow-y-auto">
                <p class="text-sm text-wv-text-secondary">
                    {{ __('Captura aqui la nueva secuencia de IDs del lineup del equipo al bate (separados por comas, en el orden deseado).') }}
                </p>
                <div>
                    <label class="block text-xs text-wv-text-secondary uppercase font-semibold mb-1">{{ __('Orden del lineup (IDs separados por coma)') }}</label>
                    <input type="text" id="lineup-order-input" placeholder="11,7,3,22,..." class="w-full bg-wv-bg border border-wv-border rounded-card text-wv-text px-3 py-2 font-mono">
                </div>
                <div x-show="lineupError" class="text-sm text-wv-alert" x-text="lineupError"></div>
                <p class="text-xs text-wv-text-secondary">
                    {{ __('Tip: abre el Roster del juego para ver los IDs de los atletas del lineup.') }}
                </p>
            </div>
            <div class="p-4 border-t border-wv-border flex gap-2 flex-shrink-0">
                <button type="button" @click="closeModal()" class="flex-1 py-3 bg-wv-surface-hover hover:bg-wv-surface text-wv-text font-bold rounded-card">{{ __('Cancelar') }}</button>
                <button type="button" @click="
                        const v = document.getElementById('lineup-order-input').value;
                        const order = v.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n));
                        if (order.length === 0) { this.lineupError = 'Ingresa al menos un ID'; return; }
                        submitLineupReorder(order);
                    " :disabled="lineupBusy" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-card">
                    <span x-show="!lineupBusy">{{ __('Guardar orden') }}</span>
                    <span x-show="lineupBusy">{{ __('Guardando...') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Stats del juego --}}
    <div x-show="modal==='stats'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 p-4"
         @keydown.escape.window="closeModal()">
        <div class="bg-wv-card rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col" @click.outside="closeModal()">
            <div class="bg-indigo-600 text-white px-5 py-3 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-black uppercase tracking-wider">{{ __('Stats del juego') }}</h3>
                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 overflow-y-auto">
                <div x-show="statsLoading" class="text-center text-wv-text-secondary py-8">
                    {{ __('Cargando...') }}
                </div>
                <div x-show="!statsLoading && statsError" class="text-center text-wv-alert py-8" x-text="statsError"></div>
                <div x-show="!statsLoading && statsData" class="space-y-3">
                    <div class="text-xs text-wv-text-secondary">
                        {{ __('Line score + box score resumido. Para ver el detalle completo visita la pagina Box Score.') }}
                    </div>
                    <div class="flex gap-1 border-b border-wv-border text-xs">
                        <button type="button" @click="statsTab='batting'"
                                :class="statsTab==='batting' ? 'border-wv-accent text-wv-accent font-bold' : 'border-transparent text-wv-text-secondary'"
                                class="px-3 py-1 border-b-2">{{ __('Bateo') }}</button>
                        <button type="button" @click="statsTab='pitching'"
                                :class="statsTab==='pitching' ? 'border-wv-accent text-wv-accent font-bold' : 'border-transparent text-wv-text-secondary'"
                                class="px-3 py-1 border-b-2">{{ __('Pitcheo') }}</button>
                    </div>
                    <div x-show="statsTab==='batting'">
                        <div class="text-[10px] uppercase tracking-wider text-wv-text-secondary font-semibold mb-1">{{ __('Bateo') }}</div>
                        <pre class="bg-wv-bg rounded-card p-3 text-[11px] overflow-x-auto" x-text="JSON.stringify(statsData?.batting ?? statsData?.line_score ?? statsData, null, 2)"></pre>
                    </div>
                    <div x-show="statsTab==='pitching'">
                        <div class="text-[10px] uppercase tracking-wider text-wv-text-secondary font-semibold mb-1">{{ __('Pitcheo') }}</div>
                        <pre class="bg-wv-bg rounded-card p-3 text-[11px] overflow-x-auto" x-text="JSON.stringify(statsData?.pitching ?? {}, null, 2)"></pre>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-wv-border flex-shrink-0">
                <a :href="statsUrl" target="_blank" class="block w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-card text-center">
                    {{ __('Abrir Box Score completo') }}
                </a>
            </div>
        </div>
    </div>

    <div class="text-center text-xs mt-3" style="color: var(--sb-text-dim);">
        Vista paralela · scoreboard-v2 · mismos datos que el scoreboard clásico, diseño experimental
    </div>
</div>
</div>
@endsection
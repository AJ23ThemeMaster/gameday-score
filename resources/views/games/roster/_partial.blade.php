{{--
    Partial del roster (pitchers + 2 tablas). Se usa desde el index() y se
    re-renderiza despues de cada operacion AJAX. El div #roster-content es
    el target de Alpine.store('roaster').replaceRoster(html).

    Variables requeridas: $game, $rosterEntries, $homeAvailable, $awayAvailable,
                          $homePitcher, $awayPitcher

    WattVision (feature/style/wattvision): paleta dark consistente. Se conserva
    el naming `local` (home=azul-cyan) y `visitante` (away=amber) via tokens
    wv-accent (cyan) y wv-alert (rojo). Se usan clases de borde lateral para
    distinguir sin recargar fondos.
--}}
<div id="roster-content">
    {{-- Lanzadores actuales --}}
    <div class="bg-wv-surface border border-wv-border rounded-card p-4 mb-6">
        <h3 class="text-sm font-semibold text-wv-text mb-2">{{ __('Lanzadores actuales') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center gap-2 p-3 bg-wv-bg border-l-[3px] border-wv-accent rounded-card">
                <span class="text-xs font-semibold text-wv-accent uppercase tracking-wider">{{ __('Local') }}</span>
                @if ($homePitcher)
                    <span class="font-mono text-xs bg-wv-surface-hover px-1.5 rounded text-wv-text">{{ $homePitcher->pivot->position ?? 'P' }}</span>
                    <span class="font-medium text-wv-text">{{ $homePitcher->full_name }}</span>
                    <span class="text-xs text-wv-text-secondary ml-auto" data-pitcher-pitches="{{ $homePitcher->id }}">{{ $homePitcher->pivot->pitches_thrown }} {{ __('pitches') }}</span>
                @else
                    <span class="text-sm text-wv-text-secondary italic" data-pitcher-empty="home">{{ __('Sin lanzador asignado') }}</span>
                @endif
            </div>
            <div class="flex items-center gap-2 p-3 bg-wv-bg border-l-[3px] border-wv-alert rounded-card">
                <span class="text-xs font-semibold text-wv-alert uppercase tracking-wider">{{ __('Visitante') }}</span>
                @if ($awayPitcher)
                    <span class="font-mono text-xs bg-wv-surface-hover px-1.5 rounded text-wv-text">{{ $awayPitcher->pivot->position ?? 'P' }}</span>
                    <span class="font-medium text-wv-text">{{ $awayPitcher->full_name }}</span>
                    <span class="text-xs text-wv-text-secondary ml-auto" data-pitcher-pitches="{{ $awayPitcher->id }}">{{ $awayPitcher->pivot->pitches_thrown }} {{ __('pitches') }}</span>
                @else
                    <span class="text-sm text-wv-text-secondary italic" data-pitcher-empty="away">{{ __('Sin lanzador asignado') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Dos columnas: Local / Visitante --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        @foreach ([
            ['team' => $game->homeTeam, 'teamId' => $game->home_team_id, 'available' => $homeAvailable, 'pitcher' => $homePitcher, 'side' => 'home', 'label' => __('Local'), 'addClass' => 'bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent', 'borderClass' => 'border-wv-accent'],
            ['team' => $game->awayTeam, 'teamId' => $game->away_team_id, 'available' => $awayAvailable, 'pitcher' => $awayPitcher, 'side' => 'away', 'label' => __('Visitante'), 'addClass' => 'bg-wv-alert hover:bg-wv-alert-hover text-wv-text-on-alert', 'borderClass' => 'border-wv-alert'],
        ] as $side)
            <div class="bg-wv-surface border border-wv-border rounded-card" data-team-card="{{ $side['side'] }}">
                <div class="p-4 border-b border-wv-border flex justify-between items-center bg-wv-bg">
                    <div class="flex items-center gap-2">
                        @if ($side['team']->logoUrl)<img src="{{ $side['team']->logoUrl }}" class="h-7 w-7 object-contain bg-white rounded p-0.5">@endif
                        <h3 class="font-semibold text-wv-text">{{ $side['team']->name }}</h3>
                        <span class="text-xs text-wv-text-secondary">({{ $side['label'] }})</span>
                    </div>
                    <button type="button"
                            @click="$store.roaster.openAdd({{ $side['teamId'] }}, @js($side['team']->name), {{ $side['available']->map(fn($a) => ['id' => $a->id, 'name' => $a->full_name, 'number' => $a->number, 'position' => $a->position])->values()->toJson() }})"
                            class="inline-flex items-center px-3 py-1.5 {{ $side['addClass'] }} text-xs font-semibold rounded-card transition">
                        + {{ __('Agregar atleta') }}
                    </button>
                </div>

                @php
                    $roster = $rosterEntries->filter(fn ($a) => $a->pivot->team_id === $side['teamId'])->sortBy(fn ($a) => $a->pivot->lineup_order ?? 99);
                @endphp
                @if ($roster->isEmpty())
                    <div class="p-6 text-center text-sm text-wv-text-secondary">
                        {{ __('Sin atletas en el roster.') }}
                    </div>
                @else
                    <table class="min-w-full divide-y divide-wv-border text-sm">
                        <thead class="bg-wv-surface-hover">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase">#</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase">{{ __('Pos') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase">{{ __('Nombre') }}</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-wv-text-secondary uppercase">{{ __('P') }}</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-wv-text-secondary uppercase">{{ __('Pitches') }}</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-wv-text-secondary uppercase">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-wv-border">
                            @foreach ($roster as $a)
                                <tr data-roster-row="{{ $a->id }}" class="{{ $a->pivot->is_pitcher ? 'bg-wv-accent-soft' : '' }} hover:bg-wv-surface-hover">
                                    <td class="px-3 py-2">
                                        <input type="number" name="lineup_order" value="{{ $a->pivot->lineup_order }}" min="1" max="30"
                                               class="w-12 text-center text-sm border-wv-border bg-wv-surface text-wv-text rounded-md"
                                               data-roster-field="lineup_order"
                                               data-athlete-id="{{ $a->id }}"
                                               @change="$store.roaster.updateField($el)">
                                    </td>
                                    <td class="px-3 py-2">
                                        <select name="position" data-roster-field="position" data-athlete-id="{{ $a->id }}"
                                                @change="$store.roaster.updateField($el)"
                                                class="text-xs font-mono border-wv-border bg-wv-surface text-wv-text rounded-md">
                                            <option value="">—</option>
                                            @foreach (['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'] as $pos)
                                                <option value="{{ $pos }}" {{ $a->pivot->position === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="font-medium text-wv-text">{{ $a->full_name }}</span>
                                        <span class="text-xs text-wv-text-secondary ml-1">B:{{ $a->bats }} T:{{ $a->throws }}</span>
                                        @if (! $a->pivot->is_starter)
                                            <span class="ml-1 text-xs bg-wv-surface-hover text-wv-text-secondary px-1.5 rounded">{{ __('Suplente') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button"
                                                data-roster-field="is_pitcher" data-athlete-id="{{ $a->id }}"
                                                data-current="{{ $a->pivot->is_pitcher ? '1' : '0' }}"
                                                @click="$store.roaster.togglePitcher($el)"
                                                title="{{ $a->pivot->is_pitcher ? __('Quitar como pitcher') : __('Marcar como pitcher') }}"
                                                class="px-2 py-1 text-xs rounded-md {{ $a->pivot->is_pitcher ? 'bg-wv-accent text-wv-text-on-accent font-bold' : 'bg-wv-surface-hover text-wv-text-secondary hover:bg-wv-surface' }}">
                                            {{ $a->pivot->is_pitcher ? '★' : '☆' }}
                                        </button>
                                    </td>
                                    <td class="px-3 py-2 text-center text-xs">
                                        <input type="number" name="pitches_thrown" value="{{ $a->pivot->pitches_thrown }}" min="0" max="999"
                                               data-roster-field="pitches_thrown" data-athlete-id="{{ $a->id }}"
                                               @change="$store.roaster.updateField($el)"
                                               class="w-14 text-center text-xs border-wv-border bg-wv-surface text-wv-text rounded-md">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button"
                                                @click="$store.roaster.openSubstitute({{ $a->id }}, @js($a->full_name), {{ $a->pivot->team_id }}, @js($side['team']->name), {{ $side['available']->map(fn($x) => ['id' => $x->id, 'name' => $x->full_name, 'number' => $x->number, 'position' => $x->position])->values()->toJson() }}, {{ $a->pivot->lineup_order ?? 0 }}, @js($a->pivot->position), {{ $a->pivot->is_pitcher ? 'true' : 'false' }})"
                                                class="text-xs text-wv-accent hover:text-wv-accent-hover mr-2">
                                            {{ __('Sustituir') }}
                                        </button>
                                        <button type="button"
                                                data-roster-action="destroy" data-athlete-id="{{ $a->id }}" data-athlete-name="{{ $a->full_name }}"
                                                @click="$store.roaster.confirmRemove($el)"
                                                class="text-xs text-wv-alert hover:text-wv-alert-hover">
                                            {{ __('Quitar') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach

    </div>
</div>
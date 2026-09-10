{{--
    Partial del roster (pitchers + 2 tablas). Se usa desde el index() y se
    re-renderiza despues de cada operacion AJAX. El div #roster-content es
    el target de Alpine.store('roaster').replaceRoster(html).

    Variables requeridas: $game, $rosterEntries, $homeAvailable, $awayAvailable,
                          $homePitcher, $awayPitcher
--}}
<div id="roster-content">
    {{-- Lanzadores actuales --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Lanzadores actuales') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center gap-2 p-3 bg-indigo-50 rounded">
                <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wider">{{ __('Local') }}</span>
                @if ($homePitcher)
                    <span class="font-mono text-xs bg-indigo-200 px-1.5 rounded">{{ $homePitcher->pivot->position ?? 'P' }}</span>
                    <span class="font-medium">{{ $homePitcher->full_name }}</span>
                    <span class="text-xs text-gray-500 ml-auto" data-pitcher-pitches="{{ $homePitcher->id }}">{{ $homePitcher->pivot->pitches_thrown }} {{ __('pitches') }}</span>
                @else
                    <span class="text-sm text-gray-500 italic" data-pitcher-empty="home">{{ __('Sin lanzador asignado') }}</span>
                @endif
            </div>
            <div class="flex items-center gap-2 p-3 bg-rose-50 rounded">
                <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider">{{ __('Visitante') }}</span>
                @if ($awayPitcher)
                    <span class="font-mono text-xs bg-rose-200 px-1.5 rounded">{{ $awayPitcher->pivot->position ?? 'P' }}</span>
                    <span class="font-medium">{{ $awayPitcher->full_name }}</span>
                    <span class="text-xs text-gray-500 ml-auto" data-pitcher-pitches="{{ $awayPitcher->id }}">{{ $awayPitcher->pivot->pitches_thrown }} {{ __('pitches') }}</span>
                @else
                    <span class="text-sm text-gray-500 italic" data-pitcher-empty="away">{{ __('Sin lanzador asignado') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Dos columnas: Local / Visitante --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        @foreach ([
            ['team' => $game->homeTeam, 'teamId' => $game->home_team_id, 'available' => $homeAvailable, 'pitcher' => $homePitcher, 'color' => 'indigo', 'label' => __('Local'), 'dataKey' => 'home'],
            ['team' => $game->awayTeam, 'teamId' => $game->away_team_id, 'available' => $awayAvailable, 'pitcher' => $awayPitcher, 'color' => 'rose', 'label' => __('Visitante'), 'dataKey' => 'away'],
        ] as $side)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" data-team-card="{{ $side['dataKey'] }}">
                <div class="p-4 border-b border-gray-200 bg-{{ $side['color'] }}-50 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        @if ($side['team']->logoUrl)<img src="{{ $side['team']->logoUrl }}" class="h-7 w-7 object-contain">@endif
                        <h3 class="font-semibold text-gray-900">{{ $side['team']->name }}</h3>
                        <span class="text-xs text-gray-500">({{ $side['label'] }})</span>
                    </div>
                    <button type="button"
                            @click="$store.roaster.openAdd({{ $side['teamId'] }}, @js($side['team']->name), {{ $side['available']->map(fn($a) => ['id' => $a->id, 'name' => $a->full_name, 'number' => $a->number, 'position' => $a->position])->values()->toJson() }})"
                            class="inline-flex items-center px-3 py-1.5 bg-{{ $side['color'] }}-600 hover:bg-{{ $side['color'] }}-700 text-white text-xs font-semibold rounded-md">
                        + {{ __('Agregar atleta') }}
                    </button>
                </div>

                @php
                    $roster = $rosterEntries->filter(fn ($a) => $a->pivot->team_id === $side['teamId'])->sortBy(fn ($a) => $a->pivot->lineup_order ?? 99);
                @endphp
                @if ($roster->isEmpty())
                    <div class="p-6 text-center text-sm text-gray-500">
                        {{ __('Sin atletas en el roster.') }}
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Pos') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Nombre') }}</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ __('P') }}</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ __('Pitches') }}</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($roster as $a)
                                <tr data-roster-row="{{ $a->id }}" class="{{ $a->pivot->is_pitcher ? 'bg-yellow-50' : '' }}">
                                    <td class="px-3 py-2">
                                        <input type="number" name="lineup_order" value="{{ $a->pivot->lineup_order }}" min="1" max="30"
                                               class="w-12 text-center text-sm border-gray-300 rounded"
                                               data-roster-field="lineup_order"
                                               data-athlete-id="{{ $a->id }}"
                                               @change="$store.roaster.updateField($el)">
                                    </td>
                                    <td class="px-3 py-2">
                                        <select name="position" data-roster-field="position" data-athlete-id="{{ $a->id }}"
                                                @change="$store.roaster.updateField($el)"
                                                class="text-xs font-mono border-gray-300 rounded">
                                            <option value="">—</option>
                                            @foreach (['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'] as $pos)
                                                <option value="{{ $pos }}" {{ $a->pivot->position === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="font-medium">{{ $a->full_name }}</span>
                                        <span class="text-xs text-gray-500 ml-1">B:{{ $a->bats }} T:{{ $a->throws }}</span>
                                        @if (! $a->pivot->is_starter)
                                            <span class="ml-1 text-xs bg-orange-100 text-orange-700 px-1.5 rounded">{{ __('Suplente') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button"
                                                data-roster-field="is_pitcher" data-athlete-id="{{ $a->id }}"
                                                data-current="{{ $a->pivot->is_pitcher ? '1' : '0' }}"
                                                @click="$store.roaster.togglePitcher($el)"
                                                title="{{ $a->pivot->is_pitcher ? __('Quitar como pitcher') : __('Marcar como pitcher') }}"
                                                class="px-2 py-1 text-xs rounded {{ $a->pivot->is_pitcher ? 'bg-yellow-300 text-yellow-900 font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                            {{ $a->pivot->is_pitcher ? '★' : '☆' }}
                                        </button>
                                    </td>
                                    <td class="px-3 py-2 text-center text-xs">
                                        <input type="number" name="pitches_thrown" value="{{ $a->pivot->pitches_thrown }}" min="0" max="999"
                                               data-roster-field="pitches_thrown" data-athlete-id="{{ $a->id }}"
                                               @change="$store.roaster.updateField($el)"
                                               class="w-14 text-center text-xs border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button"
                                                @click="$store.roaster.openSubstitute({{ $a->id }}, @js($a->full_name), {{ $a->pivot->team_id }}, @js($side['team']->name), {{ $side['available']->map(fn($x) => ['id' => $x->id, 'name' => $x->full_name, 'number' => $x->number, 'position' => $x->position])->values()->toJson() }}, {{ $a->pivot->lineup_order ?? 0 }}, @js($a->pivot->position), {{ $a->pivot->is_pitcher ? 'true' : 'false' }})"
                                                class="text-xs text-indigo-600 hover:text-indigo-900 mr-2">
                                            {{ __('Sustituir') }}
                                        </button>
                                        <button type="button"
                                                data-roster-action="destroy" data-athlete-id="{{ $a->id }}" data-athlete-name="{{ $a->full_name }}"
                                                @click="$store.roaster.confirmRemove($el)"
                                                class="text-xs text-red-600 hover:text-red-900">
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

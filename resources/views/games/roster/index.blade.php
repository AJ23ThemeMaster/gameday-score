<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Roster') }}: {{ $game->homeTeam->short_name ?? $game->homeTeam->name }} <span class="text-gray-400">vs</span> {{ $game->awayTeam->short_name ?? $game->awayTeam->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('games.show', $game) }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Detalle') }}</a>
                <a href="{{ route('games.live', $game) }}" class="inline-flex items-center px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-md">{{ __('Scoreboard en vivo') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            {{-- Lanzadores actuales --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Lanzadores actuales') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center gap-2 p-3 bg-indigo-50 rounded">
                        <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wider">{{ __('Local') }}</span>
                        @if ($homePitcher)
                            <span class="font-mono text-xs bg-indigo-200 px-1.5 rounded">{{ $homePitcher->pivot->position ?? 'P' }}</span>
                            <span class="font-medium">{{ $homePitcher->full_name }}</span>
                            <span class="text-xs text-gray-500 ml-auto">{{ $homePitcher->pivot->pitches_thrown }} {{ __('pitches') }}</span>
                        @else
                            <span class="text-sm text-gray-500 italic">{{ __('Sin lanzador asignado') }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 p-3 bg-rose-50 rounded">
                        <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider">{{ __('Visitante') }}</span>
                        @if ($awayPitcher)
                            <span class="font-mono text-xs bg-rose-200 px-1.5 rounded">{{ $awayPitcher->pivot->position ?? 'P' }}</span>
                            <span class="font-medium">{{ $awayPitcher->full_name }}</span>
                            <span class="text-xs text-gray-500 ml-auto">{{ $awayPitcher->pivot->pitches_thrown }} {{ __('pitches') }}</span>
                        @else
                            <span class="text-sm text-gray-500 italic">{{ __('Sin lanzador asignado') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Dos columnas: Local / Visitante --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                @foreach ([
                    ['team' => $game->homeTeam, 'teamId' => $game->home_team_id, 'available' => $homeAvailable, 'pitcher' => $homePitcher, 'color' => 'indigo', 'label' => __('Local')],
                    ['team' => $game->awayTeam, 'teamId' => $game->away_team_id, 'available' => $awayAvailable, 'pitcher' => $awayPitcher, 'color' => 'rose', 'label' => __('Visitante')],
                ] as $side)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4 border-b border-gray-200 bg-{{ $side['color'] }}-50 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                @if ($side['team']->logoUrl)<img src="{{ $side['team']->logoUrl }}" class="h-7 w-7 object-contain">@endif
                                <h3 class="font-semibold text-gray-900">{{ $side['team']->name }}</h3>
                                <span class="text-xs text-gray-500">({{ $side['label'] }})</span>
                            </div>
                            <button type="button"
                                    @click="$dispatch('open-add-modal', { teamId: {{ $side['teamId'] }}, teamName: @js($side['team']->name), available: @js($side['available']->map(fn($a) => ['id' => $a->id, 'name' => $a->full_name, 'number' => $a->number, 'position' => $a->position])->values()) })"
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
                                        <tr class="{{ $a->pivot->is_pitcher ? 'bg-yellow-50' : '' }}">
                                            <td class="px-3 py-2">
                                                <form method="POST" action="{{ route('games.roster.update', [$game, $a]) }}" class="inline">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="lineup_order" value="{{ $a->pivot->lineup_order }}">
                                                    <input type="number" name="lineup_order" value="{{ $a->pivot->lineup_order }}" min="1" max="30"
                                                           class="w-12 text-center text-sm border-gray-300 rounded"
                                                           onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="px-3 py-2">
                                                <form method="POST" action="{{ route('games.roster.update', [$game, $a]) }}" class="inline">
                                                    @csrf @method('PATCH')
                                                    <select name="position" onchange="this.form.submit()"
                                                            class="text-xs font-mono border-gray-300 rounded">
                                                        <option value="">—</option>
                                                        @foreach (['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'] as $pos)
                                                            <option value="{{ $pos }}" {{ $a->pivot->position === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="px-3 py-2">
                                                <span class="font-medium">{{ $a->full_name }}</span>
                                                <span class="text-xs text-gray-500 ml-1">B:{{ $a->bats }} T:{{ $a->throws }}</span>
                                                @if (! $a->pivot->is_starter)
                                                    <span class="ml-1 text-xs bg-orange-100 text-orange-700 px-1.5 rounded">{{ __('Suplente') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <form method="POST" action="{{ route('games.roster.update', [$game, $a]) }}" class="inline">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="is_pitcher" value="{{ $a->pivot->is_pitcher ? '0' : '1' }}">
                                                    <button type="submit" title="{{ $a->pivot->is_pitcher ? __('Quitar como pitcher') : __('Marcar como pitcher') }}"
                                                            class="px-2 py-1 text-xs rounded {{ $a->pivot->is_pitcher ? 'bg-yellow-300 text-yellow-900 font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                                        {{ $a->pivot->is_pitcher ? '★' : '☆' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="px-3 py-2 text-center text-xs">
                                                <form method="POST" action="{{ route('games.roster.update', [$game, $a]) }}" class="inline">
                                                    @csrf @method('PATCH')
                                                    <input type="number" name="pitches_thrown" value="{{ $a->pivot->pitches_thrown }}" min="0" max="999"
                                                           class="w-14 text-center text-xs border-gray-300 rounded"
                                                           onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <button type="button"
                                                        @click="$dispatch('open-substitute-modal', { outAthleteId: {{ $a->id }}, outAthleteName: @js($a->full_name), teamId: {{ $a->pivot->team_id }}, teamName: @js($side['team']->name), available: @js($side['available']->map(fn($x) => ['id' => $x->id, 'name' => $x->full_name, 'number' => $x->number, 'position' => $x->position])->values()), currentLineupOrder: {{ $a->pivot->lineup_order ?? 0 }}, currentPosition: @js($a->pivot->position), isPitcher: {{ $a->pivot->is_pitcher ? 'true' : 'false' }} })"
                                                        class="text-xs text-indigo-600 hover:text-indigo-900 mr-2">
                                                    {{ __('Sustituir') }}
                                                </button>
                                                <form action="{{ route('games.roster.destroy', [$game, $a]) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('¿Quitar a «{{ $a->full_name }}» del roster?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-900">{{ __('Quitar') }}</button>
                                                </form>
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
    </div>

    {{-- Modal: Agregar atleta --}}
    @include('games.roster._add_modal')

    {{-- Modal: Sustituir --}}
    @include('games.roster._substitute_modal')

</x-app-layout>

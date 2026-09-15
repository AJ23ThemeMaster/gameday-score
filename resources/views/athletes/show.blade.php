<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-3">
                @if ($athlete->photoUrl)
                    <img src="{{ $athlete->photoUrl }}" class="h-10 w-10 rounded-full object-cover">
                @endif
                {{ __('Atleta') }}: {{ $athlete->full_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('athletes.index') }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Listado') }}</a>
                <a href="{{ route('athletes.edit', $athlete) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center gap-3 mb-4">
                    @if ($athlete->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('Activo') }}</span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ __('Inactivo') }}</span>
                    @endif
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Cédula') }}</dt><dd class="text-base font-medium text-gray-900">{{ $athlete->document_id ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Fecha de nacimiento') }}</dt><dd class="text-base font-medium text-gray-900">{{ $athlete->birth_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Equipo') }}</dt>
                        <dd class="text-base font-medium text-gray-900">
                            @if ($athlete->team)
                                <a href="{{ route('teams.show', $athlete->team) }}" class="text-indigo-600 hover:text-indigo-800">{{ $athlete->team->name }}</a>
                                @if ($athlete->team->league)
                                    <span class="text-gray-400 mx-1">·</span>
                                    <a href="{{ route('leagues.show', $athlete->team->league) }}" class="text-sm text-gray-600 hover:text-indigo-600">{{ $athlete->team->league->name }}</a>
                                @endif
                            @else —
                            @endif
                        </dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Categoría') }}</dt>
                        <dd class="text-base font-medium text-gray-900">
                            @if ($athlete->category)
                                <a href="{{ route('categories.show', $athlete->category) }}" class="text-indigo-600 hover:text-indigo-800">{{ $athlete->category->name }}</a>
                            @else —
                            @endif
                        </dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Número') }}</dt><dd class="text-2xl font-semibold text-gray-900">{{ $athlete->number ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Posición') }}</dt><dd class="text-2xl font-semibold text-gray-900">{{ $athlete->position ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Batea / Lanza') }}</dt><dd class="text-lg font-semibold text-gray-900">{{ $athlete->bats }} / {{ $athlete->throws }}</dd></div>
                </dl>
                <div class="mt-6 pt-6 border-t border-gray-200 text-xs text-gray-500">
                    {{ __('Creado') }}: {{ $athlete->created_at->format('d/m/Y H:i') }} · {{ __('Actualizado') }}: {{ $athlete->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- DISI-62: stats carrera (totales acumulados) --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <span class="inline-block w-2 h-2 bg-amber-500 rounded-full"></span>
                        {{ __('Estadísticas como bateador') }}
                    </h3>
                    @if ($careerBatting['at_bats'] > 0 || $careerBatting['hits'] > 0)
                        <dl class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm">
                            <div class="bg-amber-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">AB</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ $careerBatting['at_bats'] }}</dd>
                            </div>
                            <div class="bg-amber-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">H</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ $careerBatting['hits'] }}</dd>
                            </div>
                            <div class="bg-amber-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">AVG</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ number_format($careerBatting['avg'], 3, '.', '') }}</dd>
                            </div>
                            <div class="bg-amber-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">BB</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ $careerBatting['walks'] }}</dd>
                            </div>
                            <div class="bg-amber-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">K</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ $careerBatting['strikeouts'] }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 italic">{{ __('Aún no tiene turnos al bate registrados.') }}</p>
                    @endif
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <span class="inline-block w-2 h-2 bg-indigo-500 rounded-full"></span>
                        {{ __('Estadísticas como lanzador') }}
                    </h3>
                    @if ($careerPitching['pitches'] > 0 || $careerPitching['strikeouts'] > 0)
                        <dl class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm">
                            <div class="bg-indigo-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">{{ __('lanz.') }}</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ $careerPitching['pitches'] }}</dd>
                            </div>
                            <div class="bg-indigo-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">S/B</dt>
                                <dd class="text-sm font-bold text-gray-900">{{ $careerPitching['strikes'] }}S / {{ $careerPitching['balls'] }}B</dd>
                            </div>
                            <div class="bg-indigo-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">K</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ $careerPitching['strikeouts'] }}</dd>
                            </div>
                            <div class="bg-indigo-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">H</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ $careerPitching['hits'] }}</dd>
                            </div>
                            <div class="bg-indigo-50 rounded p-2">
                                <dt class="text-gray-500 text-[10px] uppercase">BB</dt>
                                <dd class="text-xl font-bold text-gray-900">{{ $careerPitching['walks'] }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 italic">{{ __('Aún no tiene lanzamientos registrados.') }}</p>
                    @endif
                </div>
            </div>

            {{-- DISI-62: stats per game --}}
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-5 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">{{ __('Estadísticas por juego') }}</h3>
                    <span class="text-sm text-gray-500">{{ $games->count() }} {{ \Illuminate\Support\Str::plural('juego', $games->count()) }}</span>
                </div>
                @if ($perGame)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('Fecha') }}</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('Oponente') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-amber-600 uppercase tracking-wider" colspan="5">{{ __('Bateo') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-indigo-600 uppercase tracking-wider" colspan="5">{{ __('Pitcheo') }}</th>
                                </tr>
                                <tr class="bg-gray-50">
                                    <th class="px-3 py-1"></th>
                                    <th class="px-3 py-1"></th>
                                    <th class="px-3 py-1 text-center text-[10px] text-gray-500">AB</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-gray-500">H</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-gray-500">AVG</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-gray-500">BB</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-gray-500">K</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-indigo-500">{{ __('lanz.') }}</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-indigo-500">S/B</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-indigo-500">K</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-indigo-500">H</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-indigo-500">BB</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($perGame as $row)
                                    @php
                                        $b = $row['batting'];
                                        $p = $row['pitching'];
                                        $g = $row['game'];
                                        $opponent = $g->home_team_id === $athlete->team_id ? $g->awayTeam : $g->homeTeam;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ $g->scheduled_at?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-gray-900">
                                            <a href="{{ route('games.scoreboard', $g) }}" class="hover:text-indigo-600">
                                                @if ($g->home_team_id === $athlete->team_id)
                                                    <span class="text-gray-400 text-xs">vs </span>{{ $opponent->short_name ?? $opponent->name }}
                                                @else
                                                    <span class="text-gray-400 text-xs">@ </span>{{ $opponent->short_name ?? $opponent->name }}
                                                @endif
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono">{{ $b['at_bats'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono">{{ $b['hits'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono">{{ $b['at_bats'] > 0 ? number_format($b['hits'] / max(1, $b['at_bats']), 3, '.', '') : '.000' }}</td>
                                        <td class="px-3 py-2 text-center font-mono">{{ $b['walks'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono">{{ $b['strikeouts'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-indigo-700">{{ $p['pitches'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-indigo-700">{{ $p['strikes'] }}S/{{ $p['balls'] }}B</td>
                                        <td class="px-3 py-2 text-center font-mono text-indigo-700">{{ $p['strikeouts'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-indigo-700">{{ $p['hits'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-indigo-700">{{ $p['walks'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="px-5 py-2 text-[11px] text-gray-500 italic border-t border-gray-200">
                        {{ __('AVG por juego se calcula como H/AB del propio juego. S/B: strikes/balls lanzados.') }}
                    </p>
                @else
                    <p class="p-6 text-gray-500 text-sm">{{ __('Este atleta aún no ha participado en juegos registrados.') }}</p>
                @endif
            </div>

            <form action="{{ route('athletes.destroy', $athlete) }}" method="POST" class="mt-4 text-right" onsubmit="return confirm('¿Eliminar a «{{ $athlete->full_name }}»?');">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Eliminar atleta') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>

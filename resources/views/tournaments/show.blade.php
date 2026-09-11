<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                @if ($tournament->logo_url)
                    <img src="{{ $tournament->logo_url }}" alt="{{ $tournament->name }}" class="h-10 w-10 object-contain bg-white rounded p-0.5">
                @endif
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $tournament->name }}
                </h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('tournaments.edit', $tournament) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                    {{ __('Editar') }}
                </a>
                <a href="{{ route('tournaments.index') }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Liga') }}</p>
                        <p class="font-medium">
                            <a href="{{ route('leagues.show', $tournament->league) }}" class="text-indigo-600 hover:text-indigo-800">
                                @if ($tournament->league->logo_url)
                                    <img src="{{ $tournament->league->logo_url }}" alt="" class="h-5 w-5 inline-block object-contain mr-1 align-middle">
                                @endif
                                {{ $tournament->league->name }}
                            </a>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Categoría') }}</p>
                        <p class="font-medium">{{ $tournament->category ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Temporada') }}</p>
                        <p class="font-medium">{{ $tournament->season ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Inicio') }}</p>
                        <p class="font-medium">{{ $tournament->starts_at?->format('Y-m-d') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Fin') }}</p>
                        <p class="font-medium">{{ $tournament->ends_at?->format('Y-m-d') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Estado') }}</p>
                        <p class="font-medium">
                            @if ($tournament->active)
                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('Activo') }}</span>
                            @else
                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ __('Inactivo') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="md:col-span-3">
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Descripción') }}</p>
                        <p class="text-gray-700">{{ $tournament->description ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Juegos asociados') }}</p>
                        <p class="font-medium">{{ $tournament->games_count }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold">{{ __('Juegos del torneo') }}</h3>
                </div>
                @if ($tournament->games->isEmpty())
                    <p class="p-6 text-gray-500 text-sm">{{ __('Este torneo aún no tiene juegos registrados.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Fecha') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Local') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Score') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Visitante') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($tournament->games as $g)
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ $g->scheduled_at?->format('Y-m-d H:i') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $g->homeTeam->short_name ?? $g->homeTeam->name }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-center text-sm font-bold">
                                        <a href="{{ route('games.scoreboard', $g) }}" class="text-indigo-600 hover:text-indigo-800">
                                            {{ $g->home_score ?? 0 }} - {{ $g->away_score ?? 0 }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $g->awayTeam->short_name ?? $g->awayTeam->name }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-center text-sm">{{ $g->status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

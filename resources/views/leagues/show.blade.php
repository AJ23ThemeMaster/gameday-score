<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                @if ($league->logo_url)
                    <img src="{{ $league->logo_url }}" alt="{{ $league->name }}" class="h-10 w-10 object-contain bg-white rounded p-0.5">
                @endif
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $league->name }}
                </h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('leagues.edit', $league) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                    {{ __('Editar') }}
                </a>
                <a href="{{ route('leagues.index') }}"
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
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Nombre corto') }}</p>
                        <p class="font-medium">{{ $league->short_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('País') }}</p>
                        <p class="font-medium">{{ $league->country ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Estado') }}</p>
                        <p class="font-medium">
                            @if ($league->active)
                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('Activa') }}</span>
                            @else
                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ __('Inactiva') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="md:col-span-3">
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Descripción') }}</p>
                        <p class="text-gray-700">{{ $league->description ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Torneos asociados') }}</p>
                        <p class="font-medium">{{ $league->tournaments_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Equipos asociados') }}</p>
                        <p class="font-medium">{{ $teams->count() }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Atletas asociados') }}</p>
                        <p class="font-medium">{{ $athletes->count() }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">{{ __('Juegos asociados') }}</p>
                        <p class="font-medium">{{ $league->games_count }}</p>
                    </div>
                </div>
            </div>

            {{-- DISI-58: Equipos de la liga --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold">{{ __('Equipos de la liga') }}</h3>
                    <a href="{{ route('teams.create', ['league_id' => $league->id]) }}"
                       class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">
                        + {{ __('Nuevo equipo') }}
                    </a>
                </div>
                @if ($teams->isEmpty())
                    <p class="p-6 text-gray-500 text-sm">{{ __('Esta liga aún no tiene equipos registrados.') }}</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-6">
                        @foreach ($teams as $t)
                            <a href="{{ route('teams.show', $t) }}"
                               class="flex items-center gap-3 p-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 transition">
                                @if ($t->logo_url)
                                    <img src="{{ $t->logo_url }}" alt="" class="h-10 w-10 object-contain bg-white rounded p-0.5">
                                @else
                                    <div class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center text-gray-500 font-bold text-sm">
                                        {{ mb_strtoupper(mb_substr($t->short_name ?? $t->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 truncate">{{ $t->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $t->categories_count }} {{ \Illuminate\Support\Str::plural('categoría', $t->categories_count) }}
                                        · {{ $t->athletes_count }} {{ \Illuminate\Support\Str::plural('atleta', $t->athletes_count) }}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold">{{ __('Torneos de la liga') }}</h3>
                    <a href="{{ route('tournaments.create', ['league_id' => $league->id]) }}"
                       class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">
                        + {{ __('Nuevo torneo') }}
                    </a>
                </div>
                @if ($tournaments->isEmpty())
                    <p class="p-6 text-gray-500 text-sm">{{ __('Esta liga aún no tiene torneos registrados.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Nombre') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Categoría') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Temporada') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Juegos') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($tournaments as $t)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('tournaments.show', $t) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ $t->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $t->category ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $t->season ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $t->games_count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('tournaments.edit', $t) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Editar') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- DISI-58: Atletas de la liga (los de los equipos) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold">{{ __('Atletas de la liga') }}</h3>
                    <span class="text-sm text-gray-500">{{ $athletes->count() }} {{ \Illuminate\Support\Str::plural('atleta', $athletes->count()) }}</span>
                </div>
                @if ($athletes->isEmpty())
                    <p class="p-6 text-gray-500 text-sm">{{ __('Esta liga aún no tiene atletas registrados (a traves de sus equipos).') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('#') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Atleta') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Equipo') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($athletes as $a)
                                    <tr>
                                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700">{{ $a->number ?? '—' }}</td>
                                        <td class="px-6 py-2 whitespace-nowrap text-sm">
                                            <a href="{{ route('athletes.show', $a) }}" class="text-indigo-600 hover:text-indigo-800">
                                                {{ $a->full_name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700">
                                            <a href="{{ route('teams.show', $a->team) }}" class="hover:text-indigo-600">
                                                {{ $a->team->short_name ?? $a->team->name }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

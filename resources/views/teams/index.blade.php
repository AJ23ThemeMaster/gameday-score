<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Equipos') }}
            </h2>
            <a href="{{ route('teams.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                + {{ __('Nuevo equipo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($teams->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">{{ __('Aún no hay equipos registrados.') }}</p>
                        <a href="{{ route('teams.create') }}"
                           class="text-indigo-600 hover:text-indigo-800 underline">
                            {{ __('Crear el primer equipo') }}
                        </a>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Logo') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Nombre / Ciudad') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Liga') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Atletas') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Juegos') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($teams as $team)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($team->logoUrl)
                                            <img src="{{ $team->logoUrl }}" alt="{{ $team->name }}"
                                                 class="h-12 w-12 object-contain bg-gray-50 rounded border border-gray-200 p-1">
                                        @else
                                            <div class="h-12 w-12 rounded border border-gray-200 bg-gray-100 flex items-center justify-center text-gray-400 text-xs">
                                                {{ __('Sin logo') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('teams.show', $team) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ $team->name }}
                                        </a>
                                        @if ($team->city)
                                            <p class="text-xs text-gray-500">{{ $team->city }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        @if ($team->league)
                                            <a href="{{ route('leagues.show', $team->league) }}" class="text-indigo-600 hover:text-indigo-800">
                                                {{ $team->league->short_name ?? $team->league->name }}
                                            </a>
                                        @else
                                            <span class="text-gray-400 italic">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                        {{ $team->athletes_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                        {{ $team->home_games_count + $team->away_games_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if ($team->active)
                                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ __('Activo') }}
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                {{ __('Inactivo') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('teams.edit', $team) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            {{ __('Editar') }}
                                        </a>
                                        <form action="{{ route('teams.destroy', $team) }}" method="POST" class="inline"
                                              onsubmit="return confirm('¿Eliminar el equipo «{{ $team->name }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                {{ __('Eliminar') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $teams->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

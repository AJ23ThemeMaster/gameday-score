<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Torneos') }}
            </h2>
            <a href="{{ route('tournaments.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                + {{ __('Nuevo torneo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($tournaments->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">{{ __('Aún no hay torneos registrados.') }}</p>
                        <a href="{{ route('tournaments.create') }}"
                           class="text-indigo-600 hover:text-indigo-800 underline">
                            {{ __('Crear el primer torneo') }}
                        </a>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Torneo') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Liga') }}</th>
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
                                        <div class="flex items-center gap-2">
                                            @if ($t->logo_url)
                                                <img src="{{ $t->logo_url }}" alt="{{ $t->name }}" class="h-8 w-8 object-contain bg-white rounded p-0.5">
                                            @endif
                                            <a href="{{ route('tournaments.show', $t) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                                {{ $t->name }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <a href="{{ route('leagues.show', $t->league) }}" class="text-indigo-600 hover:text-indigo-800">
                                            {{ $t->league->short_name ?? $t->league->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $t->category ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $t->season ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $t->games_count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('tournaments.edit', $t) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('Editar') }}</a>
                                        <form action="{{ route('tournaments.destroy', $t) }}" method="POST" class="inline"
                                              onsubmit="return confirm('¿Eliminar el torneo «{{ $t->name }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $tournaments->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Mis juegos') }}</h2>
            <a href="{{ route('games.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">+ {{ __('Nuevo juego') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
                @php
                    $statLabels = ['total' => __('Total'), 'scheduled' => __('Programados'), 'in_progress' => __('En vivo'), 'completed' => __('Finalizados'), 'public' => __('Públicos')];
                @endphp
                @foreach ($statLabels as $key => $label)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ $stats[$key] ?? 0 }}</div>
                        <div class="text-xs text-gray-500 uppercase">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($games->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">{{ __('Aún no has creado juegos.') }}</p>
                        <a href="{{ route('games.create') }}" class="text-indigo-600 hover:text-indigo-800 underline">{{ __('Crear el primer juego') }}</a>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Fecha') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Enfrentamiento') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Categoría / Estadio') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Público') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($games as $g)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-gray-900">
                                        <div class="font-medium">{{ $g->scheduled_at->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $g->scheduled_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <a href="{{ route('games.show', $g) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ $g->homeTeam->short_name ?? $g->homeTeam->name }}
                                            <span class="text-gray-400 mx-1">vs</span>
                                            {{ $g->awayTeam->short_name ?? $g->awayTeam->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 text-xs text-gray-500">
                                        <div>{{ $g->category->name ?? '—' }}</div>
                                        <div>{{ $g->stadium->name ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        @php
                                            $statusColors = ['scheduled' => 'bg-blue-100 text-blue-800', 'in_progress' => 'bg-red-100 text-red-800', 'paused' => 'bg-yellow-100 text-yellow-800', 'completed' => 'bg-green-100 text-green-800', 'suspended' => 'bg-gray-200 text-gray-800', 'cancelled' => 'bg-gray-100 text-gray-500'];
                                            $statusLabels = ['scheduled' => 'Programado', 'in_progress' => 'En vivo', 'paused' => 'Pausado', 'completed' => 'Finalizado', 'suspended' => 'Suspendido', 'cancelled' => 'Cancelado'];
                                        @endphp
                                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full {{ $statusColors[$g->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ __($statusLabels[$g->status] ?? $g->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($g->is_public)
                                            <span class="inline-flex items-center text-green-600" title="{{ __('Juego público') }}">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                            </span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-medium">
                                        <a href="{{ route('games.edit', $g) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('Editar') }}</a>
                                        <form action="{{ route('games.destroy', $g) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar el juego?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-3 border-t border-gray-200">{{ $games->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

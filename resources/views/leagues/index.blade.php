<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ligas') }}
            </h2>
            <a href="{{ route('leagues.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                + {{ __('Nueva liga') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($leagues->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">{{ __('Aún no hay ligas registradas.') }}</p>
                        <a href="{{ route('leagues.create') }}"
                           class="text-indigo-600 hover:text-indigo-800 underline">
                            {{ __('Crear la primera liga') }}
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                        @foreach ($leagues as $league)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-400 hover:shadow transition">
                                <div class="flex items-start gap-3">
                                    @if ($league->logo_url)
                                        <img src="{{ $league->logo_url }}" alt="{{ $league->name }}"
                                             class="h-14 w-14 object-contain flex-shrink-0 bg-white rounded p-1">
                                    @else
                                        <div class="h-14 w-14 bg-gray-100 rounded flex items-center justify-center text-gray-400 text-xs flex-shrink-0">
                                            LOGO
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('leagues.show', $league) }}"
                                           class="text-base font-bold text-gray-900 hover:text-indigo-700 block truncate">
                                            {{ $league->name }}
                                        </a>
                                        @if ($league->short_name)
                                            <p class="text-xs text-gray-500">{{ $league->short_name }}@if ($league->country) · {{ $league->country }}@endif</p>
                                        @endif
                                        <div class="mt-2 flex gap-2 text-xs">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 text-indigo-700">
                                                {{ $league->tournaments_count }} {{ __('torneos') }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-emerald-50 text-emerald-700">
                                                {{ $league->games_count }} {{ __('juegos') }}
                                            </span>
                                            @if ($league->active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800 font-semibold">
                                                    {{ __('Activa') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-semibold">
                                                    {{ __('Inactiva') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex justify-end gap-2 text-sm">
                                    <a href="{{ route('leagues.edit', $league) }}"
                                       class="text-indigo-600 hover:text-indigo-900 font-medium">
                                        {{ __('Editar') }}
                                    </a>
                                    <form action="{{ route('leagues.destroy', $league) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Eliminar la liga «{{ $league->name }}»? Si tiene torneos o juegos asociados, no se podrá eliminar.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-medium">
                                            {{ __('Eliminar') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $leagues->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

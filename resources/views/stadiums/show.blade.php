<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Estadio') }}: {{ $stadium->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('stadiums.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                    {{ __('Listado') }}
                </a>
                <a href="{{ route('stadiums.edit', $stadium) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md">
                    {{ __('Editar') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex items-center gap-3 mb-4">
                    @if ($stadium->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ __('Activo') }}
                        </span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            {{ __('Inactivo') }}
                        </span>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Ciudad') }}</dt>
                        <dd class="text-base font-medium text-gray-900">{{ $stadium->city ?? '—' }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Estado / Provincia') }}</dt>
                        <dd class="text-base font-medium text-gray-900">{{ $stadium->state ?? '—' }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3 sm:col-span-2">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Dirección') }}</dt>
                        <dd class="text-base font-medium text-gray-900">{{ $stadium->address ?? '—' }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Capacidad') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">
                            {{ $stadium->capacity ? number_format($stadium->capacity, 0, ',', '.') : '—' }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Juegos asociados') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $stadium->games_count }}</dd>
                    </div>
                </dl>

                @if ($stadium->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Notas') }}</h4>
                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ $stadium->notes }}</p>
                    </div>
                @endif

                <div class="mt-6 pt-6 border-t border-gray-200 text-xs text-gray-500">
                    {{ __('Creado') }}: {{ $stadium->created_at->format('d/m/Y H:i') }} ·
                    {{ __('Actualizado') }}: {{ $stadium->updated_at->format('d/m/Y H:i') }}
                </div>

            </div>

            <form action="{{ route('stadiums.destroy', $stadium) }}" method="POST" class="mt-4 text-right"
                  onsubmit="return confirm('¿Eliminar el estadio «{{ $stadium->name }}»?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                    {{ __('Eliminar estadio') }}
                </button>
            </form>

        </div>
    </div>
</x-app-layout>

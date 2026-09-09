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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
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
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Equipo') }}</dt><dd class="text-base font-medium text-gray-900">{{ $athlete->team->name ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Número') }}</dt><dd class="text-2xl font-semibold text-gray-900">{{ $athlete->number ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Posición') }}</dt><dd class="text-2xl font-semibold text-gray-900">{{ $athlete->position ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Batea / Lanza') }}</dt><dd class="text-lg font-semibold text-gray-900">{{ $athlete->bats }} / {{ $athlete->throws }}</dd></div>
                </dl>
                <div class="mt-6 pt-6 border-t border-gray-200 text-xs text-gray-500">
                    {{ __('Creado') }}: {{ $athlete->created_at->format('d/m/Y H:i') }} · {{ __('Actualizado') }}: {{ $athlete->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>
            <form action="{{ route('athletes.destroy', $athlete) }}" method="POST" class="mt-4 text-right" onsubmit="return confirm('¿Eliminar a «{{ $athlete->full_name }}»?');">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Eliminar atleta') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>

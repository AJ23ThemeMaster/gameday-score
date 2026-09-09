<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Editar árbitro') }}: {{ $referee->full_name }}</h2></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-between">
            <a href="{{ route('referees.show', $referee) }}" class="text-sm text-indigo-600 hover:text-indigo-800">← {{ __('Volver al detalle') }}</a>
            <a href="{{ route('referees.index') }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Listado') }}</a>
        </div>
        @include('partials._flash')
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form method="POST" action="{{ route('referees.update', $referee) }}" enctype="multipart/form-data" class="p-6">
                @csrf @method('PUT')
                @include('referees._form')
                <div class="flex justify-end mt-6 gap-3">
                    <a href="{{ route('referees.show', $referee) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Cancelar') }}</a>
                    <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div></div>
</x-app-layout>

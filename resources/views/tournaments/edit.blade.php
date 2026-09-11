<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar torneo') }}: {{ $tournament->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4 flex gap-3 text-sm">
                <a href="{{ route('tournaments.index') }}" class="text-indigo-600 hover:text-indigo-800">
                    ← {{ __('Volver al listado') }}
                </a>
                <a href="{{ route('tournaments.show', $tournament) }}" class="text-indigo-600 hover:text-indigo-800">
                    {{ __('Ver detalle') }}
                </a>
            </div>

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('tournaments.update', $tournament) }}" enctype="multipart/form-data" class="p-6">
                    @csrf
                    @method('PUT')
                    @include('tournaments._form')

                    <div class="flex justify-end mt-6 gap-3">
                        <a href="{{ route('tournaments.show', $tournament) }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('Cancelar') }}
                        </a>
                        <x-primary-button>
                            {{ __('Guardar cambios') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>

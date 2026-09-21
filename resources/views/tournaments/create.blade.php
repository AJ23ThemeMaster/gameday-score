<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                {{ __('Nuevo torneo') }}
            </h2>
            <p class="text-sm text-wv-text-secondary mt-1">{{ __('Crea un torneo y asignale una liga.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4">
                <a href="{{ route('tournaments.index') }}" class="text-sm text-wv-accent hover:text-wv-accent-hover">
                    ← {{ __('Volver al listado') }}
                </a>
            </div>

            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card">
                <form method="POST" action="{{ route('tournaments.store') }}" enctype="multipart/form-data" class="p-6">
                    @csrf
                    @include('tournaments._form')

                    <div class="flex justify-end mt-6 gap-3">
                        <a href="{{ route('tournaments.index') }}"
                           class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">
                            {{ __('Cancelar') }}
                        </a>
                        <x-primary-button>
                            {{ __('Crear torneo') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
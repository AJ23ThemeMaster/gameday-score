<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Nuevo entrenador') }}: {{ $team->name }}</h2>
            </div>
            <a href="{{ route('teams.coaches.index', $team) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">← {{ __('Volver') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card">
                <form method="POST" action="{{ route('teams.coaches.store', $team) }}" class="p-6" data-loader>
                    @csrf
                    @include('coaches._form')
                    <div class="flex justify-end mt-6 gap-3">
                        <a href="{{ route('teams.coaches.index', $team) }}" class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">{{ __('Cancelar') }}</a>
                        <x-primary-button>{{ __('Crear entrenador') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

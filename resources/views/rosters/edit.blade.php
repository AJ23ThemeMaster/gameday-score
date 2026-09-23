<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Editar roster') }}: {{ $roster->full_name }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ $team->name }}</p>
            </div>
            <a href="{{ route('teams.rosters.show', [$team, $roster]) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">← {{ __('Volver') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card">
                <form method="POST" action="{{ route('teams.rosters.update', [$team, $roster]) }}" class="p-6" data-loader>
                    @csrf
                    @method('PUT')
                    @include('rosters._form')
                    <div class="flex justify-end mt-6 gap-3">
                        <a href="{{ route('teams.rosters.show', [$team, $roster]) }}" class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">{{ __('Cancelar') }}</a>
                        <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

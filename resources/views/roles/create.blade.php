<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Nuevo rol') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Crea un nuevo rol con sus permisos.') }}</p>
            </div>
            <a href="{{ route('roles.index') }}" class="text-sm text-wv-text-secondary hover:text-wv-text">← {{ __('Volver al listado') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card">
                <form method="POST" action="{{ route('roles.store') }}" class="p-6">
                    @csrf @include('roles._form')
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('roles.index') }}" class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">{{ __('Cancelar') }}</a>
                        <x-primary-button>{{ __('Crear rol') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
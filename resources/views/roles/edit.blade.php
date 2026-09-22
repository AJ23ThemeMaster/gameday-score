<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                    {{ __('Editar rol') }}: <span class="text-wv-accent">{{ $role->name }}</span>
                </h2>
                <p class="text-sm text-wv-text-secondary mt-1"><code>{{ $role->name }}</code></p>
            </div>
            <a href="{{ route('roles.show', $role) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">← {{ __('Volver al detalle') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card">
                <form method="POST" action="{{ route('roles.update', $role) }}" class="p-6">
                    @csrf @method('PUT') @include('roles._form')
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('roles.show', $role) }}" class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">{{ __('Cancelar') }}</a>
                        <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
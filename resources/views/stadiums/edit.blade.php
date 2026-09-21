<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Editar estadio') }}: {{ $stadium->name }}</h2></x-slot>
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-between">
                <a href="{{ route('stadiums.show', $stadium) }}" class="text-sm text-wv-accent hover:text-wv-accent-hover">← {{ __('Volver al detalle') }}</a>
                <a href="{{ route('stadiums.index') }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Listado') }}</a>
            </div>
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card">
                <form method="POST" action="{{ route('stadiums.update', $stadium) }}" class="p-6">
                    @csrf @method('PUT') @include('stadiums._form')
                    <div class="flex justify-end mt-6 gap-3">
                        <a href="{{ route('stadiums.show', $stadium) }}" class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">{{ __('Cancelar') }}</a>
                        <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
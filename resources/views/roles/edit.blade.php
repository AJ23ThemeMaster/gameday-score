<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Editar rol') }}: <span class="text-indigo-700">{{ $role->name }}</span>
            </h2>
            <a href="{{ route('roles.show', $role) }}"
               class="text-sm text-gray-600 hover:text-gray-900 underline">
                {{ __('← Volver al detalle') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('roles.update', $role) }}">
                    @csrf
                    @method('PUT')
                    @include('roles._form')
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('roles.show', $role) }}"
                           class="text-sm text-gray-600 hover:text-gray-900 underline">
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

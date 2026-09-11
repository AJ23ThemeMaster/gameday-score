<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Nuevo rol') }}
            </h2>
            <a href="{{ route('roles.index') }}"
               class="text-sm text-gray-600 hover:text-gray-900 underline">
                {{ __('← Volver al listado') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('roles.store') }}">
                    @csrf
                    @include('roles._form')
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('roles.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900 underline">
                            {{ __('Cancelar') }}
                        </a>
                        <x-primary-button>
                            {{ __('Crear rol') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>

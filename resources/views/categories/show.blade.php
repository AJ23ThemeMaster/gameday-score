<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Categoría') }}: {{ $category->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('categories.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                    {{ __('Listado') }}
                </a>
                <a href="{{ route('categories.edit', $category) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md">
                    {{ __('Editar') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex items-center gap-3 mb-4">
                    @if ($category->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ __('Activa') }}
                        </span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            {{ __('Inactiva') }}
                        </span>
                    @endif
                    <code class="text-sm text-gray-500">{{ $category->slug }}</code>
                </div>

                @if ($category->description)
                    <p class="text-gray-700 mb-6">{{ $category->description }}</p>
                @endif

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Innings por juego') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $category->innings_count }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Límite de lanzamientos') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">
                            {{ $category->pitch_limit ?? '—' }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Regla del Nocaut') }}</dt>
                        <dd class="text-lg font-semibold text-gray-900">
                            -{{ $category->mercy_rule_difference }} desde el inning {{ $category->mercy_rule_inning }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Juegos asociados') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $category->games_count }}</dd>
                    </div>
                </dl>

                <div class="mt-6 pt-6 border-t border-gray-200 text-xs text-gray-500">
                    {{ __('Creada') }}: {{ $category->created_at->format('d/m/Y H:i') }} ·
                    {{ __('Actualizada') }}: {{ $category->updated_at->format('d/m/Y H:i') }}
                </div>

            </div>

            <form action="{{ route('categories.destroy', $category) }}" method="POST" class="mt-4 text-right"
                  onsubmit="return confirm('¿Eliminar la categoría «{{ $category->name }}»?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                    {{ __('Eliminar categoría') }}
                </button>
            </form>

        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Anotadores') }}</h2>
            <a href="{{ route('scorekeepers.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">+ {{ __('Nuevo anotador') }}</a>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($scorekeepers->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">{{ __('Aún no hay anotadores registrados.') }}</p>
                        <a href="{{ route('scorekeepers.create') }}" class="text-indigo-600 hover:text-indigo-800 underline">{{ __('Registrar el primer anotador') }}</a>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Foto') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Nombre') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Contacto') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($scorekeepers as $sk)
                                <tr>
                                    <td class="px-6 py-3">
                                        @if ($sk->photoUrl)
                                            <img src="{{ $sk->photoUrl }}" class="h-10 w-10 rounded-full object-cover">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-semibold">{{ mb_substr($sk->first_name, 0, 1) }}{{ mb_substr($sk->last_name, 0, 1) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        <a href="{{ route('scorekeepers.show', $sk) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $sk->full_name }}</a>
                                        @if ($sk->document_id)<p class="text-xs text-gray-500">{{ $sk->document_id }}</p>@endif
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-600">
                                        @if ($sk->email)<div>{{ $sk->email }}</div>@endif
                                        @if ($sk->phone)<div class="text-xs text-gray-500">{{ $sk->phone }}</div>@endif
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($sk->active)
                                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('Activo') }}</span>
                                        @else
                                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ __('Inactivo') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-medium">
                                        <a href="{{ route('scorekeepers.edit', $sk) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('Editar') }}</a>
                                        <form action="{{ route('scorekeepers.destroy', $sk) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar a «{{ $sk->full_name }}»?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-3 border-t border-gray-200">{{ $scorekeepers->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Roles y permisos') }}
            </h2>
            <a href="{{ route('roles.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                + {{ __('Nuevo rol') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($roles->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">{{ __('Aún no hay roles personalizados.') }}</p>
                        <a href="{{ route('roles.create') }}"
                           class="text-indigo-600 hover:text-indigo-800 underline">
                            {{ __('Crear el primer rol') }}
                        </a>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    {{ __('Rol') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    {{ __('Permisos') }}
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    {{ __('Usuarios') }}
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    {{ __('Acciones') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($roles as $role)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('roles.show', $role) }}"
                                           class="font-semibold text-indigo-700 hover:text-indigo-900">
                                            {{ $role->name }}
                                        </a>
                                        @if (in_array($role->name, ['admin', 'anotador'], true))
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700">
                                                {{ __('Sistema') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $role->permissions->count() }} {{ __('permiso(s)') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold">
                                            {{ $role->users_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('roles.edit', $role) }}"
                                           class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">
                                            {{ __('Editar') }}
                                        </a>
                                        @if (! in_array($role->name, ['admin', 'anotador'], true))
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('¿Eliminar el rol «{{ $role->name }}»?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-medium">
                                                    {{ __('Eliminar') }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $roles->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

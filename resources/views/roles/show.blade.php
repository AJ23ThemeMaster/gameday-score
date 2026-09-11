<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Rol') }}: <span class="text-indigo-700">{{ $role->name }}</span>
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('roles.index') }}"
                   class="text-sm text-gray-600 hover:text-gray-900 underline">
                    {{ __('← Volver al listado') }}
                </a>
                <a href="{{ route('roles.edit', $role) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md transition">
                    {{ __('Editar') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('partials._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('Permisos asignados') }}</h3>
                @if ($role->permissions->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('Este rol no tiene permisos asignados.') }}</p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($role->permissions as $permission)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('Usuarios con este rol') }}</h3>
                @if ($users->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('Ningún usuario tiene este rol asignado.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                                    {{ __('Nombre') }}
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                                    {{ __('Correo electrónico') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-4 py-2 text-sm">
                                        <a href="{{ route('users.edit', $user) }}" class="text-indigo-700 hover:text-indigo-900 font-medium">
                                            {{ $user->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ $user->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

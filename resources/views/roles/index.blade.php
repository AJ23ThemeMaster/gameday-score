<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Roles') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Roles del sistema y sus permisos asignados.') }}</p>
            </div>
            <a href="{{ route('roles.create') }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                + {{ __('Nuevo rol') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($roles->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        <p class="mb-4">{{ __('AÃƒÂºn no hay roles registrados.') }}</p>
                        <a href="{{ route('roles.create') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Crear el primer rol') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Identificador') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Permisos') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Usuarios') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($roles as $role)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('roles.show', $role) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $role->name }}</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">
                                            <code>{{ $role->name }}</code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $role->permissions_count ?? $role->permissions->count() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $role->users_count ?? $role->users->count() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('roles.edit', $role) }}" class="text-wv-accent hover:text-wv-accent-hover mr-3">{{ __('Editar') }}</a>
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline" data-confirm="'¿Eliminar el rol «{{ $role->name }}»? Los usuarios con este rol quedarÃƒ¡n sin rol asignado.'" data-confirm-danger="true" data-loader>
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
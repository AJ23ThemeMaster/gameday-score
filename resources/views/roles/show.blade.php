<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                    {{ __('Rol') }}: <span class="text-wv-accent">{{ $role->display_name ?? $role->name }}</span>
                </h2>
                <p class="text-sm text-wv-text-secondary mt-1"><code>{{ $role->name }}</code></p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('roles.index') }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Listado') }}</a>
                <a href="{{ route('roles.edit', $role) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                @if ($role->description)
                    <p class="text-wv-text-secondary mb-6">{{ $role->description }}</p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-4 text-center">
                        <div class="font-mono text-kpi text-wv-text">{{ $role->permissions->count() }}</div>
                        <div class="text-xs text-wv-text-secondary uppercase mt-1">{{ __('Permisos') }}</div>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-4 text-center">
                        <div class="font-mono text-kpi text-wv-text">{{ $role->users->count() }}</div>
                        <div class="text-xs text-wv-text-secondary uppercase mt-1">{{ __('Usuarios') }}</div>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-4 text-center">
                        <div class="font-mono text-kpi text-wv-text">{{ $role->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-wv-text-secondary uppercase mt-1">{{ __('Creado') }}</div>
                    </div>
                </div>

                <h3 class="text-sm font-bold text-wv-text mb-3">{{ __('Permisos asignados') }}</h3>
                @if ($role->permissions->isEmpty())
                    <p class="text-sm text-wv-text-secondary italic">{{ __('Este rol no tiene permisos asignados aún.') }}</p>
                @else
                    @php $grouped = $role->permissions->groupBy('group'); @endphp
                    <div class="space-y-4">
                        @foreach ($grouped as $groupName => $perms)
                            <div>
                                <p class="text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ ucfirst($groupName) }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($perms as $perm)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-wv-accent-soft text-wv-accent border border-wv-accent/40">
                                            {{ $perm->display_name ?? $perm->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($role->users->count())
                <div class="mt-4 bg-wv-surface border border-wv-border rounded-card p-6">
                    <h3 class="text-sm font-bold text-wv-text mb-3">{{ __('Usuarios con este rol') }} ({{ $role->users->count() }})</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border text-sm">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase">{{ __('Nombre') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase">{{ __('Correo') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-wv-text-secondary uppercase">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($role->users as $u)
                                    <tr class="hover:bg-wv-surface-hover">
                                        <td class="px-3 py-2">
                                            <a href="{{ route('users.edit', $u) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $u->name }}</a>
                                        </td>
                                        <td class="px-3 py-2 text-wv-text-secondary">{{ $u->email }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('users.edit', $u) }}" class="text-xs text-wv-accent hover:text-wv-accent-hover">{{ __('Editar') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="mt-4 text-right" onsubmit="return confirm('¿Eliminar el rol «{{ $role->display_name ?? $role->name }}»?');">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar rol') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
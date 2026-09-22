<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Usuarios') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Usuarios con acceso al sistema y su rol.') }}</p>
            </div>
            {{-- DISI-delegado: registro publico deshabilitado; el admin crea
                 usuarios desde aqui. --}}
            @auth
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('users.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                        <span class="material-symbols-outlined text-[18px]">person_add</span>
                        {{ __('Nuevo usuario') }}
                    </a>
                @endif
            @endauth
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            @php $statLabels = ['total' => __('Total'), 'active' => __('Activos'), 'inactive' => __('Inactivos'), 'admin' => __('Admins')]; @endphp
            @if (! empty($stats))
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    @foreach ($statLabels as $key => $label)
                        <div class="bg-wv-surface border border-wv-border rounded-card p-4 text-center">
                            <div class="font-mono text-kpi text-wv-text">{{ $stats[$key] ?? 0 }}</div>
                            <div class="text-xs text-wv-text-secondary uppercase tracking-wider mt-1">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($users->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        <p>{{ __('AÃºn no hay otros usuarios en el sistema.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Usuario') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Rol') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Equipo') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($users as $u)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                @if ($u->avatarUrl)
                                                    <img src="{{ $u->avatarUrl }}" class="h-9 w-9 rounded-full object-cover bg-wv-surface border border-wv-border p-0.5">
                                                @else
                                                    <div class="h-9 w-9 rounded-full bg-wv-accent-soft text-wv-accent flex items-center justify-center text-xs font-semibold border border-wv-border">
                                                        {{ $u->avatar_initials }}
                                                    </div>
                                                @endif
                                                <div>
                                                    <p class="font-medium text-wv-text">{{ $u->name }}</p>
                                                    <p class="text-xs text-wv-text-secondary">{{ $u->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php $primaryRole = $u->roles->first(); @endphp
                                            @if ($primaryRole)
                                                <a href="{{ route('roles.show', $primaryRole) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $primaryRole->name }}</a>
                                            @else
                                                <span class="text-wv-text-secondary italic">â€”</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">
                                            @if ($u->team)
                                                <a href="{{ route('teams.show', $u->team) }}" class="hover:text-wv-accent">{{ $u->team->short_name ?? $u->team->name }}</a>
                                            @else
                                                <span class="italic">â€”</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if ($u->active ?? true)
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('users.edit', $u) }}" class="text-wv-accent hover:text-wv-accent-hover mr-3">{{ __('Editar') }}</a>
                                            @if ($u->id !== auth()->id())
                                                <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline" data-confirm="'¿Eliminar al usuario «{{ $u->name }}»?'" data-confirm-danger="true" data-loader>
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $users->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
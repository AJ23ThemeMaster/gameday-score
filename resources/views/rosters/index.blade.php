<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Rosters') }}: {{ $team->name }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Plantillas del equipo agrupadas por categoría.') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('teams.show', $team) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Volver al equipo') }}</a>
                <a href="{{ route('teams.rosters.create', $team) }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card">
                    + {{ __('Nuevo roster') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($rosters->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        <p class="mb-4">{{ __('Aún no hay rosters registrados en este equipo.') }}</p>
                        <a href="{{ route('teams.rosters.create', $team) }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Crear el primer roster') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Categoría') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Manager') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Delegado') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Coaches') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Atletas') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($rosters as $r)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-3 text-sm">
                                            @if ($r->category)
                                                <a href="{{ route('categories.show', $r->category) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $r->category->name }}</a>
                                            @else
                                                <span class="text-wv-text-secondary italic">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm text-wv-text">{{ $r->name ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm">
                                            @if ($r->managerCoach)
                                                <a href="{{ route('teams.coaches.show', [$team, $r->managerCoach]) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $r->managerCoach->full_name }}</a>
                                            @else
                                                <span class="text-wv-text-secondary italic">{{ __('Sin asignar') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            @if ($r->delegateUser)
                                                {{ $r->delegateUser->name }}
                                            @else
                                                <span class="text-wv-text-secondary italic">{{ __('Sin asignar') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-center font-mono text-sm text-wv-text">{{ $r->coaches_count }}</td>
                                        <td class="px-6 py-3 text-center font-mono text-sm text-wv-text">{{ $r->athletes_count }}</td>
                                        <td class="px-6 py-3 text-center">
                                            @if ($r->active)
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium">
                                            <x-action-buttons
                                                :show="route('teams.rosters.show', [$team, $r])"
                                                :edit="route('teams.rosters.edit', [$team, $r])"
                                                :delete="route('teams.rosters.destroy', [$team, $r])"
                                                :deleteMessage="__('¿Eliminar el roster «:name»?', ['name' => $r->full_name])"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $rosters->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

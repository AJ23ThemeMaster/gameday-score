<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Entrenadores') }}: {{ $team->name }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Entrenadores registrados en este equipo.') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('teams.show', $team) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Volver al equipo') }}</a>
                <a href="{{ route('teams.coaches.create', $team) }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card">
                    + {{ __('Nuevo entrenador') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($coaches->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        <p class="mb-4">{{ __('Aún no hay entrenadores registrados en este equipo.') }}</p>
                        <a href="{{ route('teams.coaches.create', $team) }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Registrar el primer entrenador') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Rol') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Documento') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Contacto') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($coaches as $c)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-3">
                                            <a href="{{ route('teams.coaches.show', [$team, $c]) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $c->full_name }}</a>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-wv-text-secondary">{{ $c->role ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm text-wv-text-secondary">{{ $c->document_id ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm text-wv-text-secondary">
                                            @if ($c->phone)<p>{{ $c->phone }}</p>@endif
                                            @if ($c->email)<p class="text-xs">{{ $c->email }}</p>@endif
                                            @if (! $c->phone && ! $c->email)<span class="italic">—</span>@endif
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            @if ($c->active)
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium">
                                            <x-action-buttons
                                                :show="route('teams.coaches.show', [$team, $c])"
                                                :edit="route('teams.coaches.edit', [$team, $c])"
                                                :delete="route('teams.coaches.destroy', [$team, $c])"
                                                :deleteMessage="__('¿Eliminar al entrenador «:name»?', ['name' => $c->full_name])"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $coaches->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

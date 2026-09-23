<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Estadios') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Listado de estadios donde se juegan los partidos.') }}</p>
            </div>
            <a href="{{ route('stadiums.create') }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                + {{ __('Nuevo estadio') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($stadiums->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        <p class="mb-4">{{ __('AÃºn no hay estadios registrados.') }}</p>
                        <a href="{{ route('stadiums.create') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Crear el primer estadio') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Ciudad / Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Capacidad') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Juegos') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($stadiums as $st)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('stadiums.show', $st) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $st->name }}</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">{{ trim(($st->city ?? '') . (($st->city && $st->state) ? ', ' : '') . ($st->state ?? ''), ', ') ?: '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text text-right">{{ $st->capacity ? number_format($st->capacity, 0, ',', '.') : '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $st->games_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if ($st->active)
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <x-action-buttons
                                                :show="route('stadiums.show', $st)"
                                                :edit="route('stadiums.edit', $st)"
                                                :delete="route('stadiums.destroy', $st)"
                                                :deleteMessage="__('¿Eliminar el estadio «:name»?', ['name' => $st->name])"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $stadiums->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
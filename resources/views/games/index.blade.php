<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Mis juegos') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Lista completa de juegos con su estado actual.') }}</p>
            </div>
            <a href="{{ route('games.create') }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card">+ {{ __('Nuevo juego') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Stats --}}
            @php
                $statLabels = ['total' => __('Total'), 'scheduled' => __('Programados'), 'in_progress' => __('En vivo'), 'completed' => __('Finalizados'), 'public' => __('PÃºblicos')];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
                @foreach ($statLabels as $key => $label)
                    <div class="bg-wv-surface border border-wv-border rounded-card p-4 text-center">
                        <div class="font-mono text-kpi text-wv-text">{{ $stats[$key] ?? 0 }}</div>
                        <div class="text-xs text-wv-text-secondary uppercase tracking-wider mt-1">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($games->isEmpty())
                    <div class="p-10 text-center">
                        <p class="mb-4 text-wv-text-secondary">{{ __('AÃºn no has creado juegos.') }}</p>
                        <a href="{{ route('games.create') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Crear el primer juego') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Fecha') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Enfrentamiento') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Categoría / Estadio') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('PÃºblico') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($games as $g)
                                    @php
                                        $statusColors = [
                                            'scheduled' => 'bg-wv-accent-soft text-wv-accent border-wv-accent/40',
                                            'in_progress' => 'bg-wv-alert-bg text-wv-alert border-wv-alert/40',
                                            'paused' => 'bg-wv-accent-soft text-wv-accent border-wv-accent/40',
                                            'completed' => 'bg-wv-success/15 text-wv-success border-wv-success/40',
                                            'suspended' => 'bg-wv-surface-hover text-wv-text-secondary border-wv-border',
                                            'cancelled' => 'bg-wv-surface-hover text-wv-text-secondary border-wv-border',
                                        ];
                                        $statusLabels = ['scheduled' => 'Programado', 'in_progress' => 'En vivo', 'paused' => 'Pausado', 'completed' => 'Finalizado', 'suspended' => 'Suspendido', 'cancelled' => 'Cancelado'];
                                    @endphp
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-3 text-sm text-wv-text">
                                            <div class="font-medium">{{ $g->scheduled_at->format('d/m/Y') }}</div>
                                            <div class="text-xs text-wv-text-secondary">{{ $g->scheduled_at->format('H:i') }}</div>
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            <a href="{{ route('games.show', $g) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">
                                                {{ $g->homeTeam->short_name ?? $g->homeTeam->name }}
                                                <span class="text-wv-text-secondary mx-1">vs</span>
                                                {{ $g->awayTeam->short_name ?? $g->awayTeam->name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-3 text-xs text-wv-text-secondary">
                                            <div>{{ $g->category->name ?? '—' }}</div>
                                            <div>{{ $g->stadium->name ?? '—' }}</div>
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full border {{ $statusColors[$g->status] ?? 'bg-wv-surface-hover text-wv-text-secondary border-wv-border' }}">
                                                {{ __($statusLabels[$g->status] ?? $g->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            @if ($g->is_public)
                                                <span class="inline-flex items-center text-wv-success" title="{{ __('Juego pÃºblico') }}">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                                </span>
                                            @else
                                                <span class="text-wv-text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium">
                                            @if (in_array($g->status, ['scheduled', 'in_progress', 'paused']))
                                                <a href="{{ route('games.scoreboard', $g) }}" class="text-wv-accent hover:text-wv-accent-hover mr-3">{{ __('En vivo') }}</a>
                                            @endif
                                            <x-action-buttons
                                                :show="route('games.show', $g)"
                                                :edit="route('games.edit', $g)"
                                                :delete="route('games.destroy', $g)"
                                                :deleteMessage="__('¿Eliminar el juego «:title»?', ['title' => $g->title ?? '#' . $g->id])"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $games->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
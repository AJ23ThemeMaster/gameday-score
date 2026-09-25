<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Mis juegos') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Lista completa de juegos con su estado actual.') }}</p>
            </div>
            @can('create', App\Models\Game::class)
                <a href="{{ route('games.create') }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card">+ {{ __('Nuevo juego') }}</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Stats --}}
            @php
                $statLabels = ['total' => __('Total'), 'scheduled' => __('Programados'), 'in_progress' => __('En vivo'), 'completed' => __('Finalizados'), 'public' => __('Públicos')];
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

            {{-- Filtros --}}
            <form method="GET" action="{{ route('games.index') }}" class="bg-wv-surface border border-wv-border rounded-card p-4 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Fecha desde') }}</label>
                        <input type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}"
                               class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Fecha hasta') }}</label>
                        <input type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}"
                               class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Equipo local') }}</label>
                        <select name="home_team_id" class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}" {{ (string) ($filters['home_team_id'] ?? '') === (string) $t->id ? 'selected' : '' }}>
                                    {{ $t->short_name ?? $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Equipo visitante') }}</label>
                        <select name="away_team_id" class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}" {{ (string) ($filters['away_team_id'] ?? '') === (string) $t->id ? 'selected' : '' }}>
                                    {{ $t->short_name ?? $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Categoría') }}</label>
                        <select name="category_id" class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" {{ (string) ($filters['category_id'] ?? '') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Estadio') }}</label>
                        <select name="stadium_id" class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($stadiums as $s)
                                <option value="{{ $s->id }}" {{ (string) ($filters['stadium_id'] ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Torneo') }}</label>
                        <select name="tournament_id" class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($tournaments as $t)
                                <option value="{{ $t->id }}" {{ (string) ($filters['tournament_id'] ?? '') === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Anotador') }}</label>
                        <select name="scorekeeper_id" class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($scorekeepers as $s)
                                <option value="{{ $s->id }}" {{ (string) ($filters['scorekeeper_id'] ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-wv-text-secondary uppercase tracking-wider mb-1">{{ __('Árbitro') }}</label>
                        <select name="referee_id" class="block w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($referees as $r)
                                <option value="{{ $r->id }}" {{ (string) ($filters['referee_id'] ?? '') === (string) $r->id ? 'selected' : '' }}>{{ $r->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <a href="{{ route('games.index') }}" class="inline-flex items-center px-3 py-1.5 bg-wv-surface border border-wv-border hover:bg-wv-surface-hover text-wv-text-secondary text-xs font-semibold rounded-md">
                        {{ __('Limpiar filtros') }}
                    </a>
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-md">
                        {{ __('Filtrar') }}
                    </button>
                </div>
            </form>

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($games->isEmpty())
                    <div class="p-10 text-center">
                        <p class="mb-4 text-wv-text-secondary">{{ __('Aún no hay juegos para mostrar con los filtros activos.') }}</p>
                        @can('create', App\Models\Game::class)
                            <a href="{{ route('games.create') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Crear el primer juego') }}</a>
                        @endcan
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Fecha') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Enfrentamiento') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Categoría / Estadio') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Anotador') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Árbitro') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Torneo') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
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

                                        // Anotador principal: el user_id del juego.
                                        // Anotadores adicionales: los del pivote game_scorekeeper.
                                        $scorekeeperNames = collect();
                                        if ($g->user) {
                                            $scorekeeperNames->push($g->user->name);
                                        }
                                        if ($g->relationLoaded('scorekeepers')) {
                                            foreach ($g->scorekeepers as $sk) {
                                                $scorekeeperNames->push($sk->full_name);
                                            }
                                        }
                                        $scorekeeperNames = $scorekeeperNames->unique()->values();

                                        // Arbitros: del pivote game_referee.
                                        $refereeNames = collect();
                                        if ($g->relationLoaded('referees')) {
                                            $refereeNames = $g->referees->pluck('full_name');
                                        }
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
                                        <td class="px-6 py-3 text-xs text-wv-text">
                                            @if ($scorekeeperNames->isEmpty())
                                                <span class="text-wv-text-secondary italic">{{ __('Sin asignar') }}</span>
                                            @else
                                                {{ $scorekeeperNames->implode(', ') }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-xs text-wv-text">
                                            @if ($refereeNames->isEmpty())
                                                <span class="text-wv-text-secondary italic">{{ __('Sin asignar') }}</span>
                                            @else
                                                {{ $refereeNames->implode(', ') }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-xs text-wv-text">
                                            {{ $g->tournament->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full border {{ $statusColors[$g->status] ?? 'bg-wv-surface-hover text-wv-text-secondary border-wv-border' }}">
                                                {{ __($statusLabels[$g->status] ?? $g->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium">
                                            <div class="inline-flex items-center gap-1">
                                                @if ($g->status === 'in_progress')
                                                    {{-- live_tv: enlace publico al juego en vivo con token (games.live.public).
                                                         Es la URL que se comparte con espectadores externos.
                                                         Solo aparece cuando el juego esta en curso. --}}
                                                    @if ($g->public_token)
                                                        <a href="{{ route('games.live.public', $g->public_token) }}"
                                                           target="_blank"
                                                           rel="noopener noreferrer"
                                                           title="{{ __('En vivo (vista publica)') }}"
                                                           aria-label="{{ __('En vivo (vista publica)') }}"
                                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-wv-bg border-wv-alert/40 text-wv-alert hover:bg-wv-alert/15 hover:border-wv-alert focus:ring-wv-alert">
                                                            <span class="material-symbols-outlined !text-[16px]">live_tv</span>
                                                        </a>
                                                    @endif
                                                @endif
                                                {{-- sports_baseball: scoreboard de control del juego (games.scoreboard).
                                                     Solo el anotador/admin usa esta vista para gestionar jugadas. --}}
                                                @if (in_array($g->status, ['scheduled', 'in_progress', 'paused']))
                                                    <a href="{{ route('games.scoreboard', $g) }}"
                                                       title="{{ __('Scoreboard') }}"
                                                       aria-label="{{ __('Scoreboard') }}"
                                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-wv-bg border-wv-accent/40 text-wv-accent hover:bg-wv-accent/15 hover:border-wv-accent focus:ring-wv-accent">
                                                        <span class="material-symbols-outlined !text-[16px]">sports_baseball</span>
                                                    </a>
                                                @endif
                                                {{-- DISI-piloto: el boton Editar aparece si la policy `update` pasa;
                                                     Eliminar requiere policy `delete` (admin-only en este piloto). --}}
                                                <x-action-buttons
                                                    :show="route('games.show', $g)"
                                                    :edit="route('games.edit', $g)"
                                                    :delete="route('games.destroy', $g)"
                                                    :canEdit="\Illuminate\Support\Facades\Gate::check('update', $g)"
                                                    :canDelete="\Illuminate\Support\Facades\Gate::check('delete', $g)"
                                                    :deleteMessage="__('¿Eliminar el juego «:title»?', ['title' => $g->title ?? '#' . $g->id])"
                                                />
                                            </div>
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

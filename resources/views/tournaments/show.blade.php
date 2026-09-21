<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                @if ($tournament->logo_url)
                    <img src="{{ $tournament->logo_url }}" alt="{{ $tournament->name }}" class="h-10 w-10 object-contain bg-white rounded p-0.5">
                @endif
                <div>
                    <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                        {{ $tournament->name }}
                    </h2>
                    <p class="text-sm text-wv-text-secondary mt-1">{{ __('Torneo') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('tournaments.edit', $tournament) }}"
                   class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                    {{ __('Editar') }}
                </a>
                <a href="{{ route('tournaments.index') }}"
                   class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card mb-4">
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Liga') }}</p>
                        <p class="font-medium">
                            <a href="{{ route('leagues.show', $tournament->league) }}" class="text-wv-accent hover:text-wv-accent-hover">
                                @if ($tournament->league->logo_url)
                                    <img src="{{ $tournament->league->logo_url }}" alt="" class="h-5 w-5 inline-block object-contain mr-1 align-middle bg-white rounded p-0.5">
                                @endif
                                {{ $tournament->league->name }}
                            </a>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Categoría') }}</p>
                        <p class="font-medium text-wv-text">{{ $tournament->category ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Temporada') }}</p>
                        <p class="font-medium text-wv-text">{{ $tournament->season ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Inicio') }}</p>
                        <p class="font-medium text-wv-text">{{ $tournament->starts_at?->format('Y-m-d') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Fin') }}</p>
                        <p class="font-medium text-wv-text">{{ $tournament->ends_at?->format('Y-m-d') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Estado') }}</p>
                        <p class="font-medium">
                            @if ($tournament->active)
                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                            @else
                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="md:col-span-3">
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Descripción') }}</p>
                        <p class="text-wv-text-secondary">{{ $tournament->description ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Juegos asociados') }}</p>
                        <p class="font-medium text-wv-text">{{ $tournament->games_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Equipos asociados') }}</p>
                        <p class="font-medium text-wv-text">{{ $tournament->teams_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Categorías representadas') }}</p>
                        <p class="font-medium text-wv-text">{{ $representedCategories->count() }}</p>
                    </div>
                </div>
            </div>

            {{-- DISI-57: listado de equipos del torneo --}}
            <div class="bg-wv-surface border border-wv-border rounded-card mb-4">
                <div class="p-6 border-b border-wv-border flex justify-between items-center">
                    <h3 class="text-lg font-bold text-wv-text">{{ __('Equipos del torneo') }}</h3>
                    <a href="{{ route('tournaments.edit', $tournament) }}"
                       class="text-sm text-wv-accent hover:text-wv-accent-hover font-semibold">
                        {{ __('Asignar equipos') }}
                    </a>
                </div>
                @if ($tournament->teams->isEmpty())
                    <p class="p-6 text-wv-text-secondary text-sm">{{ __('Este torneo aún no tiene equipos asignados.') }}</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-6">
                        @foreach ($tournament->teams as $t)
                            <a href="{{ route('teams.show', $t) }}"
                               class="flex items-center gap-3 p-3 bg-wv-bg hover:bg-wv-surface-hover rounded-card border border-wv-border transition">
                                @if ($t->logo_url)
                                    <img src="{{ $t->logo_url }}" alt="" class="h-10 w-10 object-contain bg-white rounded p-0.5">
                                @else
                                    <div class="h-10 w-10 bg-wv-surface-hover rounded flex items-center justify-center text-wv-text-secondary font-bold text-sm">
                                        {{ mb_strtoupper(mb_substr($t->short_name ?? $t->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-semibold text-wv-text truncate">{{ $t->name }}</div>
                                    @if ($t->short_name)
                                        <div class="text-xs text-wv-text-secondary">({{ $t->short_name }})</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- DISI-59: categorias representadas en el torneo --}}
            <div class="bg-wv-surface border border-wv-border rounded-card mb-4">
                <div class="p-6 border-b border-wv-border">
                    <h3 class="text-lg font-bold text-wv-text">{{ __('Categorias representadas') }}</h3>
                    <p class="text-xs text-wv-text-secondary mt-1">
                        {{ __('Unicas, derivadas de las categorias de los equipos del torneo.') }}
                    </p>
                </div>
                @if ($representedCategories->isEmpty())
                    <p class="p-6 text-wv-text-secondary text-sm">{{ __('Los equipos del torneo aun no tienen categorias registradas.') }}</p>
                @else
                    <div class="p-6 flex flex-wrap gap-2">
                        @foreach ($representedCategories as $c)
                            <a href="{{ route('categories.show', $c) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-wv-surface-hover hover:bg-wv-surface rounded-full border border-wv-border text-sm">
                                <span class="font-semibold text-wv-text">{{ $c->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-wv-surface border border-wv-border rounded-card">
                <div class="p-6 border-b border-wv-border">
                    <h3 class="text-lg font-bold text-wv-text">{{ __('Juegos del torneo') }}</h3>
                </div>
                @if ($tournament->games->isEmpty())
                    <p class="p-6 text-wv-text-secondary text-sm">{{ __('Este torneo aún no tiene juegos registrados.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Fecha') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Local') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Score') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Visitante') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($tournament->games as $g)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-wv-text-secondary">
                                            {{ $g->scheduled_at?->format('Y-m-d H:i') ?? '—' }}
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-wv-text font-medium">{{ $g->homeTeam->short_name ?? $g->homeTeam->name }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap text-center text-sm font-bold">
                                            <a href="{{ route('games.scoreboard', $g) }}" class="text-wv-accent hover:text-wv-accent-hover">
                                                {{ $g->home_score ?? 0 }} - {{ $g->away_score ?? 0 }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-wv-text font-medium">{{ $g->awayTeam->short_name ?? $g->awayTeam->name }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap text-center text-sm text-wv-text-secondary">{{ $g->status }}</td>
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
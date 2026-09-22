<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('CategorÃ�a') }}: {{ $category->name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('categories.index') }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Listado') }}</a>
                <a href="{{ route('categories.edit', $category) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    @if ($category->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activa') }}</span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactiva') }}</span>
                    @endif
                    <code class="text-sm text-wv-text-secondary">{{ $category->slug }}</code>
                </div>

                @if ($category->description)
                    <p class="text-wv-text-secondary mb-6">{{ $category->description }}</p>
                @endif

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Innings por juego') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $category->innings_count }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('LÃ�mite de lanzamientos') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $category->pitch_limit ?? 'â€”' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Regla del Nocaut') }}</dt>
                        <dd class="text-lg font-semibold text-wv-text">-{{ $category->mercy_rule_difference }} desde el inning {{ $category->mercy_rule_inning }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Juegos asociados') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $category->games_count }}</dd>
                    </div>
                </dl>

                {{-- DISI-61: breadcrumb de equipo --}}
                @if ($category->team)
                    <div class="mt-6 pt-6 border-t border-wv-border text-sm">
                        <p class="text-wv-text-secondary text-xs uppercase mb-1">{{ __('Equipo') }}</p>
                        <a href="{{ route('teams.show', $category->team) }}"
                           class="inline-flex items-center gap-2 text-wv-accent hover:text-wv-accent-hover">
                            @if ($category->team->logo_url)
                                <img src="{{ $category->team->logo_url }}" alt="" class="h-6 w-6 object-contain bg-white rounded p-0.5">
                            @endif
                            <span class="font-semibold">{{ $category->team->name }}</span>
                        </a>
                        @if ($category->team->league)
                            <span class="text-wv-text-secondary mx-1">Â·</span>
                            <a href="{{ route('leagues.show', $category->team->league) }}" class="text-sm text-wv-text-secondary hover:text-wv-accent">{{ $category->team->league->name }}</a>
                        @endif
                    </div>
                @endif

                {{-- DISI-61: atletas de la categoria --}}
                <div class="mt-6 pt-6 border-t border-wv-border">
                    <h4 class="text-sm font-semibold text-wv-text mb-3 flex justify-between items-center">
                        <span>{{ __('Atletas de la categoria') }} ({{ $category->athletes->count() }})</span>
                        <a href="{{ route('athletes.create', ['team_id' => $category->team_id, 'category_id' => $category->id]) }}"
                           class="text-xs text-wv-accent hover:text-wv-accent-hover font-semibold">
                            + {{ __('Nuevo atleta') }}
                        </a>
                    </h4>
                    @if ($category->athletes->isEmpty())
                        <p class="text-sm text-wv-text-secondary italic">{{ __('Esta categoria aun no tiene atletas asignados.') }}</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach ($category->athletes as $a)
                                <a href="{{ route('athletes.show', $a) }}"
                                   class="text-sm text-wv-text bg-wv-bg hover:bg-wv-surface-hover rounded-card border border-wv-border px-3 py-2 flex items-center gap-2 transition">
                                    <span class="text-xs font-mono text-wv-text-secondary w-6 text-right">{{ $a->number ?? '-' }}</span>
                                    <span class="flex-1 truncate">{{ $a->full_name }}</span>
                                    @if ($a->position)
                                        <span class="text-xs bg-wv-surface-hover text-wv-text px-1.5 py-0.5 rounded">{{ $a->position }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- DISI-61: juegos de la categoria --}}
                <div class="mt-6 pt-6 border-t border-wv-border">
                    <h4 class="text-sm font-semibold text-wv-text mb-3">{{ __('Juegos de la categoria') }} ({{ $category->games_count }})</h4>
                    @if ($category->games->isEmpty())
                        <p class="text-sm text-wv-text-secondary italic">{{ __('Esta categoria aun no tiene juegos registrados.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-wv-border text-sm">
                                <thead class="bg-wv-surface-hover">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Fecha') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Local') }}</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Score') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Visitante') }}</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-wv-border">
                                    @foreach ($category->games as $g)
                                        <tr class="hover:bg-wv-surface-hover">
                                            <td class="px-3 py-2 whitespace-nowrap text-wv-text-secondary">{{ $g->scheduled_at?->format('d/m/Y H:i') ?? 'â€”' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium text-wv-text">{{ $g->homeTeam->short_name ?? $g->homeTeam->name }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-center font-bold">
                                                <a href="{{ route('games.scoreboard', $g) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $g->home_score ?? 0 }} - {{ $g->away_score ?? 0 }}</a>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium text-wv-text">{{ $g->awayTeam->short_name ?? $g->awayTeam->name }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-center">
                                                <span class="text-xs font-mono text-wv-text-secondary">{{ $g->status }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="mt-6 pt-6 border-t border-wv-border text-xs text-wv-text-secondary">
                    {{ __('Creada') }}: {{ $category->created_at->format('d/m/Y H:i') }} Â·
                    {{ __('Actualizada') }}: {{ $category->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

            <form action="{{ route('categories.destroy', $category) }}" method="POST" class="mt-4 text-right" data-confirm="'�Eliminar la categorÃ�a �{{ $category->name }}�?'" data-confirm-danger="true" data-loader>
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar categorÃ�a') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
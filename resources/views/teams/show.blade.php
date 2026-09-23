<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight flex items-center gap-3">
                @if ($team->logoUrl)
                    <img src="{{ $team->logoUrl }}" alt="{{ $team->name }}" class="h-10 w-10 object-contain bg-white rounded p-0.5">
                @endif
                {{ __('Equipo') }}: {{ $team->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('teams.index') }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Listado') }}</a>
                <a href="{{ route('teams.edit', $team) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card p-6">

                <div class="flex flex-wrap items-center gap-3 mb-4">
                    @if ($team->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                    @endif
                    @if ($team->short_name)
                        <code class="text-sm text-wv-text-secondary">{{ $team->short_name }}</code>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Ciudad') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $team->city ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Atletas') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $team->athletes_count }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Como local') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $team->home_games_count }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Como visitante') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $team->away_games_count }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-card p-4 border border-wv-border bg-wv-bg" :style="'background-color: ' + '{{ $team->home_color ?? '#1a3d6e' }}' + '20'">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-8 h-8 rounded border border-wv-border-strong"
                                  style="background-color: {{ $team->home_color ?? '#1a3d6e' }}"></span>
                            <span class="text-sm text-wv-text-secondary">{{ __('Color local') }}: <code class="text-wv-text">{{ $team->home_color ?? '—' }}</code></span>
                        </div>
                    </div>
                    <div class="rounded-card p-4 border border-wv-border bg-wv-bg">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-8 h-8 rounded border border-wv-border-strong"
                                  style="background-color: {{ $team->away_color ?? '#ffffff' }}"></span>
                            <span class="text-sm text-wv-text-secondary">{{ __('Color visitante') }}: <code class="text-wv-text">{{ $team->away_color ?? '—' }}</code></span>
                        </div>
                    </div>
                </div>

                {{-- DISI-64: roster filtrable --}}
                <div class="mt-6 pt-6 border-t border-wv-border">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-sm font-semibold text-wv-text">
                            {{ __('Roster') }}
                            <span class="text-wv-text-secondary font-normal">({{ $filteredCount }} {{ __('de') }} {{ $totalAthletes }})</span>
                        </h4>
                        @if ($teamCategories->count())
                            <a href="{{ route('categories.create', ['team_id' => $team->id]) }}"
                               class="text-xs text-wv-accent hover:text-wv-accent-hover font-semibold">
                                + {{ __('Nueva categoria') }}
                            </a>
                        @endif
                    </div>

                    {{-- Filtros --}}
                    <form method="GET" action="{{ route('teams.show', $team) }}" class="grid grid-cols-2 sm:grid-cols-6 gap-2 mb-3">
                        <input type="text" name="name" value="{{ $filters['name'] }}" placeholder="{{ __('Nombre') }}"
                               class="sm:col-span-2 border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                        <input type="text" name="number" value="{{ $filters['number'] }}" placeholder="{{ __('N°') }}"
                               class="border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                        <select name="position" class="border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Pos. (todas)') }}</option>
                            @foreach ($positions as $p)
                                <option value="{{ $p }}" {{ $filters['position'] === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>{{ __('Estado (todos)') }}</option>
                            <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>{{ __('Activos') }}</option>
                            <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>{{ __('Inactivos') }}</option>
                        </select>
                        <select name="category_id" class="border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Categoria (todas)') }}</option>
                            @foreach ($filterCategories as $c)
                                <option value="{{ $c->id }}" {{ (string) $filters['category_id'] === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <div class="sm:col-span-6 flex gap-2">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-md">
                                {{ __('Filtrar') }}
                            </button>
                            @if ($filters['name'] !== '' || $filters['number'] !== '' || $filters['position'] !== '' || $filters['status'] !== 'all' || $filters['category_id'] !== null)
                                <a href="{{ route('teams.show', $team) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-wv-surface border border-wv-border hover:bg-wv-surface-hover text-wv-text-secondary text-xs font-semibold rounded-md">
                                    {{ __('Limpiar filtros') }}
                                </a>
                                <span class="text-xs text-wv-text-secondary self-center">{{ __('Filtros activos') }}</span>
                            @endif
                        </div>
                    </form>

                    {{-- Chips de categorias --}}
                    @if ($teamCategories->count())
                        <div class="mb-3 flex flex-wrap gap-1.5">
                            @foreach ($teamCategories as $c)
                                @php
                                    $isActive = (string) $filters['category_id'] === (string) $c->id;
                                    $queryParams = request()->except(['category_id', 'page']);
                                    if (! $isActive && $c->id) {
                                        $queryParams['category_id'] = $c->id;
                                    }
                                @endphp
                                <a href="{{ route('teams.show', array_merge([$team], $queryParams)) }}"
                                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition
                                          {{ $isActive ? 'bg-wv-accent text-wv-text-on-accent border-wv-accent' : 'bg-wv-surface text-wv-text-secondary border-wv-border hover:bg-wv-surface-hover' }}">
                                    {{ $c->name }}
                                    <span class="text-[10px] opacity-75 font-mono">{{ $categoryStats[$c->id]['athletes'] ?? 0 }}</span>
                                    @if ($c->active)
                                        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-wv-text-on-accent/70' : 'bg-wv-success' }}"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Tabla del roster filtrado --}}
                    <div class="overflow-x-auto border border-wv-border rounded-card">
                        <table class="min-w-full divide-y divide-wv-border text-sm">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">N°</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Pos.') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Categoria') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('B/L') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @forelse ($athletes as $a)
                                    <tr class="hover:bg-wv-surface-hover">
                                        <td class="px-3 py-2 whitespace-nowrap font-mono text-wv-text">{{ $a->number ?? '—' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <a href="{{ route('athletes.show', $a) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $a->full_name }}</a>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            @if ($a->position)
                                                <span class="inline-block px-1.5 py-0.5 text-xs bg-wv-surface-hover text-wv-text rounded font-mono">{{ $a->position }}</span>
                                            @else —
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            @if ($a->active)
                                                <span class="inline-flex px-2 text-[10px] leading-4 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-[10px] leading-4 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @if ($a->category)
                                                <a href="{{ route('categories.show', $a->category) }}" class="text-wv-accent hover:text-wv-accent-hover text-xs">{{ $a->category->name }}</a>
                                            @else
                                                <span class="text-wv-text-secondary text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-xs text-wv-text-secondary">{{ $a->bats }}/{{ $a->throws }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-wv-text-secondary text-sm">
                                            {{ __('No hay atletas que coincidan con el filtro.') }}
                                            @if ($filters['name'] !== '' || $filters['number'] !== '' || $filters['position'] !== '' || $filters['status'] !== 'all' || $filters['category_id'] !== null)
                                                <a href="{{ route('teams.show', $team) }}" class="text-wv-accent hover:text-wv-accent-hover underline ms-2">{{ __('Limpiar filtros') }}</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- DISI-60: torneos en los que participa el equipo --}}
                @if ($team->tournaments->count())
                    <div class="mt-6 pt-6 border-t border-wv-border">
                        <h4 class="text-sm font-semibold text-wv-text mb-3">
                            {{ __('Torneos en los que participa') }} ({{ $team->tournaments->count() }})
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($team->tournaments as $t)
                                <a href="{{ route('tournaments.show', $t) }}"
                                   class="inline-flex items-center gap-2 px-3 py-1.5 bg-wv-surface hover:bg-wv-surface-hover rounded-full border border-wv-border text-sm">
                                    @if ($t->logo_url)
                                        <img src="{{ $t->logo_url }}" alt="" class="h-4 w-4 object-contain bg-white rounded p-0.5">
                                    @endif
                                    <span class="font-semibold text-wv-text">{{ $t->name }}</span>
                                    @if ($t->category)
                                        <span class="text-xs text-wv-text-secondary">({{ $t->category }})</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Entrenadores del equipo --}}
                <div class="mt-6 pt-6 border-t border-wv-border">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-sm font-semibold text-wv-text">
                            {{ __('Entrenadores') }}
                            <span class="text-wv-text-secondary font-normal">({{ $team->coaches_count ?? 0 }})</span>
                        </h4>
                        <a href="{{ route('teams.coaches.index', $team) }}"
                           class="text-xs text-wv-accent hover:text-wv-accent-hover font-semibold">
                            {{ __('Gestionar entrenadores') }} →
                        </a>
                    </div>

                    @php
                        $coachesList = $team->coaches()->orderBy('last_name')->orderBy('first_name')->limit(5)->get();
                    @endphp

                    @if ($coachesList->isEmpty())
                        <p class="text-sm text-wv-text-secondary italic">{{ __('Aún no hay entrenadores registrados.') }}</p>
                    @else
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($coachesList as $c)
                                <li class="bg-wv-bg border border-wv-border rounded-card p-3 flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('teams.coaches.show', [$team, $c]) }}" class="text-sm font-medium text-wv-text hover:text-wv-accent truncate block">
                                            {{ $c->full_name }}
                                        </a>
                                        @if ($c->role)
                                            <p class="text-xs text-wv-text-secondary">{{ $c->role }}</p>
                                        @endif
                                    </div>
                                    @if ($c->active)
                                        <span class="inline-block w-2 h-2 rounded-full bg-wv-success flex-shrink-0" title="{{ __('Activo') }}"></span>
                                    @else
                                        <span class="inline-block w-2 h-2 rounded-full bg-wv-text-secondary flex-shrink-0" title="{{ __('Inactivo') }}"></span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if (($team->coaches_count ?? 0) > 5)
                            <p class="text-xs text-wv-text-secondary mt-2">
                                {{ __('Mostrando 5 de :total. Ver todos en', ['total' => $team->coaches_count]) }}
                                <a href="{{ route('teams.coaches.index', $team) }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Entrenadores') }}</a>.
                            </p>
                        @endif
                    @endif
                </div>

                <div class="mt-6 pt-6 border-t border-wv-border text-xs text-wv-text-secondary">
                    {{ __('Creado') }}: {{ $team->created_at->format('d/m/Y H:i') }} ·
                    {{ __('Actualizado') }}: {{ $team->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

            <form action="{{ route('teams.destroy', $team) }}" method="POST" class="mt-4 text-right" data-confirm="'¿Eliminar el equipo «{{ $team->name }}»?'" data-confirm-danger="true" data-loader>
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar equipo') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
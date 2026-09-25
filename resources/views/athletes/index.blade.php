<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Atletas') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">
                    @auth
                        @if (auth()->user()->isGestor())
                            {{ __('Mostrando solo atletas de tu equipo asociado.') }}
                        @else
                            {{ __('Listado completo de atletas registrados en el sistema.') }}
                        @endif
                    @endauth
                </p>
            </div>
            {{-- DISI-piloto: el boton "+ Nuevo atleta" aparece si el user tiene via
                 abierta para crear (admin / gestor con team / delegado con scope).
                 Coincide con EnsureAdminOrGestorOrDelegado middleware. --}}
            @if (auth()->user()?->canCreateAthletes())
                <a href="{{ route('athletes.create') }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card">
                    + {{ __('Nuevo atleta') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                {{-- DISI-65: filtros --}}
                <div class="p-4 border-b border-wv-border bg-wv-surface-hover">
                    <form method="GET" action="{{ route('athletes.index') }}" class="grid grid-cols-2 sm:grid-cols-7 gap-2">
                        <input type="text" name="name" value="{{ $filters['name'] }}" placeholder="{{ __('Nombre') }}"
                               class="sm:col-span-2 border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                        <input type="text" name="document_id" value="{{ $filters['document_id'] }}" placeholder="{{ __('Doc.') }}"
                               class="border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
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
                        <select name="team_id" class="border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Equipo (todos)') }}</option>
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}" {{ (string) $filters['team_id'] === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <select name="category_id" class="border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md text-sm">
                            <option value="">{{ __('Categoria (todas)') }}</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" {{ (string) $filters['category_id'] === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <div class="col-span-2 sm:col-span-7 flex flex-wrap gap-2 mt-1">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-md">
                                {{ __('Buscar') }}
                            </button>
                            @php
                                $hasFilters = $filters['name'] !== '' || $filters['document_id'] !== '' || $filters['number'] !== '' || $filters['position'] !== '' || $filters['status'] !== 'all' || $filters['team_id'] !== null || $filters['category_id'] !== null;
                            @endphp
                            @if ($hasFilters)
                                <a href="{{ route('athletes.index') }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-wv-surface hover:bg-wv-surface-hover text-wv-text-secondary text-xs font-semibold rounded-md border border-wv-border">
                                    {{ __('Limpiar filtros') }}
                                </a>
                                <span class="text-xs text-wv-text-secondary self-center">
                                    {{ __('Mostrando') }} {{ $filteredCount }} {{ __('de') }} {{ $totalAthletes }} {{ __('atletas') }}
                                </span>
                            @else
                                <span class="text-xs text-wv-text-secondary self-center">{{ $totalAthletes }} {{ __('atletas en total') }}</span>
                            @endif
                        </div>
                    </form>
                </div>

                @if ($athletes->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        @if ($hasFilters)
                            <p class="mb-4">{{ __('No hay atletas que coincidan con el filtro.') }}</p>
                            <a href="{{ route('athletes.index') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Limpiar filtros') }}</a>
                        @else
                            <p class="mb-4">{{ __('AÃºn no hay atletas registrados.') }}</p>
                            @if (auth()->user()?->canCreateAthletes())
                                <a href="{{ route('athletes.create') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Registrar el primer atleta') }}</a>
                            @endif
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Foto') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Equipo') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Categoria') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('N°') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Pos.') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('B/T') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($athletes as $a)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-3">
                                            @if ($a->photoUrl)
                                                <img src="{{ $a->photoUrl }}" class="h-10 w-10 rounded-full object-cover bg-wv-surface">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-wv-surface-hover flex items-center justify-center text-wv-text-secondary text-xs font-semibold">
                                                    {{ mb_substr($a->first_name, 0, 1) }}{{ mb_substr($a->last_name, 0, 1) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3">
                                            <a href="{{ route('athletes.show', $a) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $a->full_name }}</a>
                                            @if ($a->document_id)<p class="text-xs text-wv-text-secondary">{{ $a->document_id }}</p>@endif
                                        </td>
                                        <td class="px-6 py-3 text-sm text-wv-text-secondary">{{ $a->team->name ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm">
                                            @if ($a->category)
                                                <a href="{{ route('categories.show', $a->category) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $a->category->name }}</a>
                                            @else
                                                <span class="text-wv-text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-center text-sm text-wv-text">{{ $a->number ?? '—' }}</td>
                                        <td class="px-6 py-3 text-center text-sm font-mono text-wv-text">{{ $a->position ?? '—' }}</td>
                                        <td class="px-6 py-3 text-center text-xs text-wv-text-secondary">{{ $a->bats }}/{{ $a->throws }}</td>
                                        <td class="px-6 py-3 text-center">
                                            @if ($a->active)
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium">
                                            <x-action-buttons
                                                :show="route('athletes.show', $a)"
                                                :edit="route('athletes.edit', $a)"
                                                :delete="route('athletes.destroy', $a)"
                                                :deleteMessage="__('¿Eliminar a «:name»?', ['name' => $a->full_name])"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $athletes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
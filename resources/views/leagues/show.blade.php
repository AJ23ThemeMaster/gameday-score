<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                @if ($league->logo_url)
                    <img src="{{ $league->logo_url }}" alt="{{ $league->name }}" class="h-10 w-10 object-contain bg-white rounded p-0.5">
                @endif
                <div>
                    <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                        {{ $league->name }}
                    </h2>
                    <p class="text-sm text-wv-text-secondary mt-1">{{ __('Liga') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('leagues.edit', $league) }}"
                   class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                    {{ __('Editar') }}
                </a>
                <a href="{{ route('leagues.index') }}"
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
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Nombre corto') }}</p>
                        <p class="font-medium text-wv-text">{{ $league->short_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('País') }}</p>
                        <p class="font-medium text-wv-text">{{ $league->country ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Estado') }}</p>
                        <p class="font-medium">
                            @if ($league->active)
                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activa') }}</span>
                            @else
                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactiva') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="md:col-span-3">
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Descripción') }}</p>
                        <p class="text-wv-text-secondary">{{ $league->description ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Torneos asociados') }}</p>
                        <p class="font-medium text-wv-text">{{ $league->tournaments_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Equipos asociados') }}</p>
                        <p class="font-medium text-wv-text">{{ $teams->count() }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Atletas asociados') }}</p>
                        <p class="font-medium text-wv-text">{{ $athletes->count() }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-wv-text-secondary">{{ __('Juegos asociados') }}</p>
                        <p class="font-medium text-wv-text">{{ $league->games_count }}</p>
                    </div>
                </div>
            </div>

            {{-- DISI-58: Equipos de la liga --}}
            <div class="bg-wv-surface border border-wv-border rounded-card mb-4">
                <div class="p-6 border-b border-wv-border flex justify-between items-center">
                    <h3 class="text-lg font-bold text-wv-text">{{ __('Equipos de la liga') }}</h3>
                    <a href="{{ route('teams.create', ['league_id' => $league->id]) }}"
                       class="text-sm text-wv-accent hover:text-wv-accent-hover font-semibold">
                        + {{ __('Nuevo equipo') }}
                    </a>
                </div>
                @if ($teams->isEmpty())
                    <p class="p-6 text-wv-text-secondary text-sm">{{ __('Esta liga aún no tiene equipos registrados.') }}</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-6">
                        @foreach ($teams as $t)
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
                                    <div class="text-xs text-wv-text-secondary">
                                        {{ $t->categories_count }} {{ \Illuminate\Support\Str::plural('categoría', $t->categories_count) }}
                                        · {{ $t->athletes_count }} {{ \Illuminate\Support\Str::plural('atleta', $t->athletes_count) }}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-wv-surface border border-wv-border rounded-card mb-4">
                <div class="p-6 border-b border-wv-border flex justify-between items-center">
                    <h3 class="text-lg font-bold text-wv-text">{{ __('Torneos de la liga') }}</h3>
                    <a href="{{ route('tournaments.create', ['league_id' => $league->id]) }}"
                       class="text-sm text-wv-accent hover:text-wv-accent-hover font-semibold">
                        + {{ __('Nuevo torneo') }}
                    </a>
                </div>
                @if ($tournaments->isEmpty())
                    <p class="p-6 text-wv-text-secondary text-sm">{{ __('Esta liga aún no tiene torneos registrados.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Categoría') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Temporada') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Juegos') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($tournaments as $t)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('tournaments.show', $t) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">
                                                {{ $t->name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">{{ $t->category ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">{{ $t->season ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $t->games_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('tournaments.edit', $t) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ __('Editar') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- DISI-58: Atletas de la liga (los de los equipos) --}}
            <div class="bg-wv-surface border border-wv-border rounded-card mb-4">
                <div class="p-6 border-b border-wv-border flex justify-between items-center">
                    <h3 class="text-lg font-bold text-wv-text">{{ __('Atletas de la liga') }}</h3>
                    <span class="text-sm text-wv-text-secondary">{{ $athletes->count() }} {{ \Illuminate\Support\Str::plural('atleta', $athletes->count()) }}</span>
                </div>
                @if ($athletes->isEmpty())
                    <p class="p-6 text-wv-text-secondary text-sm">{{ __('Esta liga aún no tiene atletas registrados (a traves de sus equipos).') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('#') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Atleta') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Equipo') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($athletes as $a)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-2 whitespace-nowrap text-sm text-wv-text-secondary">{{ $a->number ?? '—' }}</td>
                                        <td class="px-6 py-2 whitespace-nowrap text-sm">
                                            <a href="{{ route('athletes.show', $a) }}" class="text-wv-accent hover:text-wv-accent-hover">
                                                {{ $a->full_name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-2 whitespace-nowrap text-sm text-wv-text-secondary">
                                            <a href="{{ route('teams.show', $a->team) }}" class="hover:text-wv-accent">
                                                {{ $a->team->short_name ?? $a->team->name }}
                                            </a>
                                        </td>
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
{{--
    Vista de resultados de busqueda global (GET /search?q=...).

    Variables:
      - $query    : string con lo que el usuario escribio
      - $results  : Collection<string, Collection<{title,subtitle,url}>>
                    agrupada por tipo de recurso
      - $tooShort : bool true si el query tiene < 2 caracteres

    Muestra los grupos en cards separados, cada uno con icono segun el
    tipo y una lista vertical de resultados. Click en una fila -> URL
    del recurso (ruta show correspondiente). Limite 5 items por grupo
    en el controller; si el grupo esta vacio, no se renderiza.
--}}
@php
    $groupMeta = [
        'games'        => ['icon' => 'sports_baseball', 'label' => 'Juegos'],
        'leagues'      => ['icon' => 'flag',             'label' => 'Ligas'],
        'categories'   => ['icon' => 'category',         'label' => 'Categorías'],
        'tournaments'  => ['icon' => 'emoji_events',     'label' => 'Torneos'],
        'teams'        => ['icon' => 'groups',           'label' => 'Equipos'],
        'athletes'     => ['icon' => 'person',           'label' => 'Atletas'],
        'scorekeepers' => ['icon' => 'edit_note',        'label' => 'Anotadores'],
        'referees'     => ['icon' => 'sports',           'label' => 'Árbitros'],
        'stadiums'     => ['icon' => 'stadium',          'label' => 'Estadios'],
    ];
    $totalHits = $results->sum(fn ($g) => $g->count());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                {{ __('Resultados de búsqueda') }}
            </h2>
            @if ($query !== '')
                <p class="text-sm text-wv-text-secondary mt-1">
                    {{ __('Buscando') }}: <span class="font-mono text-wv-accent">"{{ $query }}"</span>
                    @if (! $tooShort && $totalHits > 0)
                        · {{ $totalHits }} {{ __('resultado(s)') }}
                    @endif
                </p>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            {{-- Query muy corto --}}
            @if ($tooShort)
                <div class="bg-wv-surface border border-wv-border rounded-card p-8 text-center">
                    <span class="material-symbols-outlined text-wv-text-secondary text-[48px]">search</span>
                    <h3 class="mt-2 text-base font-semibold text-wv-text">{{ __('Escribe al menos 2 caracteres') }}</h3>
                    <p class="mt-1 text-sm text-wv-text-secondary">
                        {{ __('La busqueda global consulta juegos, ligas, categorias, torneos, equipos, atletas, anotadores, arbitros y estadios.') }}
                    </p>
                </div>

            {{-- Sin resultados --}}
            @elseif ($results->isEmpty())
                <div class="bg-wv-surface border border-wv-border rounded-card p-8 text-center">
                    <span class="material-symbols-outlined text-wv-text-secondary text-[48px]">search_off</span>
                    <h3 class="mt-2 text-base font-semibold text-wv-text">{{ __('Sin resultados') }}</h3>
                    <p class="mt-1 text-sm text-wv-text-secondary">
                        {{ __('No encontramos nada que coincida con') }} <span class="font-mono text-wv-accent">"{{ $query }}"</span>.
                    </p>
                </div>

            {{-- Resultados agrupados por tipo --}}
            @else
                <div class="space-y-4">
                    @foreach ($results as $groupKey => $items)
                        @php $meta = $groupMeta[$groupKey] ?? ['icon' => 'circle', 'label' => ucfirst($groupKey)]; @endphp
                        <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                            <div class="p-4 border-b border-wv-border flex items-center gap-2">
                                <span class="material-symbols-outlined text-wv-accent text-[20px]">{{ $meta['icon'] }}</span>
                                <h3 class="text-sm font-bold text-wv-text">
                                    {{ __($meta['label']) }}
                                    <span class="text-xs font-normal text-wv-text-secondary">({{ $items->count() }})</span>
                                </h3>
                            </div>
                            <ul class="divide-y divide-wv-border">
                                @foreach ($items as $item)
                                    <li>
                                        <a href="{{ $item['url'] }}"
                                           class="flex items-center gap-3 px-4 py-3 hover:bg-wv-surface-hover transition group">

                                            {{-- Avatar/logo/icono segun disponibilidad --}}
                                            @if (! empty($item['image']))
                                                {{-- Logo o foto real (logos de equipo/liga vienen con fondo blanco, imageClass lo aplica) --}}
                                                <img src="{{ $item['image'] }}"
                                                     alt="{{ $item['title'] }}"
                                                     class="h-9 w-9 flex-shrink-0 object-contain rounded-md {{ $item['imageClass'] ?? '' }}">
                                            @elseif (! empty($item['initials']))
                                                {{-- Iniciales para personas (atletas, anotadores, arbitros) --}}
                                                <span class="h-9 w-9 rounded-full bg-wv-accent-soft text-wv-accent flex items-center justify-center text-xs font-semibold flex-shrink-0 border border-wv-border">
                                                    {{ $item['initials'] }}
                                                </span>
                                            @else
                                                {{-- Fallback: material-symbols-outlined con el icono del grupo --}}
                                                <span class="material-symbols-outlined text-wv-text-secondary text-[18px] group-hover:text-wv-accent transition">
                                                    {{ $item['fallbackIcon'] ?? $meta['icon'] }}
                                                </span>
                                            @endif

                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-wv-text truncate group-hover:text-wv-accent transition">
                                                    {{ $item['title'] }}
                                                </div>
                                                @if (! empty($item['subtitle']))
                                                    <div class="text-xs text-wv-text-secondary truncate">{{ $item['subtitle'] }}</div>
                                                @endif
                                            </div>
                                            <span class="material-symbols-outlined text-wv-text-secondary text-[16px] opacity-0 group-hover:opacity-100 transition">
                                                arrow_forward
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
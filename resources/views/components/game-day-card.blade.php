@php
    /** @var \App\Models\Game $game */
    $game = $game ?? null;
    if (! $game) {
        return;
    }

    $homeTeam = $game->homeTeam;
    $awayTeam = $game->awayTeam;
    $category = $game->category;
    $stadium  = $game->stadium;

    // Mapeo de status a etiqueta legible + estilo del badge.
    // - scheduled   -> "Programado"  (sin score visible)
    // - in_progress -> "En vivo"     (con score visible + dot pulsante VERDE wv-success)
    // - paused      -> "Pausado"     (con score visible)
    // - completed   -> "Finalizado"  (con score visible)
    $statusMeta = [
        'scheduled'   => ['label' => __('Programado'),  'class' => 'bg-wv-accent-soft text-wv-accent border-wv-accent/40',  'showScore' => false, 'live' => false],
        'in_progress' => ['label' => __('En vivo'),     'class' => 'bg-wv-success/15 text-wv-success border-wv-success/40', 'showScore' => true,  'live' => true],
        'paused'      => ['label' => __('Pausado'),     'class' => 'bg-wv-accent-soft text-wv-accent border-wv-accent/40', 'showScore' => true,  'live' => false],
        'completed'   => ['label' => __('Finalizado'),  'class' => 'bg-wv-success/15 text-wv-success border-wv-success/40','showScore' => true,  'live' => false],
    ];
    $meta = $statusMeta[$game->status] ?? ['label' => ucfirst((string) $game->status), 'class' => 'bg-wv-surface-hover text-wv-text-secondary border-wv-border', 'showScore' => false, 'live' => false];
@endphp

<a href="{{ route('games.scoreboard', $game) }}"
   class="snap-start flex-shrink-0 w-full md:w-[calc(50%-0.5rem)] lg:w-[calc((100%-2rem)/3)] bg-wv-surface border border-wv-border rounded-card p-4 hover:border-wv-accent/50 hover:bg-wv-surface-hover transition group block">

    {{-- Header del card: horario + status badge --}}
    <div class="flex items-center justify-between mb-3">
        <span class="font-mono text-sm font-bold text-wv-text">{{ $game->scheduled_at?->format('H:i') ?? '—' }}</span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase tracking-wider {{ $meta['class'] }}">
            @if ($meta['live'])
                <span class="relative flex h-1.5 w-1.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-wv-success opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-wv-success"></span>
                </span>
            @endif
            {{ $meta['label'] }}
        </span>
    </div>

    {{-- Equipos (logos + nombres + marcador en el centro) --}}
    <div class="flex items-center gap-3">
        {{-- Local --}}
        <div class="flex-1 min-w-0 text-center">
            @if ($homeTeam?->logoUrl)
                <img src="{{ $homeTeam->logoUrl }}" alt="{{ $homeTeam->name }}"
                     class="h-12 w-12 mx-auto object-contain bg-white rounded-md border border-wv-border p-1">
            @else
                <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-wv-surface-hover text-wv-text-secondary border border-wv-border">
                    <span class="material-symbols-outlined text-[24px]">shield</span>
                </span>
            @endif
            <p class="text-xs font-bold text-wv-text truncate mt-1.5">{{ $homeTeam->short_name ?? $homeTeam->name ?? '—' }}</p>
        </div>

        {{-- Centro: marcador en una sola linea "home-away" + inning con flecha
             de mitad (top/bottom) si hay score y el juego esta en curso. --}}
        <div class="flex-shrink-0 text-center px-2 min-w-[60px]">
            @if ($meta['showScore'])
                <div class="font-mono text-2xl font-bold {{ $meta['live'] ? 'text-wv-text' : 'text-wv-text-secondary' }} leading-none">
                    {{ (int) ($game->home_score ?? 0) }}-{{ (int) ($game->away_score ?? 0) }}
                </div>
                @if ((int) ($game->current_inning ?? 0) > 0)
                    <div class="mt-1.5 text-[10px] text-wv-text-secondary font-medium leading-none">
                        Inning{{ (int) $game->current_inning }}
                        <span class="ml-0.5">{{ $game->inning_half === 'top' ? '▲' : '▼' }}</span>
                    </div>
                @endif
            @else
                <span class="text-wv-text-secondary text-xs font-bold uppercase tracking-widest">vs</span>
            @endif
        </div>

        {{-- Visitante --}}
        <div class="flex-1 min-w-0 text-center">
            @if ($awayTeam?->logoUrl)
                <img src="{{ $awayTeam->logoUrl }}" alt="{{ $awayTeam->name }}"
                     class="h-12 w-12 mx-auto object-contain bg-white rounded-md border border-wv-border p-1">
            @else
                <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-wv-surface-hover text-wv-text-secondary border border-wv-border">
                    <span class="material-symbols-outlined text-[24px]">shield</span>
                </span>
            @endif
            <p class="text-xs font-bold text-wv-text truncate mt-1.5">{{ $awayTeam->short_name ?? $awayTeam->name ?? '—' }}</p>
        </div>
    </div>

    {{-- Footer: categoria + estadio centrados en una sola linea con "|".
         Si solo hay uno de los dos, se muestra solo ese. --}}
    @php
        $hasCat = ! empty($category?->name);
        $hasSta = ! empty($stadium?->name);
    @endphp
    @if ($hasCat || $hasSta)
        <div class="border-t border-wv-border mt-4 pt-3">
            <p class="flex items-center justify-center gap-1.5 text-xs text-wv-text-secondary truncate text-center">
                @if ($hasSta)
                    <span class="material-symbols-outlined text-[14px] flex-shrink-0">stadium</span>
                    <span class="truncate">{{ $stadium->name }}</span>
                @endif
                @if ($hasCat && $hasSta)
                    <span class="text-wv-border-strong">|</span>
                @endif
                @if ($hasCat)
                    <span class="material-symbols-outlined text-[14px] flex-shrink-0">category</span>
                    <span class="truncate">{{ $category->name }}</span>
                @endif
            </p>
        </div>
    @endif
</a>
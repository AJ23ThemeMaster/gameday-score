{{--
  Partial: jugada individual del feed play-by-play (DISI-49).

  Variables:
    $play — instancia de App\Models\Play
           - $play->display_bat_order (int, asignado por PublicGameController)
             se usa como fallback cuando el bateador no esta identificado.

  Render:
    - at_bat_start       -> linea sutil slate-500 que establece el bateador
                           (o "Bateador {nro_orden} al bate" si no identificado)
    - pitch (no at_bat)  -> linea compacta slate-300 con B/S
    - outcome (out/hit/etc.) -> linea destacada con color por tipo y B/S/O/RBI
--}}
@php
    [$bg, $text, $border] = $play->badgeClasses();
    $summary = $play->summary();
    $detail = $play->detail();
    $isAtBatStart = $play->type === 'pitch' && $play->subtype === 'at_bat_start';
    $isPlainPitch = $play->type === 'pitch' && ! $isAtBatStart;

    // DISI-49: bateador identificado = tiene athlete y su nombre no es
    // el placeholder "Corredor" (DISI-27). Si no, mostramos "Bateador {n}".
    $batter = $play->batter;
    $isPlaceholderBatter = $batter
        && (
            strtolower((string) $batter->first_name) === 'corredor'
            || strtolower((string) $batter->last_name) === 'corredor'
            || trim((string) $batter->first_name) === ''
        );
    $batterIdentified = $batter && ! $isPlaceholderBatter;
    $batOrder = $play->display_bat_order ?? null;
@endphp

@if ($isAtBatStart)
    {{-- Marcador de inicio de turno (sutil, italic, slate-500) --}}
    <div class="flex items-baseline gap-2 py-1 text-[11px] text-slate-500 italic">
        <span class="w-5 text-center text-slate-600">→</span>
        @if ($batterIdentified)
            <span class="font-mono text-slate-500">#{{ $batter->number ?? '?' }}</span>
            <span class="font-semibold text-slate-400 not-italic">{{ $batter->full_name }}</span>
            @if ($batter->position)
                <span class="font-mono text-[10px] bg-slate-700/60 px-1 rounded text-slate-400 not-italic">{{ $batter->position }}</span>
            @endif
            <span class="text-slate-500">{{ __('al bate') }}</span>
        @else
            <span class="font-semibold text-slate-400 not-italic">
                {{ __('Bateador') }} {{ $batOrder ?? '?' }} {{ __('al bate') }}
            </span>
        @endif
    </div>

@elseif ($isPlainPitch)
    {{-- Pitch individual (compacta, slate-300) --}}
    <div class="flex items-baseline gap-2 py-0.5 text-xs text-slate-300">
        <span class="w-5 text-center text-slate-600">•</span>
        <span class="flex-1">{{ $summary }}</span>
        <span class="text-[10px] text-slate-500 font-mono tabular-nums">B:{{ $play->balls }} S:{{ $play->strikes }}</span>
    </div>

@else
    {{-- Outcome destacado (out, hit, walk, HBP, error, bunt, balk, robo, sustitucion) --}}
    <div class="flex items-baseline gap-2 py-1 text-xs {{ $bg }} {{ $text }} rounded px-2 my-0.5 border-l-2 {{ $border }}">
        <span class="w-5 text-center">
            @if ($play->type === 'out') ❌
            @elseif ($play->type === 'hit') ⚾
            @elseif ($play->type === 'walk') 🚶
            @elseif ($play->type === 'hbp') ⚠
            @elseif ($play->type === 'error') ⚠
            @elseif ($play->type === 'bunt') ⛔
            @elseif ($play->type === 'balk') ⚠
            @elseif ($play->type === 'runner_movement') 🏃
            @elseif ($play->type === 'substitution') 🔄
            @else •
            @endif
        </span>

        @if ($batterIdentified)
            <span class="font-mono opacity-70">#{{ $batter->number ?? '?' }}</span>
        @endif

        <span class="flex-1 font-semibold">
            {{ $summary }}
            @if ($detail)
                <span class="opacity-70 font-normal">({{ $detail }})</span>
            @endif
        </span>

        @if ($play->outs_after > 0)
            <span class="text-[10px] opacity-70 tabular-nums">
                {{ $play->outs_after }} {{ $play->outs_after === 1 ? __('out') : __('outs') }}
            </span>
        @endif

        @if ($play->runs_scored > 0)
            <span class="text-[10px] font-bold text-emerald-300 tabular-nums">
                +{{ $play->runs_scored }}R
                @if ($play->rbi > 0)
                    <span class="opacity-75">({{ $play->rbi }} {{ __('RBI') }})</span>
                @endif
            </span>
        @endif
    </div>
@endif

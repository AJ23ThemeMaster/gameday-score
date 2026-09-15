{{--
  Partial: jugada individual del feed play-by-play (DISI-48).

  Variables:
    $play — instancia de App\Models\Play

  Render:
    - at_bat_start       -> linea sutil slate-500 que establece el bateador
    - pitch (no at_bat)  -> linea compacta slate-300 con B/S
    - outcome (out/hit/etc.) -> linea destacada con color por tipo y B/S/O/RBI
--}}
@php
    [$bg, $text, $border] = $play->badgeClasses();
    $summary = $play->summary();
    $detail = $play->detail();
    $isAtBatStart = $play->type === 'pitch' && $play->subtype === 'at_bat_start';
    $isPlainPitch = $play->type === 'pitch' && ! $isAtBatStart;
@endphp

@if ($isAtBatStart)
    {{-- Marcador de inicio de turno (sutil, italic, slate-500) --}}
    <div class="flex items-baseline gap-2 py-1 text-[11px] text-slate-500 italic">
        <span class="w-5 text-center text-slate-600">→</span>
        @if ($play->batter)
            <span class="font-mono text-slate-500">#{{ $play->batter->number ?? '?' }}</span>
            <span class="font-semibold text-slate-400 not-italic">{{ $play->batter->full_name }}</span>
            @if ($play->batter->position)
                <span class="font-mono text-[10px] bg-slate-700/60 px-1 rounded text-slate-400 not-italic">{{ $play->batter->position }}</span>
            @endif
            <span class="text-slate-500">{{ __('al bate') }}</span>
        @else
            <span class="text-slate-500">{{ __('Bateador desconocido al bate') }}</span>
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

        @if ($play->batter)
            <span class="font-mono opacity-70">#{{ $play->batter->number ?? '?' }}</span>
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

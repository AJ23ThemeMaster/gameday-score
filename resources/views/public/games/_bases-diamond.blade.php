{{--
  Partial: diamante de bases para la vista publica (DISI-54).
  Variables:
    $bases - array con keys 'first', 'second', 'third' (cada una con athlete_id o null)

  Layout (vista desde arriba del campo):
        [2B]
       /    \
    [3B]    [1B]
       \    /
       [HOME]

  - Bases ocupadas: amarillo (bg-yellow-400) + label blanca (1B/2B/3B) - heredan estilo del scoreboard
  - Bases vacias: slate-700/60 + label apagada
  - Home plate: pentagon (clip-path) en slate, siempre visible como referencia
  - SVG overlay dibuja el contorno del diamante conectando las 4 bases
  - Tamano compacto: ~112x112 px (w-28 h-28) para encajar en la columna entre Pitcheando y Al bate
--}}
@php
    $b = $bases ?? [];
    $hasFirst = ! empty($b['first']);
    $hasSecond = ! empty($b['second']);
    $hasThird = ! empty($b['third']);
@endphp
<div class="relative w-28 h-28 mx-auto" data-diamond>
    {{-- Contorno del diamante (SVG): lineas entre 2B (top), 1B (right), H (bottom), 3B (left) --}}
    <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet">
        <polygon points="50,12 88,50 50,88 12,50"
                 fill="rgba(51, 65, 85, 0.25)"
                 stroke="rgb(100, 116, 139)"
                 stroke-width="1.5"
                 stroke-linejoin="round"
                 vector-effect="non-scaling-stroke" />
    </svg>

    {{-- 2B (arriba) --}}
    <div class="absolute" style="top: 4%; left: 50%; transform: translateX(-50%);">
        <div class="w-7 h-7 rotate-45 border {{ $hasSecond ? 'bg-yellow-400 border-yellow-300 shadow-md shadow-yellow-400/30' : 'bg-slate-700/60 border-slate-600' }} flex items-center justify-center">
            <span class="text-[9px] font-bold {{ $hasSecond ? 'text-yellow-950' : 'text-slate-500' }}" style="transform: rotate(-45deg);">2B</span>
        </div>
    </div>

    {{-- 1B (derecha) --}}
    <div class="absolute" style="top: 50%; right: 4%; transform: translateY(-50%);">
        <div class="w-7 h-7 rotate-45 border {{ $hasFirst ? 'bg-yellow-400 border-yellow-300 shadow-md shadow-yellow-400/30' : 'bg-slate-700/60 border-slate-600' }} flex items-center justify-center">
            <span class="text-[9px] font-bold {{ $hasFirst ? 'text-yellow-950' : 'text-slate-500' }}" style="transform: rotate(-45deg);">1B</span>
        </div>
    </div>

    {{-- 3B (izquierda) --}}
    <div class="absolute" style="top: 50%; left: 4%; transform: translateY(-50%);">
        <div class="w-7 h-7 rotate-45 border {{ $hasThird ? 'bg-yellow-400 border-yellow-300 shadow-md shadow-yellow-400/30' : 'bg-slate-700/60 border-slate-600' }} flex items-center justify-center">
            <span class="text-[9px] font-bold {{ $hasThird ? 'text-yellow-950' : 'text-slate-500' }}" style="transform: rotate(-45deg);">3B</span>
        </div>
    </div>

    {{-- HOME plate (abajo, pentagon) --}}
    <div class="absolute" style="bottom: 4%; left: 50%; transform: translateX(-50%);">
        <div class="relative w-8 h-8 flex items-center justify-center">
            <div class="absolute inset-0 bg-slate-600/70 border border-slate-500" style="clip-path: polygon(50% 0%, 100% 38%, 82% 100%, 18% 100%, 0% 38%);"></div>
            <span class="relative text-[8px] font-black text-slate-300 tracking-wider">HOME</span>
        </div>
    </div>
</div>

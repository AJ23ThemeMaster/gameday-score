<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Box score') }} — {{ $game->homeTeam->short_name ?? $game->homeTeam->name }} vs {{ $game->awayTeam->short_name ?? $game->awayTeam->name }}
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('games.scoreboard', $game) }}"
                   class="text-sm text-gray-600 hover:text-gray-900 underline">
                    {{ __('← Volver al scoreboard') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('partials._flash')

            {{-- ============================================================ --}}
            {{-- CARD DE BOX SCORE (1:1, lo que se exporta como imagen)        --}}
            {{-- ============================================================ --}}
            <div class="bg-white shadow-lg sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ __('Vista previa para compartir') }}
                    </h3>
                    <div class="flex gap-2">
                        <button type="button" id="share-boxscore"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-md transition">
                            📤 {{ __('Compartir como imagen') }}
                        </button>
                        <button type="button" id="download-boxscore"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                            ⬇ {{ __('Descargar PNG') }}
                        </button>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mb-3">
                    {{ __('La imagen generada es cuadrada (720x720), incluye logos de los equipos, inning-by-inning con carreras/hits/errores y pitchers/MVP del juego.') }}
                </p>

                <div class="flex justify-center">
                    {{-- Contenedor de escala: el card mide 720px de ancho nativo.
                         La altura es natural (auto) para que el preview no tenga
                         huecos vacios. Solo durante el export se fuerza 720x720. --}}
                    <div id="box-score-wrapper" class="w-full max-w-[720px]">
                        <div id="box-score-card"
                             class="mx-auto bg-indigo-900 text-white shadow-2xl rounded-xl flex flex-col overflow-hidden border-0 outline-none"
                             style="width: 720px; transform-origin: top center;">

                        {{-- Header --}}
                        <div class="px-8 py-6 border-b border-white/20 flex-shrink-0 export-grow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-widest text-indigo-200">Box Score</p>
                                    <p class="text-2xl font-bold">
                                        @if ($game->category)
                                            {{ $game->category->name }}
                                        @endif
                                        @if ($game->tournament)
                                            · {{ $game->tournament->name }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-widest text-indigo-200">Final</p>
                                    <p class="text-lg font-semibold">
                                        {{ optional($game->ended_at)->format('d M Y') ?? $game->scheduled_at?->format('d M Y') }}
                                    </p>
                                    @if ($game->stadium)
                                        <p class="text-sm text-indigo-200">{{ $game->stadium->name }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Equipos con logos y score CENTRADO entre ellos --}}
                        <div class="px-8 py-6 border-b border-white/20 flex-shrink-0 export-grow">
                            <div class="flex items-center justify-center gap-6">
                                {{-- Local (logo + nombre a la izquierda) --}}
                                <div class="flex flex-col items-center gap-3 w-1/3">
                                    @if ($game->homeTeam->logoUrl)
                                        <img src="{{ $game->homeTeam->logoUrl }}" alt="{{ $game->homeTeam->name }}"
                                             class="h-24 w-24 object-contain bg-white/10 rounded-2xl p-2">
                                    @else
                                        <div class="h-24 w-24 rounded-2xl bg-white/10 flex items-center justify-center text-2xl font-bold">
                                            {{ mb_substr($game->homeTeam->name, 0, 3) }}
                                        </div>
                                    @endif
                                    <div class="text-center min-w-0">
                                        <p class="text-xs uppercase tracking-widest text-indigo-200">Local</p>
                                        <p class="text-lg font-bold leading-tight truncate" title="{{ $game->homeTeam->name }}">{{ $game->homeTeam->name }}</p>
                                    </div>
                                </div>

                                {{-- Score CENTRADO --}}
                                <div class="text-center flex-shrink-0 px-4">
                                    <p class="text-6xl font-black">{{ $score['home'] }} - {{ $score['away'] }}</p>
                                </div>

                                {{-- Visitante (logo + nombre a la derecha) --}}
                                <div class="flex flex-col items-center gap-3 w-1/3">
                                    @if ($game->awayTeam->logoUrl)
                                        <img src="{{ $game->awayTeam->logoUrl }}" alt="{{ $game->awayTeam->name }}"
                                             class="h-24 w-24 object-contain bg-white/10 rounded-2xl p-2">
                                    @else
                                        <div class="h-24 w-24 rounded-2xl bg-white/10 flex items-center justify-center text-2xl font-bold">
                                            {{ mb_substr($game->awayTeam->name, 0, 3) }}
                                        </div>
                                    @endif
                                    <div class="text-center min-w-0">
                                        <p class="text-xs uppercase tracking-widest text-indigo-200">Visitante</p>
                                        <p class="text-lg font-bold leading-tight truncate" title="{{ $game->awayTeam->name }}">{{ $game->awayTeam->name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Line score: inning-by-inning --}}
                        <div class="px-8 py-6 border-b border-white/20 flex-shrink-0 export-grow">
                            <table class="w-full text-center">
                                <thead>
                                    <tr class="text-xs uppercase tracking-widest text-indigo-200 border-b border-white/20">
                                        <th class="py-2 text-left pl-4">Equipo</th>
                                        @for ($i = 1; $i <= $totalInnings; $i++)
                                            <th class="py-2 px-2">{{ $i }}</th>
                                        @endfor
                                        <th class="py-2 px-3 bg-white/10 font-bold">R</th>
                                        <th class="py-2 px-3 bg-white/10 font-bold">H</th>
                                        <th class="py-2 px-3 bg-white/10 font-bold">E</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Visitante (top) --}}
                                    <tr class="text-lg font-medium border-b border-white/10">
                                        <td class="py-2 text-left pl-4 truncate" title="{{ $game->awayTeam->name }}">{{ $game->awayTeam->name }}</td>
                                        @for ($i = 1; $i <= $totalInnings; $i++)
                                            <td class="py-2 px-2">
                                                @if ($i > $maxPlayedInning)
                                                    @if ($gameEnded)
                                                        <span class="text-indigo-400 font-medium">X</span>
                                                    @elseif (in_array($game->status, ['in_progress', 'paused'], true))
                                                        <span class="text-indigo-400 font-medium">-</span>
                                                    @endif
                                                @else
                                                    {{ $lineScore[$i]['away'] }}
                                                @endif
                                            </td>
                                        @endfor
                                        <td class="py-2 px-3 bg-white/10 font-bold">{{ $score['away'] }}</td>
                                        <td class="py-2 px-3 bg-white/10">{{ $score['totals_hits']['away'] }}</td>
                                        <td class="py-2 px-3 bg-white/10">{{ $score['totals_errors']['away'] }}</td>
                                    </tr>
                                    {{-- Local (bottom) --}}
                                    <tr class="text-lg font-medium">
                                        <td class="py-2 text-left pl-4 truncate" title="{{ $game->homeTeam->name }}">{{ $game->homeTeam->name }}</td>
                                        @for ($i = 1; $i <= $totalInnings; $i++)
                                            <td class="py-2 px-2">
                                                @if ($i > $maxPlayedInning)
                                                    @if ($gameEnded)
                                                        <span class="text-indigo-400 font-medium">X</span>
                                                    @elseif (in_array($game->status, ['in_progress', 'paused'], true))
                                                        <span class="text-indigo-400 font-medium">-</span>
                                                    @endif
                                                @else
                                                    {{ $lineScore[$i]['home'] }}
                                                @endif
                                            </td>
                                        @endfor
                                        <td class="py-2 px-3 bg-white/10 font-bold">{{ $score['home'] }}</td>
                                        <td class="py-2 px-3 bg-white/10">{{ $score['totals_hits']['home'] }}</td>
                                        <td class="py-2 px-3 bg-white/10">{{ $score['totals_errors']['home'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Pitchers + MVP (solo se muestran los asignados) --}}
                        @if ($game->winningPitcher || $game->losingPitcher || $game->savePitcher || $game->mvp)
                            <div class="px-8 py-6 border-b border-white/20 flex-shrink-0 export-grow">
                                <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                                    @if ($game->winningPitcher)
                                        <div class="flex items-center gap-2">
                                            <span class="text-indigo-200 font-semibold uppercase tracking-wider text-xs w-32">Pitcher ganador</span>
                                            <span class="font-semibold">#{{ $game->winningPitcher->number }} {{ $game->winningPitcher->full_name }}</span>
                                        </div>
                                    @endif
                                    @if ($game->losingPitcher)
                                        <div class="flex items-center gap-2">
                                            <span class="text-indigo-200 font-semibold uppercase tracking-wider text-xs w-32">Pitcher perdedor</span>
                                            <span class="font-semibold">#{{ $game->losingPitcher->number }} {{ $game->losingPitcher->full_name }}</span>
                                        </div>
                                    @endif
                                    @if ($game->savePitcher)
                                        <div class="flex items-center gap-2">
                                            <span class="text-indigo-200 font-semibold uppercase tracking-wider text-xs w-32">Juego salvado</span>
                                            <span class="font-semibold">#{{ $game->savePitcher->number }} {{ $game->savePitcher->full_name }}</span>
                                        </div>
                                    @endif
                                    @if ($game->mvp)
                                        <div class="flex items-center gap-2">
                                            <span class="text-yellow-300 font-semibold uppercase tracking-wider text-xs w-32">⭐ MVP</span>
                                            <span class="font-semibold">#{{ $game->mvp->number }} {{ $game->mvp->full_name }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Footer --}}
                        <div class="px-8 py-4 flex items-center justify-between text-xs text-indigo-200 border-t border-white/20 flex-shrink-0">
                            <span>Generado con ⚾ Gameday Score</span>
                            <span>{{ now()->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- FORMULARIO: Asignar pitchers + MVP (solo admin/anotador)      --}}
            {{-- ============================================================ --}}
            @can('score', $game)
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                        {{ __('Asignar pitchers y MVP del juego') }}
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Estos atletas se mostraran en el box score y en la imagen para compartir.') }}
                    </p>

                    <form method="POST" action="{{ route('games.box-score.attributions', $game) }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('Pitcher ganador (G)') }}
                                    <span class="text-xs text-gray-500 font-normal">— {{ __('roster del ganador') }}</span>
                                </label>
                                <select name="winning_pitcher_id" class="block w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— Sin asignar —</option>
                                    @forelse ($winningRoster as $a)
                                        <option value="{{ $a->id }}" @selected($game->winning_pitcher_id == $a->id)>
                                            #{{ $a->number }} {{ $a->full_name }} ({{ $a->team->short_name ?? $a->team->name }})
                                        </option>
                                    @empty
                                        <option value="" disabled>{{ __('Sin roster del equipo ganador.') }}</option>
                                    @endforelse
                                </select>
                                @error('winning_pitcher_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('Pitcher perdedor (P)') }}
                                    <span class="text-xs text-gray-500 font-normal">— {{ __('roster del perdedor') }}</span>
                                </label>
                                <select name="losing_pitcher_id" class="block w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— Sin asignar —</option>
                                    @forelse ($losingRoster as $a)
                                        <option value="{{ $a->id }}" @selected($game->losing_pitcher_id == $a->id)>
                                            #{{ $a->number }} {{ $a->full_name }} ({{ $a->team->short_name ?? $a->team->name }})
                                        </option>
                                    @empty
                                        <option value="" disabled>{{ __('Sin roster del equipo perdedor.') }}</option>
                                    @endforelse
                                </select>
                                @error('losing_pitcher_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('Juego salvado (SV)') }}
                                    <span class="text-xs text-gray-500 font-normal">— {{ __('roster del ganador') }}</span>
                                </label>
                                <select name="save_pitcher_id" class="block w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— N/A —</option>
                                    @forelse ($winningRoster as $a)
                                        <option value="{{ $a->id }}" @selected($game->save_pitcher_id == $a->id)>
                                            #{{ $a->number }} {{ $a->full_name }} ({{ $a->team->short_name ?? $a->team->name }})
                                        </option>
                                    @empty
                                        <option value="" disabled>{{ __('Sin roster del equipo ganador.') }}</option>
                                    @endforelse
                                </select>
                                @error('save_pitcher_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('MVP del juego') }}
                                    <span class="text-xs text-gray-500 font-normal">— {{ __('roster del ganador') }}</span>
                                </label>
                                <select name="mvp_athlete_id" class="block w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— Sin asignar —</option>
                                    @forelse ($winningRoster as $a)
                                        <option value="{{ $a->id }}" @selected($game->mvp_athlete_id == $a->id)>
                                            #{{ $a->number }} {{ $a->full_name }} ({{ $a->team->short_name ?? $a->team->name }})
                                        </option>
                                    @empty
                                        <option value="" disabled>{{ __('Sin roster del equipo ganador.') }}</option>
                                    @endforelse
                                </select>
                                @error('mvp_athlete_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                {{ __('Guardar atribuciones') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

        </div>
    </div>

    {{-- Script para generar imagen 1:1 via html2canvas (cargado solo en esta pagina) --}}
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>
    <script defer>
        document.addEventListener('DOMContentLoaded', function () {
            const card = document.getElementById('box-score-card');
            if (!card) return;

            // ---- Escalado responsivo del preview (el card tiene altura NATURAL;
            //      solo se escala horizontalmente para caber en el viewport) ----
            const NATIVE_W = 720;
            function fitCard() {
                const wrapperWidth = card.parentElement.clientWidth;
                const scale = Math.min(1, wrapperWidth / NATIVE_W);
                card.style.transform = 'scale(' + scale + ')';
                const naturalHeight = card.scrollHeight;
                card.parentElement.style.height = (naturalHeight * scale) + 'px';
            }
            fitCard();
            window.addEventListener('resize', fitCard);

            // Convierte <img src="http://..."> a data URL base64 para evitar
            // problemas de CORS al capturar con html2canvas. Si falla, deja
            // el src original (la imagen saldra vacia pero el resto se vera OK).
            async function inlineImages(rootEl) {
                const imgs = rootEl.querySelectorAll('img');
                await Promise.all([...imgs].map(async (img) => {
                    const src = img.getAttribute('src');
                    if (!src || src.startsWith('data:')) return;
                    try {
                        const res = await fetch(src, { mode: 'cors', credentials: 'omit' });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const blob = await res.blob();
                        const dataUrl = await new Promise((resolve, reject) => {
                            const reader = new FileReader();
                            reader.onload = () => resolve(reader.result);
                            reader.onerror = reject;
                            reader.readAsDataURL(blob);
                        });
                        img.src = dataUrl;
                    } catch (e) {
                        console.warn('No se pudo inlinear imagen:', src, e.message);
                    }
                }));
            }

            // ---- Snapshot para descargar/compartir (720x720 con card clonado) ----
            async function snapshot(scale = 1) {
                // 1) Esperar a que las imagenes reales del DOM terminen de cargar
                await inlineImages(card);

                // 2) Restaurar escala del preview (la card queda a tamano natural)
                const originalTransform = card.style.transform;
                card.style.transform = 'none';

                // 3) Clonar el card para exportarlo sin afectar el preview
                const clone = card.cloneNode(true);
                clone.style.transform = 'none';
                clone.style.width = NATIVE_W + 'px';
                clone.style.height = 'auto';
                clone.style.boxShadow = 'none';

                // 4) Envoltorio 720x720 con fondo indigo para forzar el 1:1
                const wrapper = document.createElement('div');
                wrapper.style.cssText = [
                    'position: fixed',
                    'top: -100000px',
                    'left: -100000px',
                    'width: ' + NATIVE_W + 'px',
                    'height: ' + NATIVE_W + 'px',
                    'background: linear-gradient(135deg, #1e1b4b 0%, #3730a3 50%, #1e3a8a 100%)',
                    'display: flex',
                    'flex-direction: column',
                    'padding: 40px',
                    'box-sizing: border-box',
                    'z-index: -1',
                ].join(';');
                document.body.appendChild(wrapper);
                wrapper.appendChild(clone);

                // 5) Pequeña pausa para que el navegador renderice el clon + imagenes
                await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));

                const canvas = await html2canvas(wrapper, {
                    backgroundColor: null,
                    scale: scale,
                    width: NATIVE_W,
                    height: NATIVE_W,
                    useCORS: true,
                    allowTaint: true,
                    logging: false,
                });

                // 6) Limpieza
                document.body.removeChild(wrapper);
                card.style.transform = originalTransform;

                return canvas;
            }

            const downloadBtn = document.getElementById('download-boxscore');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', async function () {
                    downloadBtn.disabled = true;
                    downloadBtn.textContent = '⏳ Generando...';
                    try {
                        const canvas = await snapshot(2);
                        const link = document.createElement('a');
                        const ts = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
                        link.download = 'boxscore-{{ $game->id }}-' + ts + '.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                    } catch (e) {
                        alert('No se pudo generar la imagen: ' + e.message);
                    } finally {
                        downloadBtn.disabled = false;
                        downloadBtn.textContent = '⬇ {{ __('Descargar PNG') }}';
                    }
                });
            }

            const shareBtn = document.getElementById('share-boxscore');
            if (shareBtn) {
                shareBtn.addEventListener('click', async function () {
                    shareBtn.disabled = true;
                    shareBtn.textContent = '⏳ Generando...';
                    try {
                        const canvas = await snapshot(2);
                        canvas.toBlob(async function (blob) {
                            const file = new File([blob], 'boxscore-{{ $game->id }}.png', { type: 'image/png' });

                            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                                try {
                                    await navigator.share({
                                        files: [file],
                                        title: 'Box score — {{ $game->homeTeam->name }} vs {{ $game->awayTeam->name }}',
                                        text: '{{ $score["home"] }} - {{ $score["away"] }} · Gameday Score',
                                    });
                                } catch (e) {
                                    if (e.name !== 'AbortError') {
                                        alert('No se pudo compartir: ' + e.message);
                                    }
                                }
                            } else {
                                const link = document.createElement('a');
                                link.download = 'boxscore-{{ $game->id }}.png';
                                link.href = canvas.toDataURL('image/png');
                                link.click();
                                alert('Tu navegador no soporta Web Share API. Se descargo la imagen.');
                            }
                        }, 'image/png');
                    } catch (e) {
                        alert('No se pudo generar la imagen: ' + e.message);
                    } finally {
                        shareBtn.disabled = false;
                        shareBtn.textContent = '📤 {{ __('Compartir como imagen') }}';
                    }
                });
            }
        });
    </script>
</x-app-layout>

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
                    {{ __('La imagen generada es cuadrada (1080x1080), incluye logos de los equipos, inning-by-inning con carreras/hits/errores y pitchers/MVP del juego.') }}
                </p>

                <div class="overflow-auto">
                    {{-- Contenedor fijo 1:1 que se renderiza a 1080x1080 al exportar --}}
                    <div id="box-score-card"
                         class="mx-auto bg-gradient-to-br from-indigo-900 via-indigo-800 to-blue-900 text-white relative"
                         style="width: 1080px; height: 1080px; transform-origin: top left;">

                        {{-- Header --}}
                        <div class="px-8 py-6 border-b border-white/20">
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

                        {{-- Equipos con logos --}}
                        <div class="px-8 py-6 border-b border-white/20">
                            <div class="grid grid-cols-2 gap-8 items-center">
                                {{-- Local --}}
                                <div class="flex items-center gap-4">
                                    @if ($game->homeTeam->logoUrl)
                                        <img src="{{ $game->homeTeam->logoUrl }}" alt="{{ $game->homeTeam->name }}"
                                             class="h-20 w-20 object-contain bg-white/10 rounded-full p-1">
                                    @else
                                        <div class="h-20 w-20 rounded-full bg-white/10 flex items-center justify-center text-2xl font-bold">
                                            {{ mb_substr($game->homeTeam->short_name ?? $game->homeTeam->name, 0, 3) }}
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <p class="text-xs uppercase tracking-widest text-indigo-200">Local</p>
                                        <p class="text-3xl font-bold">{{ $game->homeTeam->short_name ?? $game->homeTeam->name }}</p>
                                        <p class="text-5xl font-black mt-2">{{ $score['home'] }}</p>
                                    </div>
                                </div>

                                {{-- Visitante --}}
                                <div class="flex items-center gap-4 flex-row-reverse text-right">
                                    @if ($game->awayTeam->logoUrl)
                                        <img src="{{ $game->awayTeam->logoUrl }}" alt="{{ $game->awayTeam->name }}"
                                             class="h-20 w-20 object-contain bg-white/10 rounded-full p-1">
                                    @else
                                        <div class="h-20 w-20 rounded-full bg-white/10 flex items-center justify-center text-2xl font-bold">
                                            {{ mb_substr($game->awayTeam->short_name ?? $game->awayTeam->name, 0, 3) }}
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <p class="text-xs uppercase tracking-widest text-indigo-200">Visitante</p>
                                        <p class="text-3xl font-bold">{{ $game->awayTeam->short_name ?? $game->awayTeam->name }}</p>
                                        <p class="text-5xl font-black mt-2">{{ $score['away'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Line score: inning-by-inning --}}
                        <div class="px-8 py-6 border-b border-white/20">
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
                                        <td class="py-2 text-left pl-4">{{ $game->awayTeam->short_name ?? $game->awayTeam->name }}</td>
                                        @for ($i = 1; $i <= $totalInnings; $i++)
                                            <td class="py-2 px-2">{{ $lineScore[$i]['away'] ?: '' }}</td>
                                        @endfor
                                        <td class="py-2 px-3 bg-white/10 font-bold">{{ $score['away'] }}</td>
                                        <td class="py-2 px-3 bg-white/10">{{ $score['totals_hits']['away'] }}</td>
                                        <td class="py-2 px-3 bg-white/10">{{ $score['totals_errors']['away'] }}</td>
                                    </tr>
                                    {{-- Local (bottom) --}}
                                    <tr class="text-lg font-medium">
                                        <td class="py-2 text-left pl-4">{{ $game->homeTeam->short_name ?? $game->homeTeam->name }}</td>
                                        @for ($i = 1; $i <= $totalInnings; $i++)
                                            <td class="py-2 px-2">{{ $lineScore[$i]['home'] ?: '' }}</td>
                                        @endfor
                                        <td class="py-2 px-3 bg-white/10 font-bold">{{ $score['home'] }}</td>
                                        <td class="py-2 px-3 bg-white/10">{{ $score['totals_hits']['home'] }}</td>
                                        <td class="py-2 px-3 bg-white/10">{{ $score['totals_errors']['home'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Pitchers + MVP --}}
                        <div class="px-8 py-6 border-b border-white/20">
                            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-indigo-200 font-semibold uppercase tracking-wider text-xs w-32">Pitcher ganador</span>
                                    <span class="font-semibold">
                                        @if ($game->winningPitcher)
                                            #{{ $game->winningPitcher->number }} {{ $game->winningPitcher->full_name }}
                                        @else
                                            <span class="text-indigo-300 italic">Sin asignar</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-indigo-200 font-semibold uppercase tracking-wider text-xs w-32">Pitcher perdedor</span>
                                    <span class="font-semibold">
                                        @if ($game->losingPitcher)
                                            #{{ $game->losingPitcher->number }} {{ $game->losingPitcher->full_name }}
                                        @else
                                            <span class="text-indigo-300 italic">Sin asignar</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-indigo-200 font-semibold uppercase tracking-wider text-xs w-32">Juego salvado</span>
                                    <span class="font-semibold">
                                        @if ($game->savePitcher)
                                            #{{ $game->savePitcher->number }} {{ $game->savePitcher->full_name }}
                                        @else
                                            <span class="text-indigo-300 italic">N/A</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-yellow-300 font-semibold uppercase tracking-wider text-xs w-32">⭐ MVP</span>
                                    <span class="font-semibold">
                                        @if ($game->mvp)
                                            #{{ $game->mvp->number }} {{ $game->mvp->full_name }}
                                        @else
                                            <span class="text-indigo-300 italic">Sin asignar</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-8 py-4 absolute bottom-0 left-0 right-0 flex items-center justify-between text-xs text-indigo-200">
                            <span>Generado con ⚾ Gameday Score</span>
                            <span>{{ now()->format('d/m/Y H:i') }}</span>
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
                                </label>
                                <select name="winning_pitcher_id" class="block w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— Sin asignar —</option>
                                    @foreach ($athletes as $a)
                                        <option value="{{ $a->id }}" @selected($game->winning_pitcher_id == $a->id)>
                                            #{{ $a->number }} {{ $a->full_name }} ({{ $a->team->short_name ?? $a->team->name }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('winning_pitcher_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('Pitcher perdedor (P)') }}
                                </label>
                                <select name="losing_pitcher_id" class="block w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— Sin asignar —</option>
                                    @foreach ($athletes as $a)
                                        <option value="{{ $a->id }}" @selected($game->losing_pitcher_id == $a->id)>
                                            #{{ $a->number }} {{ $a->full_name }} ({{ $a->team->short_name ?? $a->team->name }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('losing_pitcher_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('Juego salvado (SV)') }}
                                </label>
                                <select name="save_pitcher_id" class="block w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— N/A —</option>
                                    @foreach ($athletes as $a)
                                        <option value="{{ $a->id }}" @selected($game->save_pitcher_id == $a->id)>
                                            #{{ $a->number }} {{ $a->full_name }} ({{ $a->team->short_name ?? $a->team->name }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('save_pitcher_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('MVP del juego') }}
                                </label>
                                <select name="mvp_athlete_id" class="block w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— Sin asignar —</option>
                                    @foreach ($athletes as $a)
                                        <option value="{{ $a->id }}" @selected($game->mvp_athlete_id == $a->id)>
                                            #{{ $a->number }} {{ $a->full_name }} ({{ $a->team->short_name ?? $a->team->name }})
                                        </option>
                                    @endforeach
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

            async function snapshot(scale = 1) {
                // Asegurar que el card esta a tamaño completo antes de capturar
                const originalTransform = card.style.transform;
                card.style.transform = 'scale(1)';

                const canvas = await html2canvas(card, {
                    backgroundColor: null,
                    scale: scale,
                    width: 1080,
                    height: 1080,
                    useCORS: true,
                    allowTaint: true,
                });

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
                                        title: 'Box score — {{ $game->homeTeam->short_name ?? $game->homeTeam->name }} vs {{ $game->awayTeam->short_name ?? $game->awayTeam->name }}',
                                        text: '{{ $score["home"] }} - {{ $score["away"] }} · Gameday Score',
                                    });
                                } catch (e) {
                                    // usuario cancelo o no soporta
                                    if (e.name !== 'AbortError') {
                                        alert('No se pudo compartir: ' + e.message);
                                    }
                                }
                            } else {
                                // Fallback: descarga directa
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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Gameday Score') }}</title>

    {{-- WattVision: tipografia Inter (feature/style/wattvision) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased font-inter bg-wv-bg text-wv-text min-h-screen">

@php
    // Juegos del dia publicos. try/catch defensivo: si la tabla games aun no
    // existe (instalacion fresca sin migrar) o cualquier otra columna/relacion
    // falla, no rompemos el render del welcome.
    $todayGames = collect();
    try {
        $todayGames = \App\Models\Game::query()
            ->public() // scope: is_public = true
            ->whereIn('status', ['scheduled', 'in_progress', 'paused', 'completed'])
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->with(['homeTeam', 'awayTeam', 'category', 'stadium'])
            ->orderBy('scheduled_at')
            ->get();
    } catch (\Throwable $e) {
        $todayGames = collect();
    }
@endphp

    <div class="min-h-screen flex flex-col">
        {{-- Top bar --}}
        <header class="w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">⚾</span>
                    <span class="text-xl font-bold text-wv-text">Gameday Score</span>
                </div>
                <nav class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent rounded-card text-sm font-semibold transition">
                                {{ __('Panel de control') }}
                            </a>
                        @else
                            {{-- DISI-delegado: registro publico deshabilitado.
                                 Solo el admin crea usuarios desde el panel. --}}
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent rounded-card text-sm font-semibold transition">
                                {{ __('Iniciar sesión') }}
                            </a>
                        @endauth
                    @endif
                </nav>
            </div>
        </header>

        {{-- Hero --}}
        <main class="flex-1 flex items-center">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                <span class="inline-block text-6xl mb-6">⚾</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight mb-4 text-wv-text">
                    Gameday Score
                </h1>
                <p class="text-xl sm:text-2xl text-wv-text-secondary mb-2">
                    {{ __('Anotación profesional de béisbol y sófbol') }}
                </p>
                <p class="text-base text-wv-text-secondary max-w-2xl mx-auto mb-10">
                    {{ __('Gestiona tus juegos, equipos, atletas y árbitros desde un solo lugar. Marca un juego como público y comparte el avance en vivo con quien quieras.') }}
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="px-6 py-3 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent rounded-card text-base font-semibold transition">
                            {{ __('Ir a mi panel') }}
                        </a>
                    @else
                        {{-- DISI-delegado: registro publico deshabilitado.
                             Los usuarios los crea el admin desde el panel. --}}
                        <a href="{{ route('login') }}"
                           class="px-6 py-3 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent rounded-card text-base font-semibold transition">
                            {{ __('Iniciar sesión') }}
                        </a>
                    @endauth
                    <a href="/legacy/" target="_blank"
                       class="px-6 py-3 border border-wv-border hover:bg-wv-surface-hover text-wv-text rounded-card text-base font-semibold transition">
                        {{ __('Abrir PWA clásica') }} ↗
                    </a>
                </div>
            </div>
        </main>

        {{-- ===========================================================
             Juegos del dia (publico)
             Solo juegos con is_public=true. Cada card abre el live
             publico (/game/live/{token}) gracias a la prop publicMode
             del componente <x-game-day-card>.
             =========================================================== --}}
        @if ($todayGames->count() > 0)
            {{-- x-data vacio: Alpine solo expone $refs dentro de un scope
                 x-data. Sin este wrapper, $refs.todayGamesCarousel es
                 undefined y los botones prev/next no hacen nada. --}}
            <section x-data="{}" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-wv-text">{{ __('Juegos del dia') }}</h2>
                        <p class="text-sm text-wv-text-secondary mt-1">
                            {{ __('Sigue el avance en vivo de los partidos habilitados para publico.') }}
                        </p>
                    </div>

                    {{-- Flechas del carousel: solo si hay mas de 3 juegos
                         (en lg caben 3 cards, el 4o ya hace overflow). --}}
                    @if ($todayGames->count() > 3)
                        <div class="hidden md:flex items-center gap-2">
                            <button type="button"
                                    x-on:click="$refs.todayGamesCarousel.scrollBy({ left: -340, behavior: 'smooth' })"
                                    aria-label="{{ __('Anterior') }}"
                                    class="p-2 rounded-card border border-wv-border hover:bg-wv-surface-hover text-wv-text transition">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </button>
                            <button type="button"
                                    x-on:click="$refs.todayGamesCarousel.scrollBy({ left: 340, behavior: 'smooth' })"
                                    aria-label="{{ __('Siguiente') }}"
                                    class="p-2 rounded-card border border-wv-border hover:bg-wv-surface-hover text-wv-text transition">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </button>
                        </div>
                    @endif
                </div>

                <div x-ref="todayGamesCarousel"
                     class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2 nav-scroll">
                    @foreach ($todayGames as $g)
                        <x-game-day-card :game="$g" publicMode />
                    @endforeach
                </div>
            </section>
        @else
            {{-- Empty state propio del welcome: sin "Mis juegos" ni enlace al
                 listado autenticado (eso es del dashboard). Aqui solo
                 invitamos a volver mas tarde. --}}
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="bg-wv-surface border border-wv-border rounded-card p-8 text-center">
                    <span class="material-symbols-outlined text-wv-text-secondary text-[40px] mb-2">sports_baseball</span>
                    <h2 class="text-lg font-semibold text-wv-text mb-1">{{ __('Hoy no hay juegos publicos') }}</h2>
                    <p class="text-sm text-wv-text-secondary max-w-md mx-auto">
                        {{ __('Cuando un administrador habilite un juego para publico, aparecera aqui con su marcador y enlace al live.') }}
                    </p>
                </div>
            </section>
        @endif

        {{-- Features preview --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                    <div class="text-3xl mb-2">📋</div>
                    <h3 class="font-semibold text-lg mb-1 text-wv-text">{{ __('Gestión integral') }}</h3>
                    <p class="text-sm text-wv-text-secondary">
                        {{ __('Categorías, estadios, equipos, atletas, anotadores y árbitros con fotos y logos.') }}
                    </p>
                </div>
                <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                    <div class="text-3xl mb-2">📡</div>
                    <h3 class="font-semibold text-lg mb-1 text-wv-text">{{ __('Juegos públicos') }}</h3>
                    <p class="text-sm text-wv-text-secondary">
                        {{ __('Comparte el avance en vivo de un partido con un enlace, sin login.') }}
                    </p>
                </div>
                <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                    <div class="text-3xl mb-2">💾</div>
                    <h3 class="font-semibold text-lg mb-1 text-wv-text">{{ __('Persistencia real') }}</h3>
                    <p class="text-sm text-wv-text-secondary">
                        {{ __('Base de datos MySQL. Tu información está segura y disponible desde cualquier dispositivo.') }}
                    </p>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-wv-border mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-wv-text-secondary">
                <p>
                    Gameday Score v2.0 (refactor en progreso) ·
                    <a href="/legacy/" class="hover:text-wv-text underline">{{ __('PWA legacy v1.1.12') }}</a> ·
                    <a href="https://github.com/AJ23ThemeMaster/gameday-score" target="_blank" class="hover:text-wv-text underline">GitHub</a>
                </p>
            </div>
        </footer>
    </div>

</body>
</html>
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
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 text-sm font-semibold text-wv-text-secondary hover:text-wv-text transition">
                                {{ __('Iniciar sesión') }}
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent rounded-card text-sm font-semibold transition">
                                    {{ __('Registrarse') }}
                                </a>
                            @endif
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
                        <a href="{{ route('register') }}"
                           class="px-6 py-3 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent rounded-card text-base font-semibold transition">
                            {{ __('Crear cuenta gratis') }}
                        </a>
                        <a href="{{ route('login') }}"
                           class="px-6 py-3 border border-wv-border hover:bg-wv-surface-hover text-wv-text rounded-card text-base font-semibold transition">
                            {{ __('Ya tengo cuenta') }}
                        </a>
                    @endauth
                    <a href="/legacy/" target="_blank"
                       class="px-6 py-3 border border-wv-border hover:bg-wv-surface-hover text-wv-text rounded-card text-base font-semibold transition">
                        {{ __('Abrir PWA clásica') }} ↗
                    </a>
                </div>
            </div>
        </main>

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
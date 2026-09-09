<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Gameday Score') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white min-h-screen">

    <div class="min-h-screen flex flex-col">
        {{-- Top bar --}}
        <header class="w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">⚾</span>
                    <span class="text-xl font-bold">Gameday Score</span>
                </div>
                <nav class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-md text-sm font-semibold transition">
                                {{ __('Panel de control') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 text-sm font-semibold hover:text-indigo-300 transition">
                                {{ __('Iniciar sesión') }}
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-md text-sm font-semibold transition">
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
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight mb-4">
                    Gameday Score
                </h1>
                <p class="text-xl sm:text-2xl text-slate-300 mb-2">
                    {{ __('Anotación profesional de béisbol y sófbol') }}
                </p>
                <p class="text-base text-slate-400 max-w-2xl mx-auto mb-10">
                    {{ __('Gestiona tus juegos, equipos, atletas y árbitros desde un solo lugar. Marca un juego como público y comparte el avance en vivo con quien quieras.') }}
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 rounded-md text-base font-semibold transition">
                            {{ __('Ir a mi panel') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 rounded-md text-base font-semibold transition">
                            {{ __('Crear cuenta gratis') }}
                        </a>
                        <a href="{{ route('login') }}"
                           class="px-6 py-3 border border-slate-500 hover:border-slate-300 rounded-md text-base font-semibold transition">
                            {{ __('Ya tengo cuenta') }}
                        </a>
                    @endauth
                    <a href="/legacy/" target="_blank"
                       class="px-6 py-3 border border-slate-500 hover:border-slate-300 rounded-md text-base font-semibold transition">
                        {{ __('Abrir PWA clásica') }} ↗
                    </a>
                </div>
            </div>
        </main>

        {{-- Features preview --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-slate-800/50 backdrop-blur rounded-lg p-6 border border-slate-700">
                    <div class="text-3xl mb-2">📋</div>
                    <h3 class="font-semibold text-lg mb-1">{{ __('Gestión integral') }}</h3>
                    <p class="text-sm text-slate-300">
                        {{ __('Categorías, estadios, equipos, atletas, anotadores y árbitros con fotos y logos.') }}
                    </p>
                </div>
                <div class="bg-slate-800/50 backdrop-blur rounded-lg p-6 border border-slate-700">
                    <div class="text-3xl mb-2">📡</div>
                    <h3 class="font-semibold text-lg mb-1">{{ __('Juegos públicos') }}</h3>
                    <p class="text-sm text-slate-300">
                        {{ __('Comparte el avance en vivo de un partido con un enlace, sin login.') }}
                    </p>
                </div>
                <div class="bg-slate-800/50 backdrop-blur rounded-lg p-6 border border-slate-700">
                    <div class="text-3xl mb-2">💾</div>
                    <h3 class="font-semibold text-lg mb-1">{{ __('Persistencia real') }}</h3>
                    <p class="text-sm text-slate-300">
                        {{ __('Base de datos MySQL. Tu información está segura y disponible desde cualquier dispositivo.') }}
                    </p>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-slate-800 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-slate-400">
                <p>
                    Gameday Score v2.0 (refactor en progreso) ·
                    <a href="/legacy/" class="hover:text-slate-200 underline">{{ __('PWA legacy v1.1.12') }}</a> ·
                    <a href="https://github.com/AJ23ThemeMaster/gameday-score" target="_blank" class="hover:text-slate-200 underline">GitHub</a>
                </p>
            </div>
        </footer>
    </div>

</body>
</html>

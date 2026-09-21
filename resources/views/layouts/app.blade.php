<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{--
          WATTVISION (feature/style/wattvision): el diseno se aplica globalmente.
          Solo las rutas excluidas en app/Support/WattVision.php (scoreboard,
          box-score, live, marcador publico) conservan la estetica original.
          Ver DESIGN.md para la especificacion completa.
        --}}
        @php
            use App\Support\WattVision;
            $wvExcluded = WattVision::isExcluded();
        @endphp

        <!-- Fonts base: Figtree (legado Breeze) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- WattVision: Inter (titulos/cuerpo) + JetBrains Mono (KPIs) + Material Symbols (iconos UI).
             Se cargan en todo el sistema EXCEPTO en las rutas excluidas.
             Material Symbols Outlined lo usan sidebar, dashboard y otros componentes.

             Bunny Fonts NO inyecta la regla CSS .material-symbols-outlined
             (a diferencia de Google Fonts), por eso esa regla se declara
             manualmente en resources/css/app.css (ver bloque "Material
             Symbols Outlined"). Sin esa regla, los elementos con la clase
             renderizan texto literal en lugar de los iconos. --}}
        @unless ($wvExcluded)
            <link href="https://fonts.bunny.net/css?family=inter:wght@400;500;600;700&family=jetbrains-mono:wght@400;500;700&display=swap" rel="stylesheet">
            <link href="https://fonts.bunny.net/css?family=material-symbols-outlined&display=swap" rel="stylesheet">
        @endunless

        @include('partials._pwa')

        {{-- Alpine stores y data components: cargados ANTES del @vite para que
             el listener de 'alpine:init' se registre antes de que Alpine.start()
             se ejecute (que es lo que dispara el evento). Mientras el build de
             Vite no se regenere (Node 22.11 vs Vite 8), esto es un bypass. --}}
        <script src="{{ asset('js/alpine-stores.js') }}" defer></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased @unless ($wvExcluded) font-inter text-wv-text @endunless">
        {{--
          WattVision: bg dark (#121212) + texto blanco por default.
          Las vistas excluidas (scoreboard/box-score/live/public) siguen con
          bg-gray-100 light + tipografia Figtree.
        --}}
        <div class="@unless ($wvExcluded) bg-wv-bg text-wv-text @else bg-gray-100 @endunless min-h-screen">

            {{--
              Nav global oculta en scoreboard/box-score/live (DISI-46) y tambien
              en el marcador publico para mantener consistencia: esas vistas
              tienen su propio header/breadcrumb.
            --}}
            @unless ($wvExcluded)
                <div x-data="{ sidebarOpen: false }" class="min-h-screen md:pl-64">
                    {{-- Sidebar persistente en desktop, drawer en mobile --}}
                    @include('layouts.navigation')

                    {{-- Top bar mobile (solo en mobile, con hamburguesa + page title) --}}
                    <div class="md:hidden sticky top-0 z-30 bg-wv-bg border-b border-wv-border">
                        <div class="h-14 px-3 flex items-center justify-between">
                            <button type="button"
                                    @click="sidebarOpen = true"
                                    class="inline-flex items-center justify-center w-10 h-10 rounded-card text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface focus:outline-none focus:ring-2 focus:ring-wv-accent">
                                <span class="material-symbols-outlined text-[24px]">menu</span>
                            </button>
                            <span class="text-sm font-semibold text-wv-text">@yield('header_mobile', config('app.name'))</span>
                            <span class="w-10"></span>
                        </div>
                    </div>

                    <!-- Page Heading (slot header opcional) -->
                    @isset($header)
                        <header class="bg-wv-bg border-b border-wv-border">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <!-- Page Content -->
                    <main class="min-h-[calc(100vh-3.5rem)]">
                        {{ $slot }}
                    </main>
                </div>
            @else
                {{-- Vista excluida: sin sidebar, sin top bar mobile --}}
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            @endunless

        </div>

        {{-- Toast stack global: recibe eventos 'toast' desde cualquier store --}}
        <x-toast-stack />
    </body>
</html>
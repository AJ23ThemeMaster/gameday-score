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

        {{-- WattVision: Inter (titulos/cuerpo) + JetBrains Mono (KPIs) en todo
             el sistema EXCEPTO en las rutas excluidas. --}}
        @unless ($wvExcluded)
            <link href="https://fonts.bunny.net/css?family=inter:wght@400;500;600;700&family=jetbrains-mono:wght@400;500;700&display=swap" rel="stylesheet">
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
        <div class="min-h-screen @unless ($wvExcluded) bg-wv-bg text-wv-text @else bg-gray-100 @endunless">
            {{--
              Nav global oculta en scoreboard/box-score/live (DISI-46) y ahora
              tambien en el marcador publico para mantener consistencia: esas
              vistas tienen su propio header/breadcrumb.
            --}}
            @unless ($wvExcluded)
                @include('layouts.navigation')
            @endunless

            <!-- Page Heading -->
            @isset($header)
                @unless ($wvExcluded)
                    <header class="bg-wv-bg border-b border-wv-border">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endunless
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        {{-- Toast stack global: recibe eventos 'toast' desde cualquier store --}}
        <x-toast-stack />
    </body>
</html>
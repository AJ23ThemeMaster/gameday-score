<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name'))</title>

        {{-- WattVision: tipografia Inter para todos los errores --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        {{-- Tailwind compilado de la app (incluye paleta WattVision). Para errores
             del framework que se renderizan sin Vite, cargamos el css via el
             helper Vite::asset() que SI lee el manifest y devuelve la URL
             versionada (ej: app-4LULqBC8.css). Usar {{ asset('build/assets/
             app.css') }} devuelve 404 porque el archivo siempre lleva hash. --}}
        <link rel="stylesheet" href="{{ Vite::asset('resources/css/app.css') }}">

        <style>
            /* Tokens WattVision inline (en caso de que el CSS no haya compilado o el
               navegador no pueda acceder a /build/ por cache). */
            :root {
                --wv-bg: #121212;
                --wv-surface: #1E1E1E;
                --wv-surface-hover: #252525;
                --wv-border: #2C2C2E;
                --wv-text: #FFFFFF;
                --wv-text-secondary: #98989D;
                --wv-accent: #00E5FF;
                --wv-alert: #FF453A;
            }
            html, body {
                background-color: var(--wv-bg);
                color: var(--wv-text);
                font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                margin: 0;
                height: 100vh;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex items-center justify-center bg-wv-bg text-wv-text px-6">
            <div class="text-center max-w-2xl">
                @yield('content')
            </div>
        </div>
    </body>
</html>
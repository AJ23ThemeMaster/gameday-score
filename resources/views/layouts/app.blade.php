<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- Alpine stores y data components: cargados ANTES del @vite para que
             el listener de 'alpine:init' se registre antes de que Alpine.start()
             se ejecute (que es lo que dispara el evento). Mientras el build de
             Vite no se regenere (Node 22.11 vs Vite 8), esto es un bypass. --}}
        <script src="{{ asset('js/alpine-stores.js') }}" defer></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            {{-- DISI-46: ocultar nav y header global en el scoreboard. El scoreboard --}}
            {{-- ya tiene su propio header (con breadcrumb y botones de accion en --}}
            {{-- DISI-39/41) y el nav global distrae del anotador en foco. --}}
            @if (! request()->routeIs('games.scoreboard'))
                @include('layouts.navigation')
            @endif

            <!-- Page Heading -->
            @isset($header)
                @if (! request()->routeIs('games.scoreboard'))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif
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

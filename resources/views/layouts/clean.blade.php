<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:wght@400;500;600;700;800&family=jetbrains-mono:wght@400;500;700&display=swap" rel="stylesheet">

        @include('partials._pwa')

        {{-- Alpine stores y data components: cargados ANTES del @vite para que
             el listener de 'alpine:init' se registre antes de Alpine.start(). --}}
        <script src="{{ asset('js/alpine-stores.js') }}" defer></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('head')
    </head>
    <body class="font-sans antialiased font-inter bg-wv-bg text-wv-text min-h-screen">
        @yield('content')

        {{-- Toast stack global: recibe eventos 'toast' desde cualquier store --}}
        <x-toast-stack />
    </body>
</html>
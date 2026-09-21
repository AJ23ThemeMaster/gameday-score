@extends('errors::layout')

@section('title', __('Página no encontrada'))

@section('content')
    {{-- WattVision: pagina de error generica. El `code` se inyecta desde
         secciones de cada archivo (404.blade.php, 500.blade.php, etc.). --}}
    <div class="mb-6">
        <p class="font-mono text-7xl sm:text-8xl font-bold text-wv-accent tracking-tight">@yield('code')</p>
    </div>
    <h1 class="text-2xl sm:text-3xl font-bold text-wv-text mb-3">@yield('title')</h1>
    <p class="text-base sm:text-lg text-wv-text-secondary mb-8">@yield('message')</p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ url('/') }}"
           class="inline-flex items-center justify-center px-5 py-2.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
            {{ __('Ir al inicio') }}
        </a>
        <a href="{{ url()->previous() }}"
           class="inline-flex items-center justify-center px-5 py-2.5 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-semibold rounded-card transition">
            {{ __('Volver') }}
        </a>
    </div>
    <p class="mt-10 text-xs text-wv-text-secondary">
        {{ __('Gameday Score') }} · v2.0
    </p>
@endsection
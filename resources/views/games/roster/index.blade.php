<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                {{ __('Roster') }}: {{ $game->homeTeam->short_name ?? $game->homeTeam->name }} <span class="text-wv-text-secondary mx-1">vs</span> {{ $game->awayTeam->short_name ?? $game->awayTeam->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('games.show', $game) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Detalle') }}</a>
                <a href="{{ route('games.scoreboard', $game) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Scoreboard en vivo') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{}" x-init="$store.roaster.gameId = {{ $game->id }}">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Flash messages (para navegacion sin JS) --}}
            @include('partials._flash')

            {{-- Roster (2 columnas). Este contenedor se re-renderiza por AJAX --}}
            @include('games.roster._partial')

        </div>
    </div>

    {{-- Modal: Agregar atleta --}}
    @include('games.roster._add_modal')

    {{-- Modal: Sustituir --}}
    @include('games.roster._substitute_modal')

</x-app-layout>
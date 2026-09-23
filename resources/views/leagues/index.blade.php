<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                    {{ __('Ligas') }}
                </h2>
                <p class="text-sm text-wv-text-secondary mt-1">
                    {{ __('Gestiona las ligas del sistema y los torneos asociados a cada una.') }}
                </p>
            </div>
            <a href="{{ route('leagues.create') }}"
               class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                + {{ __('Nueva liga') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($leagues->isEmpty())
                    <div class="p-10 text-center">
                        <p class="mb-4 text-wv-text-secondary">{{ __('AÃºn no hay ligas registradas.') }}</p>
                        <a href="{{ route('leagues.create') }}"
                           class="text-wv-accent hover:text-wv-accent-hover underline">
                            {{ __('Crear la primera liga') }}
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                        @foreach ($leagues as $league)
                            <div class="bg-wv-bg border border-wv-border rounded-card p-4 hover:border-wv-accent/50 hover:bg-wv-surface-hover transition">
                                <div class="flex items-start gap-3">
                                    @if ($league->logo_url)
                                        <img src="{{ $league->logo_url }}" alt="{{ $league->name }}"
                                             class="h-14 w-14 object-contain flex-shrink-0 bg-white rounded p-1">
                                    @else
                                        <div class="h-14 w-14 bg-wv-surface-hover rounded flex items-center justify-center text-wv-text-secondary text-xs flex-shrink-0">
                                            LOGO
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('leagues.show', $league) }}"
                                           class="text-base font-bold text-wv-text hover:text-wv-accent block truncate">
                                            {{ $league->name }}
                                        </a>
                                        @if ($league->short_name)
                                            <p class="text-xs text-wv-text-secondary">{{ $league->short_name }}@if ($league->country) · {{ $league->country }}@endif</p>
                                        @endif
                                        <div class="mt-2 flex gap-2 text-xs flex-wrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-wv-accent-soft text-wv-accent">
                                                {{ $league->tournaments_count }} {{ __('torneos') }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-wv-accent-soft text-wv-accent">
                                                {{ $league->games_count }} {{ __('juegos') }}
                                            </span>
                                            @if ($league->active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-wv-success/15 text-wv-success border border-wv-success/40 font-semibold">
                                                    {{ __('Activa') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-wv-surface-hover text-wv-text-secondary border border-wv-border font-semibold">
                                                    {{ __('Inactiva') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex justify-end gap-2 text-sm">
                                    <a href="{{ route('leagues.edit', $league) }}"
                                       class="text-wv-accent hover:text-wv-accent-hover font-medium">
                                        {{ __('Editar') }}
                                    </a>
                                    <form action="{{ route('leagues.destroy', $league) }}" method="POST" class="inline"
                                          data-confirm="'¿Eliminar la liga «{{ $league->name }}»? Si tiene torneos o juegos asociados, no se podrá eliminar.'" data-confirm-danger="true" data-loader>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-wv-alert hover:text-wv-alert-hover font-medium">
                                            {{ __('Eliminar') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-6 py-3 border-t border-wv-border">
                        {{ $leagues->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $game->homeTeam->name }}
                <span class="text-gray-400 mx-1">vs</span>
                {{ $game->awayTeam->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('games.index') }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Listado') }}</a>
                <a href="{{ route('games.roster.index', $game) }}" class="inline-flex items-center px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-md">{{ __('Roster') }}</a>
                @if (in_array($game->status, ['scheduled', 'in_progress', 'paused']))
                    <a href="{{ route('games.scoreboard', $game) }}" class="inline-flex items-center px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-md">{{ __('Scoreboard en vivo') }}</a>
                @endif
                <a href="{{ route('games.edit', $game) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- Header con logos y score --}}
                <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-200">
                    <div class="flex-1 text-center">
                        @if ($game->homeTeam->logoUrl)
                            <img src="{{ $game->homeTeam->logoUrl }}" class="h-16 w-16 mx-auto object-contain">
                        @endif
                        <h3 class="font-bold text-lg mt-2">{{ $game->homeTeam->name }}</h3>
                        <p class="text-sm text-gray-500">{{ __('Local') }}</p>
                    </div>
                    <div class="text-center px-4">
                        <div class="text-4xl font-bold text-gray-900">
                            {{ $game->home_score }} - {{ $game->away_score }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ __('Inning') }} {{ $game->current_inning }} {{ $game->inning_half === 'top' ? '▲' : '▼' }}
                        </div>
                    </div>
                    <div class="flex-1 text-center">
                        @if ($game->awayTeam->logoUrl)
                            <img src="{{ $game->awayTeam->logoUrl }}" class="h-16 w-16 mx-auto object-contain">
                        @endif
                        <h3 class="font-bold text-lg mt-2">{{ $game->awayTeam->name }}</h3>
                        <p class="text-sm text-gray-500">{{ __('Visitante') }}</p>
                    </div>
                </div>

                {{-- Estado y público --}}
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    @php
                        $statusLabels = ['scheduled' => 'Programado', 'in_progress' => 'En vivo', 'paused' => 'Pausado', 'completed' => 'Finalizado', 'suspended' => 'Suspendido', 'cancelled' => 'Cancelado'];
                    @endphp
                    <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ __($statusLabels[$game->status] ?? $game->status) }}
                    </span>
                    @if ($game->is_public)
                        <span class="inline-flex items-center gap-1 px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                            {{ __('Público') }}
                        </span>
                        @if ($game->public_url)
                            <button type="button"
                                    x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText('{{ $game->public_url }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V7a2 2 0 012-2m-2 8h2m-2 0v2a2 2 0 002 2h2a2 2 0 002-2v-2"/></svg>
                                <span x-show="!copied">{{ __('Copiar enlace') }}</span>
                                <span x-show="copied" x-cloak>{{ __('¡Copiado!') }}</span>
                            </button>
                            <a href="{{ $game->public_url }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 underline">
                                {{ __('Ver enlace público') }} ↗
                            </a>
                        @endif
                    @endif
                </div>

                {{-- Datos --}}
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Fecha y hora') }}</dt><dd class="text-base font-medium text-gray-900">{{ $game->scheduled_at->format('d/m/Y H:i') }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Categoría') }}</dt><dd class="text-base font-medium text-gray-900">{{ $game->category->name ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Estadio') }}</dt><dd class="text-base font-medium text-gray-900">{{ $game->stadium->name ?? '—' }}</dd></div>
                    <div class="bg-gray-50 rounded-md p-3"><dt class="text-gray-500 text-xs uppercase">{{ __('Innings / Mercy') }}</dt><dd class="text-base font-medium text-gray-900">{{ $game->innings_count }} innings, -{{ $game->mercy_rule_difference }} en in. {{ $game->mercy_rule_inning }}</dd></div>
                </dl>

                {{-- Staff --}}
                @if ($game->scorekeepers->count() || $game->referees->count())
                    <div class="mt-6 pt-6 border-t border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if ($game->scorekeepers->count())
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Anotadores') }}</h4>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    @foreach ($game->scorekeepers as $sk)
                                        <li class="flex items-center gap-2">
                                            @if ($sk->photoUrl)<img src="{{ $sk->photoUrl }}" class="h-6 w-6 rounded-full object-cover">@endif
                                            <a href="{{ route('scorekeepers.show', $sk) }}" class="text-indigo-600 hover:text-indigo-800">{{ $sk->full_name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($game->referees->count())
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Árbitros') }}</h4>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    @foreach ($game->referees as $rf)
                                        <li class="flex items-center gap-2">
                                            @if ($rf->photoUrl)<img src="{{ $rf->photoUrl }}" class="h-6 w-6 rounded-full object-cover">@endif
                                            <a href="{{ route('referees.show', $rf) }}" class="text-indigo-600 hover:text-indigo-800">{{ $rf->full_name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($game->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Notas') }}</h4>
                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ $game->notes }}</p>
                    </div>
                @endif

                <div class="mt-6 pt-6 border-t border-gray-200 text-xs text-gray-500">
                    {{ __('Creado') }}: {{ $game->created_at->format('d/m/Y H:i') }} · {{ __('Actualizado') }}: {{ $game->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

            <form action="{{ route('games.destroy', $game) }}" method="POST" class="mt-4 text-right" onsubmit="return confirm('¿Eliminar este juego?');">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Eliminar juego') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>

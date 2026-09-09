<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Scoreboard en vivo') }}: {{ $game->homeTeam->short_name ?? $game->homeTeam->name }} <span class="text-gray-400">vs</span> {{ $game->awayTeam->short_name ?? $game->awayTeam->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('games.show', $game) }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Detalle') }}</a>
                @if ($game->is_public && $game->public_url)
                    <a href="{{ $game->public_url }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-md">
                        {{ __('Ver vista pública') }} ↗
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            {{-- SCOREBOARD PRINCIPAL --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                {{-- Equipos y score --}}
                <div class="flex items-center justify-between mb-6">
                    <div class="flex-1 text-center">
                        @if ($game->homeTeam->logoUrl)
                            <img src="{{ $game->homeTeam->logoUrl }}" class="h-16 w-16 mx-auto object-contain">
                        @endif
                        <h3 class="font-bold text-lg mt-2">{{ $game->homeTeam->name }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Local') }}</p>
                    </div>
                    <div class="text-center px-4">
                        <div class="text-5xl font-black text-gray-900">
                            <span id="home-score">{{ $game->home_score }}</span>
                            <span class="text-gray-300 mx-1">-</span>
                            <span id="away-score">{{ $game->away_score }}</span>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            <span>{{ __('Inning') }}</span>
                            <span id="current-inning" class="font-bold">{{ $game->current_inning }}</span>
                            <span id="inning-half" class="ml-1">{{ $game->inning_half === 'top' ? '▲' : '▼' }}</span>
                        </div>
                    </div>
                    <div class="flex-1 text-center">
                        @if ($game->awayTeam->logoUrl)
                            <img src="{{ $game->awayTeam->logoUrl }}" class="h-16 w-16 mx-auto object-contain">
                        @endif
                        <h3 class="font-bold text-lg mt-2">{{ $game->awayTeam->name }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Visitante') }}</p>
                    </div>
                </div>

                {{-- B-S-O + Bases --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 text-center">{{ __('Conteo actual') }}</h4>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="bg-gray-50 rounded-md p-3">
                                <div class="text-3xl font-bold" id="balls">{{ $game->balls }}</div>
                                <div class="text-xs text-gray-500 uppercase">B</div>
                            </div>
                            <div class="bg-gray-50 rounded-md p-3">
                                <div class="text-3xl font-bold" id="strikes">{{ $game->strikes }}</div>
                                <div class="text-xs text-gray-500 uppercase">S</div>
                            </div>
                            <div class="bg-gray-50 rounded-md p-3">
                                <div class="text-3xl font-bold" id="outs">{{ $game->outs }}</div>
                                <div class="text-xs text-gray-500 uppercase">O</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 text-center">{{ __('Corredores en bases') }}</h4>
                        <div class="flex items-center justify-center gap-4">
                            @php $bases = $game->bases ?? []; @endphp
                            <div class="text-center">
                                <div id="base-3" class="w-10 h-10 rounded-full border-2 {{ ! empty($bases['third']) ? 'bg-yellow-400 border-yellow-500' : 'bg-gray-100 border-gray-300' }}"></div>
                                <div class="text-xs mt-1">3B</div>
                            </div>
                            <div class="text-center">
                                <div id="base-2" class="w-10 h-10 rounded-full border-2 {{ ! empty($bases['second']) ? 'bg-yellow-400 border-yellow-500' : 'bg-gray-100 border-gray-300' }}"></div>
                                <div class="text-xs mt-1">2B</div>
                            </div>
                            <div class="text-center">
                                <div id="base-1" class="w-10 h-10 rounded-full border-2 {{ ! empty($bases['first']) ? 'bg-yellow-400 border-yellow-500' : 'bg-gray-100 border-gray-300' }}"></div>
                                <div class="text-xs mt-1">1B</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTROLES --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Carreras --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ __('Sumar carreras') }}</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <form method="POST" action="{{ route('games.runs.add', $game) }}">
                            @csrf
                            <input type="hidden" name="team" value="home">
                            <button type="submit" class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">
                                +1 {{ $game->homeTeam->short_name ?? 'Local' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('games.runs.add', $game) }}">
                            @csrf
                            <input type="hidden" name="team" value="away">
                            <button type="submit" class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">
                                +1 {{ $game->awayTeam->short_name ?? 'Visitante' }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- B-S-O y Bases --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ __('Bolas, strikes y outs') }}</h4>
                    <form method="POST" action="{{ route('games.state.update', $game) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs text-gray-500">Bolas</label>
                                <input type="number" name="balls" min="0" max="3" value="{{ $game->balls }}" class="w-full border-gray-300 rounded text-center">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Strikes</label>
                                <input type="number" name="strikes" min="0" max="2" value="{{ $game->strikes }}" class="w-full border-gray-300 rounded text-center">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Outs</label>
                                <input type="number" name="outs" min="0" max="2" value="{{ $game->outs }}" class="w-full border-gray-300 rounded text-center">
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <label class="flex items-center gap-1"><input type="checkbox" name="bases[first]" value="1" {{ ! empty($bases['first']) ? 'checked' : '' }}>1B</label>
                            <label class="flex items-center gap-1"><input type="checkbox" name="bases[second]" value="1" {{ ! empty($bases['second']) ? 'checked' : '' }}>2B</label>
                            <label class="flex items-center gap-1"><input type="checkbox" name="bases[third]" value="1" {{ ! empty($bases['third']) ? 'checked' : '' }}>3B</label>
                        </div>
                        <button type="submit" class="w-full px-3 py-2 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold rounded-md">
                            {{ __('Actualizar B-S-O y bases') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Inning y estado --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('games.end-inning', $game) }}" class="text-center">
                        @csrf
                        <button type="submit" class="w-full px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-md">
                            {{ __('Finalizar inning actual') }} →
                        </button>
                    </form>
                    <form method="POST" action="{{ route('games.state.update', $game) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-2 gap-2">
                            <select name="current_inning" class="border-gray-300 rounded text-sm">
                                @for ($i = 1; $i <= $game->innings_count; $i++)
                                    <option value="{{ $i }}" {{ $game->current_inning == $i ? 'selected' : '' }}>Inning {{ $i }}</option>
                                @endfor
                            </select>
                            <select name="inning_half" class="border-gray-300 rounded text-sm">
                                <option value="top" {{ $game->inning_half === 'top' ? 'selected' : '' }}>▲ {{ __('Alta') }}</option>
                                <option value="bottom" {{ $game->inning_half === 'bottom' ? 'selected' : '' }}>▼ {{ __('Baja') }}</option>
                            </select>
                        </div>
                        <select name="status" class="w-full border-gray-300 rounded text-sm">
                            <option value="">{{ __('— Sin cambio de estado —') }}</option>
                            @foreach (['scheduled' => 'Programado', 'in_progress' => 'En vivo', 'paused' => 'Pausado', 'completed' => 'Finalizado', 'suspended' => 'Suspendido', 'cancelled' => 'Cancelado'] as $key => $label)
                                <option value="{{ $key }}" {{ $game->status === $key ? 'selected' : '' }}>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full px-3 py-2 bg-gray-700 hover:bg-gray-800 text-white text-xs font-semibold rounded-md">
                            {{ __('Aplicar cambios de inning/estado') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Roster --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ([$game->homeTeam, $game->awayTeam] as $team)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
                            @if ($team->logoUrl)<img src="{{ $team->logoUrl }}" class="h-6 w-6 object-contain">@endif
                            {{ $team->name }}
                            <span class="text-xs text-gray-500 font-normal">({{ $team->athletes->count() }} {{ __('atletas') }})</span>
                        </h4>
                        @if ($team->athletes->isEmpty())
                            <p class="text-sm text-gray-500">{{ __('Sin atletas registrados.') }}</p>
                        @else
                            <div class="space-y-1 text-sm">
                                @foreach ($team->athletes as $a)
                                    <div class="flex items-center gap-2 py-1 px-2 bg-gray-50 rounded">
                                        <span class="text-xs font-mono text-gray-500 w-6 text-right">{{ $a->number ?? '-' }}</span>
                                        <span class="font-mono text-xs bg-gray-200 px-1.5 rounded">{{ $a->position ?? '—' }}</span>
                                        <span class="flex-1">{{ $a->full_name }}</span>
                                        <span class="text-xs text-gray-500">{{ $a->bats }}/{{ $a->throws }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>

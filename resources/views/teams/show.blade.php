<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-3">
                @if ($team->logoUrl)
                    <img src="{{ $team->logoUrl }}" alt="{{ $team->name }}" class="h-10 w-10 object-contain">
                @endif
                {{ __('Equipo') }}: {{ $team->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('teams.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                    {{ __('Listado') }}
                </a>
                <a href="{{ route('teams.edit', $team) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md">
                    {{ __('Editar') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex flex-wrap items-center gap-3 mb-4">
                    @if ($team->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ __('Activo') }}
                        </span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            {{ __('Inactivo') }}
                        </span>
                    @endif
                    @if ($team->short_name)
                        <code class="text-sm text-gray-500">{{ $team->short_name }}</code>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Ciudad') }}</dt>
                        <dd class="text-base font-medium text-gray-900">{{ $team->city ?? '—' }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Atletas') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $team->athletes_count }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Como local') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $team->home_games_count }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Como visitante') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $team->away_games_count }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-md p-4 border" :style="'background-color: ' + '{{ $team->home_color ?? '#1a3d6e' }}' + '20'">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-8 h-8 rounded border border-gray-300"
                                  style="background-color: {{ $team->home_color ?? '#1a3d6e' }}"></span>
                            <span class="text-sm text-gray-700">{{ __('Color local') }}: <code>{{ $team->home_color ?? '—' }}</code></span>
                        </div>
                    </div>
                    <div class="rounded-md p-4 border">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-8 h-8 rounded border border-gray-300"
                                  style="background-color: {{ $team->away_color ?? '#ffffff' }}"></span>
                            <span class="text-sm text-gray-700">{{ __('Color visitante') }}: <code>{{ $team->away_color ?? '—' }}</code></span>
                        </div>
                    </div>
                </div>

                @if ($team->athletes->count())
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">
                            {{ __('Roster') }} ({{ $team->athletes->count() }})
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach ($team->athletes as $athlete)
                                <div class="text-sm text-gray-700 bg-gray-50 rounded px-3 py-2 flex items-center gap-2">
                                    <span class="text-xs font-mono text-gray-500 w-6 text-right">{{ $athlete->number ?? '-' }}</span>
                                    <span class="flex-1">{{ $athlete->full_name }}</span>
                                    @if ($athlete->position)
                                        <span class="text-xs bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded">{{ $athlete->position }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 pt-6 border-t border-gray-200 text-xs text-gray-500">
                    {{ __('Creado') }}: {{ $team->created_at->format('d/m/Y H:i') }} ·
                    {{ __('Actualizado') }}: {{ $team->updated_at->format('d/m/Y H:i') }}
                </div>

            </div>

            <form action="{{ route('teams.destroy', $team) }}" method="POST" class="mt-4 text-right"
                  onsubmit="return confirm('¿Eliminar el equipo «{{ $team->name }}»?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                    {{ __('Eliminar equipo') }}
                </button>
            </form>

        </div>
    </div>
</x-app-layout>

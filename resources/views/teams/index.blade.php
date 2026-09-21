<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Equipos') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Lista de equipos registrados en el sistema.') }}</p>
            </div>
            <a href="{{ route('teams.create') }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                + {{ __('Nuevo equipo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($teams->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        <p class="mb-4">{{ __('Aún no hay equipos registrados.') }}</p>
                        <a href="{{ route('teams.create') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Crear el primer equipo') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Logo') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre / Ciudad') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Liga') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Atletas') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Juegos') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($teams as $team)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($team->logoUrl)
                                                <img src="{{ $team->logoUrl }}" alt="{{ $team->name }}" class="h-12 w-12 object-contain bg-white rounded border border-wv-border p-1">
                                            @else
                                                <div class="h-12 w-12 rounded border border-wv-border bg-wv-surface-hover flex items-center justify-center text-wv-text-secondary text-xs">
                                                    {{ __('Sin logo') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('teams.show', $team) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $team->name }}</a>
                                            @if ($team->city)<p class="text-xs text-wv-text-secondary">{{ $team->city }}</p>@endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">
                                            @if ($team->league)
                                                <a href="{{ route('leagues.show', $team->league) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $team->league->short_name ?? $team->league->name }}</a>
                                            @else
                                                <span class="italic">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $team->athletes_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $team->home_games_count + $team->away_games_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if ($team->active)
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('teams.edit', $team) }}" class="text-wv-accent hover:text-wv-accent-hover mr-3">{{ __('Editar') }}</a>
                                            <form action="{{ route('teams.destroy', $team) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar el equipo «{{ $team->name }}»?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $teams->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
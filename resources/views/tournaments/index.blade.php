<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                    {{ __('Torneos') }}
                </h2>
                <p class="text-sm text-wv-text-secondary mt-1">
                    {{ __('Lista de torneos registrados y sus juegos asociados.') }}
                </p>
            </div>
            <a href="{{ route('tournaments.create') }}"
               class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                + {{ __('Nuevo torneo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($tournaments->isEmpty())
                    <div class="p-10 text-center">
                        <p class="mb-4 text-wv-text-secondary">{{ __('Aún no hay torneos registrados.') }}</p>
                        <a href="{{ route('tournaments.create') }}"
                           class="text-wv-accent hover:text-wv-accent-hover underline">
                            {{ __('Crear el primer torneo') }}
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Torneo') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Liga') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Categoría') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Temporada') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Juegos') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($tournaments as $t)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                @if ($t->logo_url)
                                                    <img src="{{ $t->logo_url }}" alt="{{ $t->name }}" class="h-8 w-8 object-contain bg-white rounded p-0.5">
                                                @endif
                                                <a href="{{ route('tournaments.show', $t) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">
                                                    {{ $t->name }}
                                                </a>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('leagues.show', $t->league) }}" class="text-wv-accent hover:text-wv-accent-hover">
                                                {{ $t->league->short_name ?? $t->league->name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">{{ $t->category ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">{{ $t->season ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $t->games_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('tournaments.edit', $t) }}" class="text-wv-accent hover:text-wv-accent-hover mr-3">{{ __('Editar') }}</a>
                                            <form action="{{ route('tournaments.destroy', $t) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('¿Eliminar el torneo «{{ $t->name }}»?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-3 border-t border-wv-border">
                        {{ $tournaments->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
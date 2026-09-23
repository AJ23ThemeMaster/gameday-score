<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight flex items-center gap-3">
                @if ($scorekeeper->photoUrl)
                    <img src="{{ $scorekeeper->photoUrl }}" class="h-10 w-10 rounded-full object-cover bg-wv-surface border border-wv-border p-0.5">
                @endif
                {{ __('Anotador') }}: {{ $scorekeeper->full_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('scorekeepers.index') }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Listado') }}</a>
                <a href="{{ route('scorekeepers.edit', $scorekeeper) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    @if ($scorekeeper->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Cédula / Documento') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $scorekeeper->document_id ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Teléfono') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $scorekeeper->phone ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3 sm:col-span-2">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Correo electrÃ³nico') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $scorekeeper->email ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($scorekeeper->notes)
                    <div class="mt-6 pt-6 border-t border-wv-border">
                        <h4 class="text-sm font-semibold text-wv-text mb-2">{{ __('Notas') }}</h4>
                        <p class="text-sm text-wv-text-secondary whitespace-pre-line">{{ $scorekeeper->notes }}</p>
                    </div>
                @endif

                <div class="mt-6 pt-6 border-t border-wv-border text-xs text-wv-text-secondary">
                    {{ __('Creado') }}: {{ $scorekeeper->created_at->format('d/m/Y H:i') }} ·
                    {{ __('Actualizado') }}: {{ $scorekeeper->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- DISI-67: juegos recientes donde participo --}}
            @if (isset($recentGames) && $recentGames->count())
                <div class="mt-4 bg-wv-surface border border-wv-border rounded-card p-6">
                    <h3 class="text-sm font-bold text-wv-text mb-3">{{ __('Ãšltimos juegos anotados') }} ({{ $recentGames->count() }})</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border text-sm">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase">{{ __('Fecha') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-wv-text-secondary uppercase">{{ __('Enfrentamiento') }}</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-wv-text-secondary uppercase">{{ __('Estado') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($recentGames as $g)
                                    <tr class="hover:bg-wv-surface-hover">
                                        <td class="px-3 py-2 text-wv-text-secondary">{{ $g->scheduled_at->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('games.show', $g) }}" class="text-wv-accent hover:text-wv-accent-hover">
                                                {{ $g->homeTeam->short_name ?? $g->homeTeam->name }} <span class="text-wv-text-secondary mx-1">vs</span> {{ $g->awayTeam->short_name ?? $g->awayTeam->name }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-center text-xs text-wv-text-secondary">{{ $g->status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <form action="{{ route('scorekeepers.destroy', $scorekeeper) }}" method="POST" class="mt-4 text-right" data-confirm="'¿Eliminar al anotador «{{ $scorekeeper->full_name }}»?'" data-confirm-danger="true" data-loader>
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar anotador') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
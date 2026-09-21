<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Árbitros') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Lista de árbitros disponibles para asignar a juegos.') }}</p>
            </div>
            <a href="{{ route('referees.create') }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                + {{ __('Nuevo árbitro') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            @php $statLabels = ['total' => __('Total'), 'active' => __('Activos'), 'inactive' => __('Inactivos'), 'games' => __('Juegos arbitrados')]; @endphp
            @if (! empty($stats))
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    @foreach ($statLabels as $key => $label)
                        <div class="bg-wv-surface border border-wv-border rounded-card p-4 text-center">
                            <div class="font-mono text-kpi text-wv-text">{{ $stats[$key] ?? 0 }}</div>
                            <div class="text-xs text-wv-text-secondary uppercase tracking-wider mt-1">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($referees->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        <p class="mb-4">{{ __('Aún no hay árbitros registrados.') }}</p>
                        <a href="{{ route('referees.create') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Registrar el primer árbitro') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Foto') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Documento') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Certificación') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Juegos') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($referees as $rf)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($rf->photoUrl)
                                                <img src="{{ $rf->photoUrl }}" class="h-10 w-10 rounded-full object-cover bg-wv-surface">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-wv-surface-hover flex items-center justify-center text-wv-text-secondary text-xs font-semibold">
                                                    {{ mb_substr($rf->first_name, 0, 1) }}{{ mb_substr($rf->last_name, 0, 1) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('referees.show', $rf) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $rf->full_name }}</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">{{ $rf->document_id ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">{{ $rf->certification ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $rf->games_count ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if ($rf->active)
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('referees.edit', $rf) }}" class="text-wv-accent hover:text-wv-accent-hover mr-3">{{ __('Editar') }}</a>
                                            <form action="{{ route('referees.destroy', $rf) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar al árbitro «{{ $rf->full_name }}»?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $referees->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Categorías') }}</h2>
                <p class="text-sm text-wv-text-secondary mt-1">{{ __('Reglas por categoría (innings, nocaut, pitch limit).') }}</p>
            </div>
            <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                + {{ __('Nueva categoría') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card overflow-hidden">
                @if ($categories->isEmpty())
                    <div class="p-10 text-center text-wv-text-secondary">
                        <p class="mb-4">{{ __('AÃºn no hay categorías registradas.') }}</p>
                        <a href="{{ route('categories.create') }}" class="text-wv-accent hover:text-wv-accent-hover underline">{{ __('Crear la primera categoría') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Slug') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Innings') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Nocaut') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Pitch limit') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Juegos') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-wv-text-secondary uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($categories as $cat)
                                    <tr class="hover:bg-wv-surface-hover transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('categories.show', $cat) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $cat->name }}</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-wv-text-secondary">
                                            <code>{{ $cat->slug }}</code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $cat->innings_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">-{{ $cat->mercy_rule_difference }} / in. {{ $cat->mercy_rule_inning }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $cat->pitch_limit ?? 'â€”' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-wv-text">{{ $cat->games_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if ($cat->active)
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activa') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactiva') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('categories.edit', $cat) }}" class="text-wv-accent hover:text-wv-accent-hover mr-3">{{ __('Editar') }}</a>
                                            <form action="{{ route('categories.destroy', $cat) }}" method="POST" class="inline" data-confirm="'¿Eliminar la categoría «{{ $cat->name }}»?'" data-confirm-danger="true" data-loader>
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-wv-border">{{ $categories->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
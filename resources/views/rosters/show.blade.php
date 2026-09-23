<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                {{ __('Roster') }}: {{ $roster->full_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('teams.rosters.index', $team) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">← {{ __('Rosters') }}</a>
                <a href="{{ route('teams.rosters.pdf', [$team, $roster]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-xs font-semibold rounded-card"
                   title="{{ __('Descargar reporte en PDF') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
                    </svg>
                    {{ __('Descargar PDF') }}
                </a>
                <a href="{{ route('teams.rosters.edit', [$team, $roster]) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    @if ($roster->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                    @endif
                    @if ($roster->category)
                        <a href="{{ route('categories.show', $roster->category) }}" class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-accent/15 text-wv-accent border border-wv-accent/40">
                            {{ $roster->category->name }}
                        </a>
                    @endif
                    @if ($roster->name)
                        <code class="text-sm text-wv-text-secondary">{{ $roster->name }}</code>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Manager') }}</dt>
                        <dd class="text-base font-medium text-wv-text">
                            @if ($roster->managerCoach)
                                <a href="{{ route('teams.coaches.show', [$team, $roster->managerCoach]) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $roster->managerCoach->full_name }}</a>
                                @if ($roster->managerCoach->role)
                                    <p class="text-xs text-wv-text-secondary">{{ $roster->managerCoach->role }}</p>
                                @endif
                            @else
                                <span class="italic text-wv-text-secondary">{{ __('Sin asignar') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Delegado') }}</dt>
                        <dd class="text-base font-medium text-wv-text">
                            @if ($roster->delegateUser)
                                {{ $roster->delegateUser->name }}
                                <p class="text-xs text-wv-text-secondary">{{ $roster->delegateUser->email }}</p>
                            @else
                                <span class="italic text-wv-text-secondary">{{ __('Sin asignar') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Equipo') }}</dt>
                        <dd class="text-base font-medium">
                            <a href="{{ route('teams.show', $team) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $team->name }}</a>
                        </dd>
                    </div>
                </dl>

                {{-- Entrenadores adicionales --}}
                <div class="mt-6 pt-6 border-t border-wv-border">
                    <h4 class="text-sm font-semibold text-wv-text mb-3">
                        {{ __('Entrenadores del roster') }}
                        <span class="text-wv-text-secondary font-normal">({{ $roster->coaches->count() }})</span>
                    </h4>
                    @if ($roster->coaches->isEmpty())
                        <p class="text-sm text-wv-text-secondary italic">{{ __('Aún no hay entrenadores adicionales registrados en este roster.') }}</p>
                    @else
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($roster->coaches as $c)
                                <li class="bg-wv-bg border border-wv-border rounded-card p-3 flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('teams.coaches.show', [$team, $c]) }}" class="text-sm font-medium text-wv-text hover:text-wv-accent truncate block">{{ $c->full_name }}</a>
                                        @if ($c->role)
                                            <p class="text-xs text-wv-text-secondary">{{ $c->role }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Atletas derivados (team_id + category_id) --}}
                <div class="mt-6 pt-6 border-t border-wv-border">
                    <h4 class="text-sm font-semibold text-wv-text mb-3">
                        {{ __('Atletas del roster') }}
                        <span class="text-wv-text-secondary font-normal">({{ $categoryAthletes->count() }})</span>
                    </h4>
                    @if ($categoryAthletes->isEmpty())
                        <p class="text-sm text-wv-text-secondary italic">{{ __('No hay atletas registrados en este equipo con esta categoría.') }}</p>
                    @else
                        <div class="overflow-x-auto border border-wv-border rounded-card">
                            <table class="min-w-full divide-y divide-wv-border text-sm">
                                <thead class="bg-wv-surface-hover">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">N°</th>
                                        <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Nombre') }}</th>
                                        <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Pos.') }}</th>
                                        <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('B/L') }}</th>
                                        <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Estado') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-wv-border">
                                    @foreach ($categoryAthletes as $a)
                                        <tr class="hover:bg-wv-surface-hover">
                                            <td class="px-3 py-2 whitespace-nowrap font-mono text-wv-text">{{ $a->number ?? '—' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <a href="{{ route('athletes.show', $a) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $a->full_name }}</a>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                @if ($a->position)
                                                    <span class="inline-block px-1.5 py-0.5 text-xs bg-wv-surface-hover text-wv-text rounded font-mono">{{ $a->position }}</span>
                                                @else —
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center font-mono text-xs text-wv-text-secondary">{{ $a->bats }}/{{ $a->throws }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @if ($a->active)
                                                    <span class="inline-flex px-2 text-[10px] leading-4 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                                                @else
                                                    <span class="inline-flex px-2 text-[10px] leading-4 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-wv-text-secondary">
                            {{ __('Los atletas se asignan automáticamente según su equipo y categoría. Para añadir atletas a esta categoría, edita su perfil de atleta.') }}
                        </p>
                    @endif
                </div>

                <div class="mt-6 pt-6 border-t border-wv-border text-xs text-wv-text-secondary">
                    {{ __('Creado') }}: {{ $roster->created_at->format('d/m/Y H:i') }} ·
                    {{ __('Actualizado') }}: {{ $roster->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

            <form action="{{ route('teams.rosters.destroy', [$team, $roster]) }}" method="POST" class="mt-4 text-right"
                  data-confirm="'¿Eliminar el roster «{{ $roster->full_name }}»?'" data-confirm-danger="true" data-loader>
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar roster') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>

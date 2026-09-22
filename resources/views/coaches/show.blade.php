<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Entrenador') }}: {{ $coach->full_name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('teams.coaches.index', $team) }}" class="text-sm text-wv-text-secondary hover:text-wv-text">← {{ __('Entrenadores') }}</a>
                <a href="{{ route('teams.coaches.edit', [$team, $coach]) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    @if ($coach->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                    @endif
                    @if ($coach->role)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-accent/15 text-wv-accent border border-wv-accent/40">{{ $coach->role }}</span>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Nombre completo') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $coach->full_name }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Documento') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $coach->document_id ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Fecha de nacimiento') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ optional($coach->birth_date)->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Teléfono') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $coach->phone ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Correo') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $coach->email ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Equipo') }}</dt>
                        <dd class="text-base font-medium">
                            <a href="{{ route('teams.show', $team) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $team->name }}</a>
                        </dd>
                    </div>
                </dl>

                @if ($coach->notes)
                    <div class="mt-4 bg-wv-bg border border-wv-border rounded-card p-4">
                        <dt class="text-wv-text-secondary text-xs uppercase mb-1">{{ __('Notas') }}</dt>
                        <dd class="text-sm text-wv-text whitespace-pre-line">{{ $coach->notes }}</dd>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

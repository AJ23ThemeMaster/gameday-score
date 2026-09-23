<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight flex items-center gap-3">
                @if ($referee->photoUrl)
                    <img src="{{ $referee->photoUrl }}" class="h-10 w-10 rounded-full object-cover bg-wv-surface border border-wv-border p-0.5">
                @endif
                {{ __('Ãrbitro') }}: {{ $referee->full_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('referees.index') }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Listado') }}</a>
                <a href="{{ route('referees.edit', $referee) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')
            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    @if ($referee->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Cédula / Documento') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $referee->document_id ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Teléfono') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $referee->phone ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('CertificaciÃ³n') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $referee->certification ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('AÃ±os de experiencia') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $referee->experience_years ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($referee->notes)
                    <div class="mt-6 pt-6 border-t border-wv-border">
                        <h4 class="text-sm font-semibold text-wv-text mb-2">{{ __('Notas') }}</h4>
                        <p class="text-sm text-wv-text-secondary whitespace-pre-line">{{ $referee->notes }}</p>
                    </div>
                @endif

                <div class="mt-6 pt-6 border-t border-wv-border text-xs text-wv-text-secondary">
                    {{ __('Creado') }}: {{ $referee->created_at->format('d/m/Y H:i') }} ·
                    {{ __('Actualizado') }}: {{ $referee->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

            <form action="{{ route('referees.destroy', $referee) }}" method="POST" class="mt-4 text-right" data-confirm="'¿Eliminar al árbitro «{{ $referee->full_name }}»?'" data-confirm-danger="true" data-loader>
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar árbitro') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
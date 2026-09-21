<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                    {{ __('Codigos de recuperacion de 2FA') }}
                </h2>
                <p class="text-sm text-wv-text-secondary mt-1">
                    {{ __('Cada codigo es de un solo uso. Guardalos en un lugar seguro.') }}
                </p>
            </div>
            <a href="{{ route('profile.edit') }}"
               class="text-sm text-wv-text-secondary hover:text-wv-text underline">
                {{ __('← Volver a Mi perfil') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- WattVision: alerta amber (advertencia) usando surface + border + texto --}}
            <div class="bg-wv-surface border border-wv-accent/40 rounded-card p-4 text-sm">
                <p class="font-semibold text-wv-accent mb-1">{{ __('Importante: guarda estos codigos en un lugar seguro.') }}</p>
                <p class="text-wv-text-secondary">
                    {{ __('Cada codigo solo puede usarse una vez. Si pierdes acceso a tu aplicacion autenticadora, puedes usar uno de estos codigos para iniciar sesion.') }}
                </p>
            </div>

            @if (session('status') === 'recovery-codes-regenerated')
                <div class="rounded-card bg-wv-surface border border-wv-success/40 p-4 text-sm text-wv-success">
                    {{ __('Codigos de recuperacion regenerados. Los anteriores ya no son validos.') }}
                </div>
            @endif

            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <h3 class="text-lg font-semibold text-wv-text mb-4">
                    {{ __('Tus codigos') }}
                </h3>

                {{-- WattVision: cada codigo en su propio card surface-deep para resaltar --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono text-base">
                    @foreach ($recoveryCodes as $code)
                        <div class="bg-wv-bg border border-wv-border rounded-card px-3 py-2 text-center text-wv-text select-all">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-wrap gap-3 justify-end">
                    <a href="{{ route('profile.edit') }}"
                       class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                        {{ __('Volver a Mi perfil') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
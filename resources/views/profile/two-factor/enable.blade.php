<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                    {{ __('Activar autenticacion en 2 pasos') }}
                </h2>
                <p class="text-sm text-wv-text-secondary mt-1">
                    {{ __('Sigue los 2 pasos para activar la verificacion TOTP en tu cuenta.') }}
                </p>
            </div>
            <a href="{{ route('profile.edit') }}"
               class="text-sm text-wv-text-secondary hover:text-wv-text underline">
                {{ __('← Volver a Mi perfil') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <h3 class="text-lg font-semibold text-wv-text mb-2">
                    {{ __('Paso 1: Escanea el codigo QR') }}
                </h3>
                <p class="text-sm text-wv-text-secondary mb-4">
                    {{ __('Abre tu aplicacion autenticadora (Google Authenticator, Authy, 1Password, Bitwarden, etc.) y escanea este codigo QR.') }}
                </p>

                @if (str_starts_with($qrCodeInline, 'data:image/'))
                    {{-- El QR ya viene como PNG data URL. Lo mostramos sobre bg blanco para max contraste (los QR son oscuros). --}}
                    <div class="flex justify-center bg-white p-4 rounded-card">
                        <img src="{{ $qrCodeInline }}" alt="Codigo QR 2FA" class="h-64 w-64">
                    </div>
                @else
                    <div class="flex justify-center bg-white p-4 rounded-card border border-wv-border">
                        {!! $qrCodeInline !!}
                    </div>
                @endif

                <div class="mt-4 p-3 bg-wv-bg border border-wv-border rounded-card">
                    <p class="text-xs font-semibold text-wv-text-secondary mb-1">
                        {{ __('O ingresa este secreto manualmente:') }}
                    </p>
                    <code class="block font-mono text-sm text-wv-text select-all break-all">{{ $secret }}</code>
                </div>
            </div>

            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <h3 class="text-lg font-semibold text-wv-text mb-2">
                    {{ __('Paso 2: Ingresa el codigo de verificacion') }}
                </h3>
                <p class="text-sm text-wv-text-secondary mb-4">
                    {{ __('Tu aplicacion te mostrara un codigo de 6 digitos que rota cada 30 segundos. Ingresalo aqui para confirmar que la configuracion funciona.') }}
                </p>

                @if (session('status') === 'two-factor-enabled')
                    <div class="mb-4 rounded-card bg-wv-surface-hover border border-wv-success/40 p-4 text-sm text-wv-success">
                        {{ __('Autenticacion en 2 pasos activada correctamente. Guarda tus codigos de recuperacion.') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.two-factor.confirm') }}">
                    @csrf

                    <div>
                        <x-input-label for="code" :value="__('Codigo de 6 digitos')" />
                        <x-text-input id="code" name="code" type="text"
                                      inputmode="numeric" autocomplete="one-time-code"
                                      pattern="[0-9]{6}" maxlength="6"
                                      class="mt-1 block w-full font-mono text-lg tracking-widest text-center"
                                      placeholder="000000"
                                      required autofocus />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('profile.edit') }}"
                           class="text-sm text-wv-text-secondary hover:text-wv-text underline">
                            {{ __('Cancelar') }}
                        </a>
                        <x-primary-button>
                            {{ __('Confirmar y activar') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
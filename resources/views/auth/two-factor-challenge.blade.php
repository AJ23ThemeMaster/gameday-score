<x-guest-layout>
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-wv-text">
            {{ __('Verificacion en 2 pasos') }}
        </h2>
        <p class="mt-1 text-sm text-wv-text-secondary">
            {{ __('Hola, ') }}<span class="font-medium text-wv-text">{{ auth()->user()->name }}</span>{{ __('. Ingresa el codigo de 6 digitos de tu aplicacion autenticadora para continuar.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge.verify') }}" id="two-factor-form" x-data="{ digits: '' }">
        @csrf

        <!-- 2FA Code -->
        <div>
            <x-input-label for="code" :value="__('Codigo de 6 digitos')" />
            <x-text-input id="code"
                          name="code"
                          type="text"
                          inputmode="numeric"
                          autocomplete="one-time-code"
                          pattern="[0-9]{6}"
                          maxlength="6"
                          class="block mt-1 w-full font-mono text-2xl tracking-widest text-center"
                          placeholder="000000"
                          x-model="digits"
                          @input="if (digits.length === 6) document.getElementById('two-factor-form').submit()"
                          required
                          autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
            <p class="mt-2 text-xs text-wv-text-secondary text-center">
                {{ __('El formulario se envia automaticamente al completar los 6 digitos.') }}
            </p>
        </div>

        <!-- Boton manual por si el autocompletar del navegador no dispara el evento -->
        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('two-factor.challenge.cancel') }}"
               class="underline text-sm text-wv-text-secondary hover:text-wv-text rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wv-accent focus:ring-offset-wv-bg">
                {{ __('Cancelar y cerrar sesion') }}
            </a>

            <x-primary-button class="ms-3">
                {{ __('Verificar') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
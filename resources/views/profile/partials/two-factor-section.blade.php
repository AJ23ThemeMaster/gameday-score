@php
    /** @var \App\Models\User $user */
    $user = $user ?? auth()->user();
    $enabled = $user->hasTwoFactorEnabled();
@endphp

<section>
    <header class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Autenticacion en 2 pasos (2FA)') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('Añade una capa extra de seguridad exigiendo un codigo de un solo uso (TOTP) generado en tu app autenticadora (Google Authenticator, Authy, 1Password, etc.) al iniciar sesion.') }}
            </p>
        </div>
        @if ($enabled)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                {{ __('Activado') }}
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                {{ __('Desactivado') }}
            </span>
        @endif
    </header>

    @if ($enabled)
        <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-md text-sm text-emerald-800">
            <p class="font-medium">{{ __('La autenticacion en 2 pasos esta activa.') }}</p>
            <p class="mt-1">
                {{ __('Si pierdes acceso a tu app autenticadora, usa los codigos de recuperacion que guardaste al activarla.') }}
            </p>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('profile.two-factor.recovery-codes.show') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                {{ __('Ver codigos de recuperacion') }}
            </a>

            {{-- Boton Regenerar codigos (abre modal con password) --}}
            <button type="button"
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'confirm-regenerate-codes')"
                    class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-md transition">
                {{ __('Regenerar codigos') }}
            </button>

            {{-- Boton Desactivar 2FA (abre modal con password) --}}
            <button type="button"
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'confirm-disable-two-factor')"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-md transition">
                {{ __('Desactivar 2FA') }}
            </button>
        </div>

        {{-- Modal: Regenerar codigos --}}
        <x-modal name="confirm-regenerate-codes" :show="$errors->regenerateCodes->isNotEmpty()" focusable>
            <form method="post" action="{{ route('profile.two-factor.recovery-codes') }}" class="p-6">
                @csrf
                @method('post')

                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('¿Regenerar codigos de recuperacion?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Los 8 codigos anteriores dejaran de ser validos inmediatamente. Se generaran 8 nuevos codigos que deberas guardar en un lugar seguro.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="regenerate_password" :value="__('Tu contraseña')" class="sr-only" />
                    <x-text-input
                        id="regenerate_password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        :placeholder="__('Contraseña actual')"
                    />
                    <x-input-error :messages="$errors->regenerateCodes->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancelar') }}
                    </x-secondary-button>

                    <x-primary-button class="ms-3 bg-amber-500 hover:bg-amber-600">
                        {{ __('Regenerar') }}
                    </x-primary-button>
                </div>
            </form>
        </x-modal>

        {{-- Modal: Desactivar 2FA --}}
        <x-modal name="confirm-disable-two-factor" :show="$errors->disableTwoFactor->isNotEmpty()" focusable>
            <form method="post" action="{{ route('profile.two-factor.disable') }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('¿Desactivar autenticacion en 2 pasos?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Tu cuenta quedara protegida solo por la contraseña. Cualquier persona con tu contraseña podra acceder.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="disable_password" :value="__('Tu contraseña')" class="sr-only" />
                    <x-text-input
                        id="disable_password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        :placeholder="__('Contraseña actual')"
                    />
                    <x-input-error :messages="$errors->disableTwoFactor->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancelar') }}
                    </x-secondary-button>

                    <x-danger-button class="ms-3">
                        {{ __('Desactivar') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    @else
        <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700">
            {{ __('La autenticacion en 2 pasos no esta activa. Te recomendamos activarla para mejorar la seguridad de tu cuenta.') }}
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <form method="post" action="{{ route('profile.two-factor.enable') }}">
                @csrf
                <x-primary-button>
                    {{ __('Activar 2FA') }}
                </x-primary-button>
            </form>
        </div>
    @endif
</section>

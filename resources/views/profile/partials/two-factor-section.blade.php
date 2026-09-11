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
            <a href="{{ route('profile.two-factor.recovery-codes') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                {{ __('Ver codigos de recuperacion') }}
            </a>
            <form method="post" action="{{ route('profile.two-factor.recovery-codes') }}" class="inline"
                  onsubmit="return confirm('Regenerar los codigos invalida los anteriores. ¿Continuar?');">
                @csrf
                @method('post')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-md transition">
                    {{ __('Regenerar codigos') }}
                </button>
            </form>
            <form method="post" action="{{ route('profile.two-factor.disable') }}" class="inline"
                  onsubmit="return confirm('¿Desactivar 2FA? Tu cuenta quedara protegida solo por la contraseña.');">
                @csrf
                @method('delete')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-md transition">
                    {{ __('Desactivar 2FA') }}
                </button>
            </form>
        </div>

        @if ($errors->disableTwoFactor->isNotEmpty())
            <div class="mt-3 text-sm text-red-600">
                {{ $errors->disableTwoFactor->first() }}
            </div>
        @endif
        @if ($errors->regenerateCodes->isNotEmpty())
            <div class="mt-3 text-sm text-red-600">
                {{ $errors->regenerateCodes->first() }}
            </div>
        @endif
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

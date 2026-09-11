<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Codigos de recuperacion de 2FA') }}
            </h2>
            <a href="{{ route('profile.edit') }}"
               class="text-sm text-gray-600 hover:text-gray-900 underline">
                {{ __('← Volver a Mi perfil') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-amber-50 border border-amber-200 rounded-md p-4 text-sm text-amber-800">
                <p class="font-semibold mb-1">{{ __('Importante: guarda estos codigos en un lugar seguro.') }}</p>
                <p>
                    {{ __('Cada codigo solo puede usarse una vez. Si pierdes acceso a tu aplicacion autenticadora, puedes usar uno de estos codigos para iniciar sesion.') }}
                </p>
            </div>

            @if (session('status') === 'recovery-codes-regenerated')
                <div class="rounded-md bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                    {{ __('Codigos de recuperacion regenerados. Los anteriores ya no son validos.') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ __('Tus codigos') }}
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono text-base">
                    @foreach ($recoveryCodes as $code)
                        <div class="bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-center text-gray-900 select-all">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-wrap gap-3 justify-end">
                    <a href="{{ route('profile.edit') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition">
                        {{ __('Volver a Mi perfil') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

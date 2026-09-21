<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
            {{ __('Mi perfil') }}
        </h2>
        <p class="text-sm text-wv-text-secondary mt-1">
            {{ __('Gestiona tu informacion, contraseña y opciones de seguridad.') }}
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('partials._flash')

            <div class="p-4 sm:p-8 bg-wv-surface border border-wv-border rounded-card">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-wv-surface border border-wv-border rounded-card">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-wv-surface border border-wv-border rounded-card">
                <div class="max-w-xl">
                    @include('profile.partials.two-factor-section')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-wv-surface border border-wv-border rounded-card">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
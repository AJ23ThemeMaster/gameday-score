<x-guest-layout>
    {{-- DISI-82: vista de fallback PWA cuando no hay red. --}}
    <div class="text-center py-12">
        <div class="mx-auto mb-6 h-16 w-16 rounded-full bg-wv-surface border border-wv-border flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-wv-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M12 9v4m0 4h.01M5.05 13.05a9 9 0 0113.9-7.418" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-wv-text mb-2">{{ __('Sin conexión') }}</h1>
        <p class="text-wv-text-secondary mb-6">
            {{ __('No pudimos conectar con el servidor. Revisa tu conexión a internet e intenta de nuevo.') }}
        </p>
        <button type="button" onclick="location.reload()"
                class="inline-flex items-center px-4 py-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent font-semibold rounded-card transition">
            {{ __('Reintentar') }}
        </button>
    </div>
</x-guest-layout>
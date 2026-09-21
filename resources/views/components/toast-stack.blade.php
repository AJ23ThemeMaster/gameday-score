{{--
    Componente de notificaciones toast. Se incluye una vez en el layout (via
    x-app-layout o en una vista) y se controla via $store.toast.

    Uso desde JS:
        window.dispatchEvent(new CustomEvent('toast', { detail: { level: 'success', message: 'Hecho' } }))
        o via Alpine: $store.toast.show('Hecho', 'success')

    WattVision: variantes dark con borde lateral de 3px segun nivel (success/error/warning/info).
    Cada toast usa bg-wv-surface + texto coherente con el palette.
--}}
@props([])

<div
    x-data="toastStack"
    @toast.window="show($event.detail.message, $event.detail.level || 'success', $event.detail.timeout)"
    class="fixed top-4 right-4 z-[100] space-y-2 pointer-events-none"
    aria-live="polite"
>
    <template x-for="t in items" :key="t.id">
        <div
            x-show="t.visible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
            :class="{
                'bg-wv-surface border-wv-success text-wv-text border-l-[3px]': t.level === 'success',
                'bg-wv-surface border-wv-alert text-wv-text border-l-[3px]': t.level === 'error',
                'bg-wv-surface border-wv-accent text-wv-text border-l-[3px]': t.level === 'warning',
                'bg-wv-surface border-wv-border-strong text-wv-text border-l-[3px]': t.level === 'info',
            }"
            class="pointer-events-auto min-w-[280px] max-w-md border rounded-card shadow-lg px-4 py-3 flex items-start gap-3"
        >
            <div class="flex-shrink-0 mt-0.5">
                <template x-if="t.level === 'success'">
                    <span class="material-symbols-outlined text-wv-success text-[20px]">check_circle</span>
                </template>
                <template x-if="t.level === 'error'">
                    <span class="material-symbols-outlined text-wv-alert text-[20px]">cancel</span>
                </template>
                <template x-if="t.level === 'warning'">
                    <span class="material-symbols-outlined text-wv-accent text-[20px]">warning</span>
                </template>
                <template x-if="t.level === 'info' || !['success','error','warning','info'].includes(t.level)">
                    <span class="material-symbols-outlined text-wv-text-secondary text-[20px]">info</span>
                </template>
            </div>
            <div class="flex-1 text-sm font-medium" x-text="t.message"></div>
            <button type="button" @click="dismiss(t.id)" class="flex-shrink-0 text-wv-text-secondary hover:text-wv-text">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
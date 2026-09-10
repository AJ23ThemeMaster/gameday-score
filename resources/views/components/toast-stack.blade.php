{{--
    Componente de notificaciones toast. Se incluye una vez en el layout (via
    x-app-layout o en una vista) y se controla via $store.toast.

    Uso desde JS:
        window.dispatchEvent(new CustomEvent('toast', { detail: { level: 'success', message: 'Hecho' } }))
        o via Alpine: $store.toast.show('Hecho', 'success')
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
                'bg-emerald-50 border-emerald-300 text-emerald-900': t.level === 'success',
                'bg-rose-50 border-rose-300 text-rose-900': t.level === 'error',
                'bg-amber-50 border-amber-300 text-amber-900': t.level === 'warning',
                'bg-sky-50 border-sky-300 text-sky-900': t.level === 'info',
            }"
            class="pointer-events-auto min-w-[280px] max-w-md border rounded-lg shadow-lg px-4 py-3 flex items-start gap-3"
        >
            <div class="flex-shrink-0 mt-0.5">
                <template x-if="t.level === 'success'">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="t.level === 'error'">
                    <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </template>
                <template x-if="t.level === 'warning'">
                    <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.34 16a2 2 0 001.73 3z"/></svg>
                </template>
                <template x-if="t.level === 'info' || !['success','error','warning','info'].includes(t.level)">
                    <svg class="w-5 h-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
            </div>
            <div class="flex-1 text-sm font-medium" x-text="t.message"></div>
            <button type="button" @click="dismiss(t.id)" class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

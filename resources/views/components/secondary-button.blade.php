{{--
  WattVision: boton secundario con superficie. Usar para acciones neutras
  (Cancelar, Volver, Cerrar modal).
--}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-wv-surface border border-wv-border rounded-card font-semibold text-xs text-wv-text uppercase tracking-widest hover:bg-wv-surface-hover hover:border-wv-border-strong focus:outline-none focus:ring-2 focus:ring-wv-accent focus:ring-offset-2 focus:ring-offset-wv-bg disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
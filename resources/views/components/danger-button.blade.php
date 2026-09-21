{{--
  WattVision: boton de peligro con acento rojo. Usar para acciones
  destructivas (Eliminar, Desactivar).
--}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-wv-alert border border-transparent rounded-card font-semibold text-xs text-wv-text-on-alert uppercase tracking-widest hover:bg-wv-alert-hover focus:bg-wv-alert-hover active:bg-wv-alert-hover focus:outline-none focus:ring-2 focus:ring-wv-alert focus:ring-offset-2 focus:ring-offset-wv-bg transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
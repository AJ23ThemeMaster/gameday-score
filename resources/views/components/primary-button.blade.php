{{--
  WattVision: boton primario con acento cyan. Texto oscuro sobre fondo
  brillante para max legibilidad. Usar para acciones principales (Guardar,
  Crear, Confirmar).
--}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-wv-accent border border-transparent rounded-card font-semibold text-xs text-wv-text-on-accent uppercase tracking-widest hover:bg-wv-accent-hover focus:bg-wv-accent-hover active:bg-wv-accent-hover focus:outline-none focus:ring-2 focus:ring-wv-accent focus:ring-offset-2 focus:ring-offset-wv-bg disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
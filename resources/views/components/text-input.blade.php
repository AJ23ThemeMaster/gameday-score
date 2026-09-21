@props(['disabled' => false])

{{--
  WattVision: input de texto con superficie dark y acento cyan al enfocar.
  Texto blanco, placeholder gris, ring cyan en focus.
--}}
<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm disabled:opacity-50']) }}>
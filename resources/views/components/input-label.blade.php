@props(['value'])

{{--
  WattVision: label con texto blanco para contraste sobre fondo dark.
--}}
<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-wv-text']) }}>
    {{ $value ?? $slot }}
</label>
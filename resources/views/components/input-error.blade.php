@props(['messages'])

{{--
  WattVision: errores en rojo WattVision (#FF453A). Mantener spacing del Breeze.
--}}
@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-wv-alert space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
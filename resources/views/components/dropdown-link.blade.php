{{--
  WattVision: enlace dentro del dropdown con hover surface.
--}}
<a {{ $attributes->merge(['class' => 'block w-full px-4 py-2 text-start text-sm leading-5 text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface-hover focus:outline-none focus:bg-wv-surface-hover focus:text-wv-text transition duration-150 ease-in-out']) }}>{{ $slot }}</a>
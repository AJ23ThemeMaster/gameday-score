@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-wv-accent text-sm font-medium leading-5 text-wv-text focus:outline-none focus:border-wv-accent transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-wv-text-secondary hover:text-wv-text hover:border-wv-border-strong focus:outline-none focus:text-wv-text focus:border-wv-border-strong transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
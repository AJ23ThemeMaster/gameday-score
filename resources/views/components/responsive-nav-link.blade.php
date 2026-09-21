@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-wv-accent text-start text-base font-medium text-wv-text bg-wv-surface focus:outline-none focus:text-wv-text focus:bg-wv-surface focus:border-wv-accent transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface hover:border-wv-border-strong focus:outline-none focus:text-wv-text focus:bg-wv-surface focus:border-wv-border-strong transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
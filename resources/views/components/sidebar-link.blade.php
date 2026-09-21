@props([
    'active' => false,
    'icon' => null,
    'href' => '#',
    'external' => false,
])

{{--
  WattVision: item del sidebar vertical. Renderiza un enlace con icono
  material-symbols-outlined + label.

  - active=true: bg-wv-surface-hover + border-l-[3px] wv-accent + texto wv-text
                 (mismo acento cyan que el resto del sistema).
  - hover: bg-wv-surface-hover (sobre el item inactivo).
  - external=true: usa <a target="_blank"> y agrega un icono pequeno open_in_new.

  Si se pasa $icon y el slot esta vacio, el label se toma de props como fallback.
  Esto permite usar <x-sidebar-link :href="..." icon="dashboard">Dashboard</x-sidebar-link>
  sin duplicar el label.
--}}
@php
$base = 'group flex items-center gap-3 px-4 py-2.5 text-sm font-medium border-l-[3px] transition';
$activeClasses = 'border-wv-accent bg-wv-surface-hover text-wv-text';
$inactiveClasses = 'border-transparent text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface-hover';
@endphp

@if ($external)
    <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" {{ $attributes->merge(['class' => $base . ' ' . ($active ? $activeClasses : $inactiveClasses)]) }}>
        @if ($icon)
            <span class="material-symbols-outlined text-[20px] flex-shrink-0 {{ $active ? 'text-wv-accent' : 'text-wv-text-secondary group-hover:text-wv-accent' }} transition">{{ $icon }}</span>
        @endif
        <span class="flex-1 truncate">{{ $slot }}</span>
        <span class="material-symbols-outlined text-[14px] text-wv-text-secondary">open_in_new</span>
    </a>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base . ' ' . ($active ? $activeClasses : $inactiveClasses)]) }}>
        @if ($icon)
            <span class="material-symbols-outlined text-[20px] flex-shrink-0 {{ $active ? 'text-wv-accent' : 'text-wv-text-secondary group-hover:text-wv-accent' }} transition">{{ $icon }}</span>
        @endif
        <span class="flex-1 truncate">{{ $slot }}</span>
    </a>
@endif
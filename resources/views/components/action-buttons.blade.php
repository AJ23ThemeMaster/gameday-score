@props([
    'show' => null,             // URL para ver (null = no mostrar boton)
    'edit' => null,             // URL para editar (null = no mostrar boton)
    'delete' => null,           // URL para eliminar (null = no mostrar boton)
    'deleteMessage' => null,    // Mensaje del swal de confirm; null = mensaje default
])

@php
    // Mensaje default para el confirm de eliminar.
    $defaultDeleteMsg = '¿Eliminar este registro?';
    $msg = $deleteMessage ?? $defaultDeleteMsg;

    // Clases comunes para los 3 botones (cuadrado con borde).
    $baseBtn = 'inline-flex items-center justify-center w-8 h-8 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-wv-bg';

    // Variantes por color. Hover: fondo con alpha del color.
    $verBtn  = $baseBtn . ' border-wv-success/40 text-wv-success hover:bg-wv-success/15 hover:border-wv-success focus:ring-wv-success';
    $editBtn = $baseBtn . ' border-wv-accent/40 text-wv-accent hover:bg-wv-accent/15 hover:border-wv-accent focus:ring-wv-accent';
    $delBtn  = $baseBtn . ' border-wv-alert/40 text-wv-alert hover:bg-wv-alert/15 hover:border-wv-alert focus:ring-wv-alert';
@endphp

<div class="inline-flex items-center gap-1">
    @if ($show)
        <a href="{{ $show }}" title="{{ __('Ver') }}" aria-label="{{ __('Ver') }}" class="{{ $verBtn }}">
            <span class="material-symbols-outlined !text-[16px]">visibility</span>
        </a>
    @endif

    @if ($edit)
        <a href="{{ $edit }}" title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}" class="{{ $editBtn }}">
            <span class="material-symbols-outlined !text-[16px]">edit</span>
        </a>
    @endif

    @if ($delete)
        <form action="{{ $delete }}" method="POST" class="inline"
              data-confirm="'{{ $msg }}'" data-confirm-danger="true" data-loader>
            @csrf
            @method('DELETE')
            <button type="submit" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar') }}" class="{{ $delBtn }}">
                <span class="material-symbols-outlined !text-[16px]">delete_forever</span>
            </button>
        </form>
    @endif
</div>

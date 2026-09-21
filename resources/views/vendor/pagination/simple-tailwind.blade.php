@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Navegación de paginación') }}" class="flex gap-2 items-center justify-between">

        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-wv-text-secondary bg-wv-surface border border-wv-border cursor-not-allowed leading-5 rounded-md">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-wv-text bg-wv-surface border border-wv-border leading-5 rounded-md hover:bg-wv-surface-hover hover:border-wv-border-strong focus:outline-none focus:ring-2 focus:ring-wv-accent focus:ring-offset-2 focus:ring-offset-wv-bg transition ease-in-out duration-150">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-wv-text bg-wv-surface border border-wv-border leading-5 rounded-md hover:bg-wv-surface-hover hover:border-wv-border-strong focus:outline-none focus:ring-2 focus:ring-wv-accent focus:ring-offset-2 focus:ring-offset-wv-bg transition ease-in-out duration-150">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-wv-text-secondary bg-wv-surface border border-wv-border cursor-not-allowed leading-5 rounded-md">
                {!! __('pagination.next') !!}
            </span>
        @endif

    </nav>
@endif
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Navegación de paginación') }}">

        {{-- Mobile: solo anterior/siguiente --}}
        <div class="flex gap-2 items-center justify-between sm:hidden">

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

        </div>

        {{-- Desktop: rango numerado + anterior/siguiente --}}
        <div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">

            <div>
                <p class="text-sm text-wv-text-secondary leading-5">
                    {!! __('Mostrando') !!}
                    @if ($paginator->firstItem())
                        <span class="font-medium text-wv-text">{{ $paginator->firstItem() }}</span>
                        {!! __('a') !!}
                        <span class="font-medium text-wv-text">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('de') !!}
                    <span class="font-medium text-wv-text">{{ $paginator->total() }}</span>
                    {!! __('resultados') !!}
                </p>
            </div>

            <div>
                <span class="inline-flex rtl:flex-row-reverse shadow-sm rounded-md">

                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="inline-flex items-center px-2 py-2 text-sm font-medium text-wv-text-secondary bg-wv-surface border border-wv-border cursor-not-allowed rounded-l-md leading-5" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-2 py-2 text-sm font-medium text-wv-text-secondary bg-wv-surface border border-wv-border rounded-l-md leading-5 hover:bg-wv-surface-hover hover:text-wv-text focus:outline-none focus:ring-2 focus:ring-wv-accent focus:ring-offset-2 focus:ring-offset-wv-bg transition ease-in-out duration-150" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-wv-text-secondary bg-wv-surface border border-wv-border cursor-default leading-5">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-bold text-wv-text-on-accent bg-wv-accent border border-wv-accent cursor-default leading-5">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-wv-text-secondary bg-wv-surface border border-wv-border leading-5 hover:bg-wv-surface-hover hover:text-wv-text focus:outline-none focus:ring-2 focus:ring-wv-accent focus:ring-offset-2 focus:ring-offset-wv-bg transition ease-in-out duration-150" aria-label="{{ __('Ir a página :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-wv-text-secondary bg-wv-surface border border-wv-border rounded-r-md leading-5 hover:bg-wv-surface-hover hover:text-wv-text focus:outline-none focus:ring-2 focus:ring-wv-accent focus:ring-offset-2 focus:ring-offset-wv-bg transition ease-in-out duration-150" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-wv-text-secondary bg-wv-surface border border-wv-border cursor-not-allowed rounded-r-md leading-5" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
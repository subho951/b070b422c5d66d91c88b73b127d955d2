@if ($paginator->hasPages())
    <nav class="listing-pagination" aria-label="{{ __('Pagination Navigation') }}">
        <div class="pagination-summary">
            <p class="pagination-result-count">Showing <strong>{{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }}</strong> of <strong>{{ $paginator->total() }}</strong> results</p>
            <span class="pagination-position">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        </div>

        <ul class="pagination-controls">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pagination-page pagination-direction" aria-disabled="true" aria-label="Previous page">&lsaquo;<span>Previous</span></span>
                @else
                    <a class="pagination-page pagination-direction" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;<span>Previous</span></a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pagination-ellipsis" aria-hidden="true">{{ $element }}</span></li>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-page" aria-current="page" aria-label="Page {{ $page }}, current page">{{ $page }}</span>
                            @else
                                <a class="pagination-page" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="pagination-page pagination-direction" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"><span>Next</span>&rsaquo;</a>
                @else
                    <span class="pagination-page pagination-direction" aria-disabled="true" aria-label="Next page"><span>Next</span>&rsaquo;</span>
                @endif
            </li>
        </ul>
    </nav>
@endif

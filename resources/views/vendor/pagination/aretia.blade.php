@if ($paginator->hasPages() || $paginator->total() > 0)
<nav class="listing-pagination" role="navigation" aria-label="Pagination">
    <div class="listing-pagination-info">
        @if($paginator->total() > 0)
            Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong> results
        @else
            No results
        @endif
    </div>
    @if ($paginator->hasPages())
    <ul class="listing-pagination-links">
        @if ($paginator->onFirstPage())
            <li><span class="page-btn disabled" aria-disabled="true">Previous</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" class="page-btn" rel="prev">Previous</a></li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <li><span class="page-ellipsis">{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li><span class="page-btn active" aria-current="page">{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}" class="page-btn">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" class="page-btn" rel="next">Next</a></li>
        @else
            <li><span class="page-btn disabled" aria-disabled="true">Next</span></li>
        @endif
    </ul>
    @endif
</nav>
@endif

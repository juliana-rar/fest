@if ($paginator->hasPages())
    <nav class="paginacio" aria-label="{{ __('admin.pagination') }}">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">‹ {{ __('app.previous') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ {{ __('app.previous') }}</a>
        @endif

        @if (method_exists($paginator, 'lastPage'))
            <span aria-current="page">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('app.next') }} ›</a>
        @else
            <span aria-disabled="true">{{ __('app.next') }} ›</span>
        @endif
    </nav>
@endif

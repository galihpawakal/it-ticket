@if ($paginator->hasPages())
    <nav class="pager" aria-label="Halaman tiket">
        @if ($paginator->onFirstPage())
            <span class="btn disabled" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif

        <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span class="btn disabled" aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif

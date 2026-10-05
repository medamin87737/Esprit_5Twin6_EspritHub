@props(['items'])

@if ($items instanceof \Illuminate\Contracts\Pagination\Paginator && $items->hasPages())
    <nav {{ $attributes->class(['mt-5 d-flex justify-content-center']) }} aria-label="Pagination">
        {{ $items->withQueryString()->links('pagination::bootstrap-5') }}
    </nav>
@endif

{{-- Laravel paginator view in the site's style. Links keep the current query string (filters). --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="mt-10 flex items-center justify-between gap-4">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="inline-flex cursor-not-allowed items-center gap-1 rounded-md border border-dashed border-border px-3 py-2 text-sm text-muted-foreground"><x-lucide-chevron-left class="size-4" /> Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium hover:border-primary hover:text-primary"><x-lucide-chevron-left class="size-4" /> Previous</a>
        @endif

        <ol class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="px-2 text-sm text-muted-foreground">{{ $element }}</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="flex size-9 items-center justify-center rounded-md bg-primary text-sm font-semibold text-primary-foreground">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="flex size-9 items-center justify-center rounded-md text-sm text-muted-foreground hover:bg-accent hover:text-foreground" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ol>
        <p class="text-sm text-muted-foreground sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium hover:border-primary hover:text-primary">Next <x-lucide-chevron-right class="size-4" /></a>
        @else
            <span aria-disabled="true" class="inline-flex cursor-not-allowed items-center gap-1 rounded-md border border-dashed border-border px-3 py-2 text-sm text-muted-foreground">Next <x-lucide-chevron-right class="size-4" /></span>
        @endif
    </nav>
@endif

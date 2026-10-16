{{-- @param list<array{0: string, 1: ?string}> $items  [label, url]; the last item is the current page. --}}
@props(['items'])
<nav aria-label="Breadcrumb" {{ $attributes->class('text-sm text-muted-foreground') }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ url('/') }}" class="hover:text-primary">Home</a></li>
        @foreach ($items as [$label, $href])
            <li class="flex items-center gap-1.5">
                <x-lucide-chevron-right class="size-3.5 shrink-0" aria-hidden="true" />
                @if ($href && ! $loop->last)
                    <a href="{{ $href }}" class="hover:text-primary">{{ $label }}</a>
                @else
                    <span aria-current="page" class="text-foreground">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

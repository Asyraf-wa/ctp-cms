{{-- "On this page" with scroll-spy. $items: [['id', 'label', 'level' => 2|3]] (built from H2/H3, ART render pipeline). --}}
@props(['items' => []])
<h3 class="mb-4 text-xs font-semibold tracking-wider text-foreground uppercase">On this page</h3>
<nav x-data="toc" class="border-l border-border text-sm" aria-label="Table of contents">
    @foreach ($items as $item)
        <a href="#{{ $item['id'] }}" data-toc="{{ $item['id'] }}"
            :class="active === @js($item['id']) ? '-ml-px border-l-2 border-primary font-medium text-primary' : 'text-muted-foreground hover:text-foreground'"
            @class([
                'block transition-colors',
                'py-1.5 pl-4' => ($item['level'] ?? 2) === 2,
                'py-1 pl-8' => ($item['level'] ?? 2) === 3,
            ])>{{ $item['label'] }}</a>
    @endforeach
</nav>

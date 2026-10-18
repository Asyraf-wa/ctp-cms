{{--
    The logo that represents an article in lists: its main technology, else its category's technology,
    else the category's Lucide icon. Always renders something so rows line up.
--}}
@props(['article', 'size' => 'size-5', 'tile' => true])
@php($technology = $article->primaryTechnology())
<span {{ $attributes->class(['flex shrink-0 items-center justify-center', 'size-10 rounded-lg border border-border bg-surface-2' => $tile]) }}>
    @if ($technology)
        <x-site.tech-icon :technology="$technology" :size="$size" />
    @else
        <span class="tech-icon inline-flex">
            <x-dynamic-component :component="'lucide-'.($article->category?->icon ?: ($article->type->value === 'blog' ? 'pen-line' : 'code-xml'))" :class="$size" />
        </span>
    @endif
</span>

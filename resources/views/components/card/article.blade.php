{{-- Article card (DESIGN §5): cover, category + difficulty, title, two-line excerpt, date and reading time. --}}
@props(['article', 'eager' => false, 'showCover' => true])
@php($url = url($article->publicPath() ?? '/'))
<article {{ $attributes->class('group relative flex flex-col overflow-hidden rounded-xl border border-border bg-card shadow-xs transition duration-200 hover:-translate-y-0.5 hover:shadow-md motion-reduce:transition-none motion-reduce:hover:translate-y-0') }}>
    @if ($showCover)
        <x-site.cover :media="$article->featuredMedia" :icon="$article->category?->icon ?: ($article->type->value === 'blog' ? 'pen-line' : 'code-xml')" :eager="$eager" />
    @endif
    <div class="flex flex-1 flex-col p-5">
        <div class="mb-2 flex flex-wrap items-center gap-2">
            @if ($article->type->value === 'blog')
                <span class="text-xs font-semibold tracking-wide text-primary uppercase">Blog</span>
            @elseif ($article->category)
                <span class="text-xs font-semibold tracking-wide text-primary uppercase">{{ $article->category->name }}</span>
            @endif
            <x-site.difficulty :level="$article->difficulty" />
            @if ($article->is_pinned)
                <span class="inline-flex items-center gap-1 text-xs text-muted-foreground"><x-lucide-pin class="size-3" /> Pinned</span>
            @endif
        </div>
        <h3 class="text-lg leading-snug font-semibold text-foreground">
            {{-- The whole card is clickable through this link's ::after. --}}
            <a href="{{ $url }}" class="after:absolute after:inset-0 group-hover:text-primary focus:outline-none focus-visible:after:rounded-xl focus-visible:after:ring-2 focus-visible:after:ring-ring">{{ $article->title }}</a>
        </h3>
        @if ($article->excerpt)
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-muted-foreground">{{ $article->excerpt }}</p>
        @endif
        <p class="mt-auto flex items-center gap-2 pt-4 text-xs text-muted-foreground">
            @if ($article->published_at)
                <time datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->timezone(config('ctp.display_timezone'))->format('M j, Y') }}</time>
                <span aria-hidden="true">•</span>
            @endif
            <span>{{ $article->readingMinutes() }} min read</span>
        </p>
    </div>
</article>

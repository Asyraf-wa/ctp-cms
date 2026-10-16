{{-- Compact list row for archives and search: title, excerpt, meta. No cover. --}}
@props(['article', 'heading' => 'h3'])
<article class="group relative flex gap-4 border-b border-border py-5 last:border-b-0">
    <x-site.article-logo :article="$article" class="mt-0.5" />
    <div class="min-w-0 flex-1">
        <div class="mb-1 flex flex-wrap items-center gap-2 text-xs">
            @if ($article->category)
                <span class="font-semibold tracking-wide text-primary uppercase">{{ $article->category->name }}</span>
            @endif
            <x-site.difficulty :level="$article->difficulty" />
        </div>
        <{{ $heading }} class="font-semibold text-foreground">
            <a href="{{ url($article->publicPath() ?? '/') }}" class="after:absolute after:inset-0 group-hover:text-primary focus:outline-none focus-visible:underline">{{ $article->title }}</a>
        </{{ $heading }}>
        @if ($article->excerpt)
            <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">{{ $article->excerpt }}</p>
        @endif
        <p class="mt-2 text-xs text-muted-foreground">
            @if ($article->published_at)
                <time datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->timezone(config('ctp.display_timezone'))->format('M j, Y') }}</time> ·
            @endif
            {{ $article->readingMinutes() }} min read
        </p>
    </div>
    <x-lucide-chevron-right class="mt-6 size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary" />
</article>

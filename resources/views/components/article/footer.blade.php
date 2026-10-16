{{-- Tags, source/demo links, prev/next (series first) and the author box (AUTHOR-01). --}}
@props(['article', 'previous' => null, 'next' => null])
<div class="not-prose mt-12 space-y-8">
    @if ($article->tags->isNotEmpty() || $article->repo_url || $article->demo_url)
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($article->tags as $tag)
                <a href="{{ url($tag->publicPath()) }}" class="rounded-full border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition-colors hover:border-primary hover:text-primary">#{{ $tag->name }}</a>
            @endforeach
            @if ($article->repo_url)
                <a href="{{ $article->repo_url }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"><x-lucide-github class="size-4" /> Source code</a>
            @endif
            @if ($article->demo_url)
                <a href="{{ $article->demo_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"><x-lucide-external-link class="size-4" /> Live demo</a>
            @endif
        </div>
    @endif

    @if ($previous || $next)
        <nav class="flex items-center justify-between gap-4 border-t border-border pt-8" aria-label="More tutorials">
            @if ($previous)
                <a href="{{ url($previous->publicPath()) }}" class="flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
                    <x-lucide-chevron-left class="size-4 shrink-0" /> <span>Previous: {{ $previous->title }}</span>
                </a>
            @else
                <span></span>
            @endif
            @if ($next)
                <a href="{{ url($next->publicPath()) }}" class="flex items-center gap-2 text-right text-sm font-medium text-primary transition-colors hover:text-primary-hover">
                    <span>Next: {{ $next->title }}</span> <x-lucide-chevron-right class="size-4 shrink-0" />
                </a>
            @endif
        </nav>
    @endif

    @if ($author = $article->author)
        <aside class="flex gap-4 rounded-xl border border-border bg-surface-2 p-5" aria-label="About the author">
            @if ($author->avatarUrl())
                <img src="{{ $author->avatarUrl() }}" alt="" class="size-14 shrink-0 rounded-full object-cover" width="56" height="56" referrerpolicy="no-referrer">
            @endif
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Written by</p>
                <a href="{{ url($author->publicPath() ?? '/') }}" class="font-semibold text-foreground hover:text-primary">{{ $author->authorName() }}</a>
                @if ($author->job_title)
                    <p class="text-sm text-muted-foreground">{{ $author->job_title }}</p>
                @endif
                @if ($author->bio_short)
                    <p class="mt-2 text-sm text-foreground">{{ $author->bio_short }}</p>
                @endif
            </div>
        </aside>
    @endif
</div>

{{-- Byline (AUTHOR-01), dates (ART-07), reading time and difficulty (ART-05). --}}
@props(['article'])
@php($tz = config('ctp.display_timezone'))
<div class="not-prose flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-muted-foreground">
    @if ($article->author)
        <a href="{{ url($article->author->publicPath() ?? '/') }}" class="flex items-center gap-2 font-medium text-foreground hover:text-primary">
            @if ($article->author->avatarUrl())
                <img src="{{ $article->author->avatarUrl() }}" alt="" class="size-7 rounded-full object-cover" width="28" height="28" referrerpolicy="no-referrer">
            @endif
            {{ $article->author->authorName() }}
        </a>
        <span aria-hidden="true">•</span>
    @endif
    @if ($article->published_at)
        <time datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->timezone($tz)->format('F j, Y') }}</time>
    @else
        <span>Not published yet</span>
    @endif
    @if ($updated = $article->lastUpdated())
        <span aria-hidden="true">•</span>
        <span>Updated <time datetime="{{ $updated->toAtomString() }}">{{ $updated->timezone($tz)->format('F j, Y') }}</time></span>
    @endif
    <span aria-hidden="true">•</span>
    <span>{{ $article->readingMinutes() }} min read</span>
    <x-site.difficulty :level="$article->difficulty" />
</div>

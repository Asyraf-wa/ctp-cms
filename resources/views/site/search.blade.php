{{-- Search results (PUB-06). Never indexed. --}}
@use('App\Search\Search')
<x-layouts.site :title="$query !== '' ? 'Search: '.$query : 'Search'" description="Search tutorials, tags and categories." robots="noindex, follow">
    <div class="mx-auto max-w-4xl px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header title="Search" :breadcrumbs="[['Search', null]]" />
        <x-site.search-trigger :value="$query" :palette="false" placeholder="Search tutorials, tags and categories" class="mb-8" />

        @if ($query === '')
            <h2 class="mb-4 text-lg font-semibold text-foreground">Popular tutorials</h2>
            <div class="rounded-xl border border-border bg-card px-5 sm:px-6">
                @foreach ($popular as $article)
                    <x-card.article-row :article="$article" />
                @endforeach
            </div>
        @else
            @if ($categories->isNotEmpty() || $tags->isNotEmpty())
                <div class="mb-6 flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-muted-foreground">Browse:</span>
                    @foreach ($categories as $category)
                        <a href="{{ url($category->publicPath()) }}" class="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1 font-medium hover:border-primary hover:text-primary">
                            <x-dynamic-component :component="'lucide-'.($category->icon ?: 'folder')" class="size-3.5" /> {{ $category->name }}
                        </a>
                    @endforeach
                    @foreach ($tags as $tag)
                        <a href="{{ url($tag->publicPath()) }}" class="rounded-full border border-border bg-card px-3 py-1 font-medium text-muted-foreground hover:border-primary hover:text-primary">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif

            <p class="mb-4 text-sm text-muted-foreground" aria-live="polite">
                {{ number_format($results?->total() ?? 0) }} {{ Str::plural('result', $results?->total() ?? 0) }} for “<span class="font-semibold text-foreground">{{ $query }}</span>”
            </p>

            @if ($results && $results->isNotEmpty())
                <ol class="rounded-xl border border-border bg-card px-5 sm:px-6">
                    @foreach ($results as $article)
                        <li class="group relative flex gap-4 border-b border-border py-5 last:border-b-0">
                            <x-site.article-logo :article="$article" class="mt-0.5" />
                            <div class="min-w-0 flex-1">
                            <p class="mb-1 flex flex-wrap items-center gap-2 text-xs">
                                <span class="font-semibold tracking-wide text-primary uppercase">{{ $article->type->value === 'blog' ? 'Blog' : $article->category?->name }}</span>
                                <x-site.difficulty :level="$article->difficulty" />
                            </p>
                            <h2 class="font-semibold text-foreground">
                                <a href="{{ url($article->publicPath()) }}" class="search-hit after:absolute after:inset-0 group-hover:text-primary">{!! Search::highlight($article->title, $query) !!}</a>
                            </h2>
                            @if ($article->excerpt)
                                <p class="search-hit mt-1 line-clamp-2 text-sm text-muted-foreground">{!! Search::highlight($article->excerpt, $query) !!}</p>
                            @endif
                            <p class="mt-2 text-xs text-muted-foreground">{{ $article->published_at->timezone(config('ctp.display_timezone'))->format('M j, Y') }} · {{ $article->readingMinutes() }} min read</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                {{ $results->links('site.partials.pagination') }}
            @else
                <x-site.empty icon="search-x" title="No results" action="Browse all tutorials" :href="url('/tutorials')">
                    Try fewer or different words, or check the spelling.
                </x-site.empty>
                @if ($popular->isNotEmpty())
                    <h2 class="mt-10 mb-4 text-lg font-semibold text-foreground">Popular tutorials</h2>
                    <div class="rounded-xl border border-border bg-card px-5 sm:px-6">
                        @foreach ($popular->take(4) as $article)
                            <x-card.article-row :article="$article" />
                        @endforeach
                    </div>
                @endif
            @endif
        @endif
    </div>
</x-layouts.site>

{{-- Tutorial page (PUB-03) and blog post (PUB-08): tutorial_web_template.html layout. --}}
@php
    $tz = config('ctp.display_timezone');
@endphp
<x-layouts.tutorial :meta="$meta">
    @if ($preview)
        <x-slot:banner><x-article.preview-banner :article="$article" /></x-slot:banner>
    @endif

    <x-slot:left>
        <x-site.category-sidebar :categories="$categories" />
    </x-slot:left>

    <x-article.mobile-bar :toc="$article->toc ?? []" />

    <article class="rounded-2xl border border-border bg-card p-6 shadow-sm transition-colors duration-300 sm:p-8" data-article @unless ($preview) data-view-beacon="{{ route('api.articles.view', $article) }}" @endunless>
        <header>
            {{-- Long titles make the breadcrumb noisy on phones; the category link covers it there. --}}
            <x-site.breadcrumbs :items="$meta->breadcrumbs" class="mb-5 hidden sm:block [&_li:last-child]:max-w-[24ch] [&_li:last-child_span]:truncate" />
            @if ($article->type === \App\Enums\ArticleType::Blog)
                <a href="{{ url('/blog') }}" class="text-sm font-semibold tracking-wide text-primary uppercase hover:underline">Blog</a>
            @elseif ($article->category)
                <a href="{{ url($article->category->publicPath()) }}" class="text-sm font-semibold tracking-wide text-primary uppercase hover:underline">{{ $article->category->name }}</a>
            @endif
            <h1 class="mt-2 mb-4 text-3xl font-bold tracking-tight text-balance text-foreground sm:text-4xl">{{ $article->title }}</h1>
            <x-article.meta :article="$article" />
        </header>

        @if ($article->excerpt)
            <p class="mt-6 text-lg leading-relaxed text-muted-foreground">{{ $article->excerpt }}</p>
        @endif

        <x-article.series-nav :article="$article" :parts="$seriesParts" />
        <x-article.intro :article="$article" />

        @if ($media = $article->featuredMedia)
            <figure class="not-prose mt-8">
                <img src="{{ $media->url($media->isRaster() ? '1280' : null) }}" @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="(min-width: 1024px) 760px, 100vw" @endif
                    @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
                    alt="{{ $media->alt }}" fetchpriority="high" class="w-full rounded-xl border border-border" data-zoomable>
            </figure>
        @endif

        <div class="prose-ctp mt-8" data-article-body>
            {!! $article->body_rendered !!}
        </div>

        <x-article.kudos :article="$article" :preview="$preview" />
        @unless ($preview)
            {{-- Below xl the right column is hidden, so the AI tools sit under the article. --}}
            <x-article.ai-tools :article="$article" compact class="not-prose mt-6 xl:hidden" />
        @endunless
        <x-article.footer :article="$article" :previous="$previous" :next="$next" />

        @unless ($preview)
            <x-comments.section :article="$article" :comments="$comments" />
        @endunless
    </article>

    <x-article.related :articles="$related" :title="$article->type === \App\Enums\ArticleType::Blog ? 'More from the blog' : 'Related tutorials'" />

    @if ($article->toc || $article->technologies->isNotEmpty())
        <x-slot:right>
            <x-article.tech-stack :article="$article" />
            @if ($article->toc)
                <x-site.toc :items="$article->toc" />
            @endif
            <div class="mt-8 space-y-3 border-t border-border pt-6 text-sm">
                @if ($updated = $article->lastUpdated() ?? $article->published_at)
                    <p class="flex items-center gap-2 text-muted-foreground">
                        <x-lucide-history class="size-4 shrink-0" />
                        <span>{{ $article->lastUpdated() ? 'Updated' : 'Published' }} <time datetime="{{ $updated->toAtomString() }}">{{ $updated->timezone($tz)->format('M j, Y') }}</time></span>
                    </p>
                @endif
                <a href="#main" class="flex items-center gap-2 text-muted-foreground hover:text-primary"><x-lucide-arrow-up-to-line class="size-4" /> Back to top</a>
            </div>
            @unless ($preview)
                <div class="mt-6 border-t border-border pt-6">
                    <p class="mb-2 text-xs font-semibold tracking-wider text-foreground uppercase">Use with AI</p>
                    <x-article.ai-tools :article="$article" />
                </div>
            @endunless
        </x-slot:right>
    @endif
</x-layouts.tutorial>

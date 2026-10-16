{{-- Tutorial page (PUB-03): tutorial_web_template.html layout with real content. Polished in Phase 2 (T2.03). --}}
<x-layouts.tutorial :title="$article->seo?->title ?: $article->title"
    :description="$article->seo?->description ?: $article->excerpt"
    :robots="$preview ? 'noindex, nofollow' : $article->seo?->robots">
    @if ($preview)
        <x-slot:banner><x-article.preview-banner :article="$article" /></x-slot:banner>
    @endif

    @if ($categories)
        <x-slot:left>
            <x-site.category-sidebar :categories="$categories" />
        </x-slot:left>
    @endif

    <article class="rounded-2xl border border-border bg-card p-6 shadow-sm transition-colors duration-300 sm:p-8">
        <header>
            @if ($article->category)
                <a href="{{ url($article->category->publicPath()) }}" class="text-sm font-semibold tracking-wide text-primary uppercase hover:underline">{{ $article->category->name }}</a>
            @endif
            <h1 class="mt-2 mb-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">{{ $article->title }}</h1>
            <x-article.meta :article="$article" />
        </header>

        @if ($article->excerpt)
            <p class="mt-6 text-lg leading-relaxed text-muted-foreground">{{ $article->excerpt }}</p>
        @endif

        <x-article.intro :article="$article" />

        @if ($media = $article->featuredMedia)
            <figure class="not-prose mt-8">
                <img src="{{ $media->url($media->isRaster() ? '1280' : null) }}" @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="(min-width: 1024px) 760px, 100vw" @endif
                    @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
                    alt="{{ $media->alt }}" fetchpriority="high" class="w-full rounded-xl border border-border">
            </figure>
        @endif

        <div class="prose-ctp mt-8">
            {!! $article->body_rendered !!}
        </div>

        <x-article.footer :article="$article" :previous="$previous" :next="$next" />
    </article>

    @if ($article->toc)
        <x-slot:right>
            <x-site.toc :items="$article->toc" />
        </x-slot:right>
    @endif
</x-layouts.tutorial>

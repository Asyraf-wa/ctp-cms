{{-- Blog post (PUB-08): single column, wider type. Polished in Phase 2 (T2.09). --}}
<x-layouts.site :title="$article->seo?->title ?: $article->title"
    :description="$article->seo?->description ?: $article->excerpt"
    :robots="$preview ? 'noindex, nofollow' : $article->seo?->robots">
    @if ($preview)
        <x-slot:banner><x-article.preview-banner :article="$article" /></x-slot:banner>
    @endif

    <article class="mx-auto max-w-3xl px-4 pt-10 pb-12 sm:px-6">
        <header class="mb-8">
            <a href="{{ url('/blog') }}" class="text-sm font-semibold tracking-wide text-primary uppercase hover:underline">Blog</a>
            <h1 class="mt-2 mb-4 text-4xl font-bold tracking-tight text-foreground sm:text-5xl">{{ $article->title }}</h1>
            <x-article.meta :article="$article" />
        </header>
        @if ($media = $article->featuredMedia)
            <img src="{{ $media->url($media->isRaster() ? '1280' : null) }}" @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="(min-width: 768px) 768px, 100vw" @endif
                @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
                alt="{{ $media->alt }}" fetchpriority="high" class="mb-8 w-full rounded-2xl border border-border">
        @endif
        <div class="prose-ctp prose-lg">{!! $article->body_rendered !!}</div>
        <x-article.footer :article="$article" :previous="$previous" :next="$next" />
    </article>
</x-layouts.site>

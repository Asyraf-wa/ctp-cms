{{-- Blog post (PUB-08, DESIGN §6): single column, larger type, no sidebars, optional cover hero. --}}
<x-layouts.site :meta="$meta">
    @if ($preview)
        <x-slot:banner><x-article.preview-banner :article="$article" /></x-slot:banner>
    @endif

    <div class="pointer-events-none fixed inset-x-0 top-0 z-[70] h-0.5" aria-hidden="true">
        <div class="h-full origin-left scale-x-0 bg-primary" data-reading-progress></div>
    </div>

    <article class="mx-auto max-w-3xl px-4 pt-10 pb-4 sm:px-6" data-article @unless ($preview) data-view-beacon="{{ route('api.articles.view', $article) }}" @endunless>
        <header class="mb-10 text-center">
            <x-site.breadcrumbs :items="$meta->breadcrumbs" class="mb-6 hidden justify-center sm:flex [&_li:last-child]:max-w-[28ch] [&_li:last-child_span]:truncate" />
            <a href="{{ url('/blog') }}" class="text-sm font-semibold tracking-wide text-primary uppercase hover:underline">Blog</a>
            <h1 class="mt-3 mb-6 text-4xl font-bold tracking-tight text-balance text-foreground sm:text-5xl">{{ $article->title }}</h1>
            @if ($article->excerpt)
                <p class="mx-auto mb-6 max-w-2xl text-xl leading-relaxed text-pretty text-muted-foreground">{{ $article->excerpt }}</p>
            @endif
            <div class="flex justify-center"><x-article.meta :article="$article" /></div>
        </header>

        @if ($media = $article->featuredMedia)
            <img src="{{ $media->url($media->isRaster() ? '1280' : null) }}" @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="(min-width: 768px) 768px, 100vw" @endif
                @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
                alt="{{ $media->alt }}" fetchpriority="high" class="mb-10 w-full rounded-2xl border border-border shadow-sm" data-zoomable>
        @endif

        <x-article.intro :article="$article" />

        <div class="prose-ctp prose-lg mt-8" data-article-body>{!! $article->body_rendered !!}</div>
        <x-article.kudos :article="$article" :preview="$preview" />
        @unless ($preview)
            <x-article.ai-tools :article="$article" compact class="not-prose mt-6" />
        @endunless
        <x-article.footer :article="$article" :previous="$previous" :next="$next" />

        @unless ($preview)
            <x-comments.section :article="$article" :comments="$comments" />
        @endunless
    </article>

    <div class="mx-auto max-w-5xl px-4 pb-12 sm:px-6">
        <x-article.related :articles="$related" title="More from the blog" />
    </div>
</x-layouts.site>

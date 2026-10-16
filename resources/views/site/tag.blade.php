{{-- Tag archive (PUB-07): tutorials and blog posts with this tag. --}}
<x-layouts.site :meta="$meta">
    <div class="mx-auto max-w-4xl px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header :title="'#'.$tag->name" eyebrow="Tag" :breadcrumbs="$meta->breadcrumbs" :description="$tag->description">
            <p class="mt-2 text-sm text-muted-foreground">{{ number_format($articles->total()) }} {{ Str::plural('article', $articles->total()) }}</p>
        </x-site.page-header>

        @if ($articles->isNotEmpty())
            <div class="rounded-xl border border-border bg-card px-5 sm:px-6">
                @foreach ($articles as $article)
                    <x-card.article-row :article="$article" heading="h2" />
                @endforeach
            </div>
            {{ $articles->links('site.partials.pagination') }}
        @else
            <x-site.empty icon="tag" title="No articles with this tag yet" action="Browse all tutorials" :href="url('/tutorials')" />
        @endif
    </div>
</x-layouts.site>

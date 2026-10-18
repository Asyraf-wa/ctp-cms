{{-- Author page (AUTHOR-03): profile and articles. --}}
@php($socials = collect($author->socials ?? [])->filter(fn ($url) => filled($url)))
<x-layouts.site :meta="$meta">
    <div class="mx-auto max-w-5xl px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.breadcrumbs :items="$meta->breadcrumbs" class="mb-6" />

        <header class="flex flex-col gap-6 rounded-2xl border border-border bg-card p-6 shadow-sm sm:flex-row sm:items-center sm:p-8">
            @if ($author->avatarUrl())
                <img src="{{ $author->avatarUrl() }}" alt="" width="112" height="112" class="size-28 shrink-0 rounded-full border border-border object-cover" referrerpolicy="no-referrer">
            @else
                <span class="flex size-28 shrink-0 items-center justify-center rounded-full bg-brand-soft text-3xl font-bold text-primary">{{ Str::of($author->authorName())->explode(' ')->map(fn ($w) => Str::substr($w, 0, 1))->take(2)->implode('') }}</span>
            @endif
            <div class="min-w-0">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ $author->authorName() }}</h1>
                @if ($author->job_title)
                    <p class="mt-1 font-medium text-primary">{{ $author->job_title }}</p>
                @endif
                @if ($author->bio_short)
                    <p class="mt-3 text-lg leading-relaxed text-muted-foreground">{{ $author->bio_short }}</p>
                @endif
                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                    <span><strong class="text-foreground">{{ $stats['tutorials'] }}</strong> {{ Str::plural('tutorial', $stats['tutorials']) }}</span>
                    @if ($stats['posts'])
                        <span><strong class="text-foreground">{{ $stats['posts'] }}</strong> blog {{ Str::plural('post', $stats['posts']) }}</span>
                    @endif
                    @if ($author->website_url)
                        <a href="{{ $author->website_url }}" rel="me noopener" target="_blank" class="inline-flex items-center gap-1 font-medium text-primary hover:underline"><x-lucide-globe class="size-4" /> Website</a>
                    @endif
                    @foreach ($socials as $network => $url)
                        <a href="{{ $url }}" rel="me noopener" target="_blank" class="text-muted-foreground hover:text-foreground" title="{{ \App\Settings\SocialSettings::NETWORKS[$network] ?? ucfirst($network) }}">
                            <x-site.social-icon :network="$network" class="size-5" /><span class="sr-only">{{ \App\Settings\SocialSettings::NETWORKS[$network] ?? ucfirst($network) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </header>

        @if ($author->bio_long)
            <section class="prose-ctp mt-8 max-w-none rounded-2xl border border-border bg-card p-6 sm:p-8" aria-label="About">
                {!! \App\Support\Html::basic($author->bio_long) !!}
            </section>
        @endif

        <h2 class="mt-12 mb-6 text-2xl font-bold tracking-tight text-foreground">Latest from {{ Str::before($author->authorName(), ' ') }}</h2>
        @if ($articles->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <x-card.article :article="$article" />
                @endforeach
            </div>
            {{ $articles->links('site.partials.pagination') }}
        @else
            <x-site.empty icon="pen-line" title="No articles yet" />
        @endif
    </div>
</x-layouts.site>

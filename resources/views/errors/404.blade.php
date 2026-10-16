{{-- PUB-12: friendly 404 with search and popular tutorials. Renamed URLs never get here (redirect lookup). --}}
@php
    try {
        $popular = \App\Models\Article::published()->tutorials()->forCards()->orderByDesc('views')->latest('published_at')->limit(4)->get();
    } catch (\Throwable) {
        $popular = collect();
    }
@endphp
<x-layouts.site title="Page not found" robots="noindex, follow">
    <section class="hero-mesh border-b border-border">
        <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 sm:py-20">
            <p class="font-mono text-sm font-semibold text-primary">404</p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight text-foreground sm:text-5xl">This page went missing</h1>
            <p class="mx-auto mt-4 max-w-xl text-lg text-muted-foreground">The link may be old or mistyped. Search for what you were after, or start from one of the pages below.</p>
            <x-site.search-trigger class="mx-auto mt-8 max-w-xl" placeholder="Search tutorials" />
            <div class="mt-6 flex flex-wrap justify-center gap-3 text-sm font-medium">
                <a href="{{ url('/') }}" class="rounded-full border border-border bg-card px-4 py-2 hover:border-primary hover:text-primary">Home</a>
                <a href="{{ url('/tutorials') }}" class="rounded-full border border-border bg-card px-4 py-2 hover:border-primary hover:text-primary">All tutorials</a>
                <a href="{{ url('/blog') }}" class="rounded-full border border-border bg-card px-4 py-2 hover:border-primary hover:text-primary">Blog</a>
            </div>
        </div>
    </section>

    @if ($popular->isNotEmpty())
        <div class="mx-auto max-w-[1440px] px-4 pb-8 sm:px-6 lg:px-8">
            <x-site.section title="Popular tutorials" class="!mt-12">
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($popular as $article)
                        <x-card.article :article="$article" />
                    @endforeach
                </div>
            </x-site.section>
        </div>
    @endif
</x-layouts.site>

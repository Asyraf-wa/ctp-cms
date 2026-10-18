{{-- Series page (TAX-03): every part in order. --}}
<x-layouts.site :meta="$meta">
    <div class="mx-auto max-w-4xl px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header :title="$series->title" eyebrow="Series" icon="list-ordered"
            :breadcrumbs="$meta->breadcrumbs" :description="$series->description">
            <p class="mt-2 text-sm text-muted-foreground">{{ $articles->count() }} {{ Str::plural('part', $articles->count()) }} · about {{ $articles->sum(fn ($a) => $a->readingMinutes()) }} min in total</p>
        </x-site.page-header>

        <ol class="relative space-y-4 border-l-2 border-border pl-8">
            @foreach ($articles as $article)
                <li class="relative">
                    <span class="absolute top-5 -left-[2.6rem] flex size-8 items-center justify-center rounded-full border-2 border-primary bg-card text-sm font-bold text-primary">{{ $loop->iteration }}</span>
                    <div class="group relative rounded-xl border border-border bg-card p-5 shadow-xs transition hover:border-primary/40 hover:shadow-md">
                        <p class="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Part {{ $loop->iteration }}</p>
                        <h2 class="font-semibold text-foreground">
                            <a href="{{ url($article->publicPath()) }}" class="after:absolute after:inset-0 group-hover:text-primary">{{ $article->title }}</a>
                        </h2>
                        @if ($article->excerpt)
                            <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">{{ $article->excerpt }}</p>
                        @endif
                        <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">{{ $article->readingMinutes() }} min read <x-site.difficulty :level="$article->difficulty" /></p>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="mt-10 text-center">
            <a href="{{ url($articles->first()->publicPath()) }}" class="inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 font-semibold text-primary-foreground hover:bg-primary-hover">
                Start with part 1 <x-lucide-arrow-right class="size-4" />
            </a>
        </div>
    </div>
</x-layouts.site>

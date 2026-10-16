{{-- Home (PUB-01, DESIGN §6). --}}
@inject('general', 'App\Settings\GeneralSettings')
<x-layouts.site>
    <section class="hero-mesh border-b border-border">
        <div class="mx-auto max-w-[1440px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-card/70 px-3 py-1 text-sm font-medium text-brand-soft-foreground backdrop-blur">
                    <x-lucide-sparkles class="size-4" /> {{ number_format($tutorialCount) }} hands-on tutorials
                </p>
                <h1 class="text-4xl font-bold tracking-tight text-balance text-foreground sm:text-5xl">{{ $general->slogan ?: $general->site_name }}</h1>
                @if ($general->description)
                    <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-pretty text-muted-foreground">{{ $general->description }}</p>
                @endif
                <x-site.search-trigger class="mx-auto mt-8 max-w-2xl" />
                @if ($categories->isNotEmpty())
                    <div class="mt-6 flex flex-wrap justify-center gap-2 text-sm">
                        <span class="text-muted-foreground">Popular:</span>
                        @foreach ($categories->sortByDesc('tutorial_count')->take(4) as $category)
                            <a href="{{ url($category->publicPath()) }}" class="rounded-full border border-border bg-card/70 px-3 py-0.5 font-medium text-foreground hover:border-primary hover:text-primary">{{ $category->name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-[1440px] px-4 pb-8 sm:px-6 lg:px-8">
        @if ($featured->isNotEmpty())
            <x-site.section title="Featured tutorials" class="!mt-12">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $article)
                        <x-card.article :article="$article" :eager="$loop->first" />
                    @endforeach
                </div>
            </x-site.section>
        @endif

        <x-site.section title="Latest tutorials" :href="url('/tutorials')" link-label="All tutorials" @class(['!mt-12' => $featured->isEmpty()])>
            @if ($latest->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latest as $article)
                        <x-card.article :article="$article" :eager="$featured->isEmpty() && $loop->first" />
                    @endforeach
                </div>
            @else
                <x-site.empty icon="book-open" title="No tutorials yet">The first tutorials are on their way.</x-site.empty>
            @endif
        </x-site.section>

        @if ($categories->isNotEmpty())
            <x-site.section title="Explore by category">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($categories as $category)
                        <a href="{{ url($category->publicPath()) }}" class="group flex items-start gap-4 rounded-xl border border-border bg-card p-5 shadow-xs transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-primary">
                                <x-dynamic-component :component="'lucide-'.($category->icon ?: 'folder')" class="size-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-foreground group-hover:text-primary">{{ $category->name }}</span>
                                <span class="block text-sm text-muted-foreground">{{ $category->tutorial_count }} {{ Str::plural('tutorial', $category->tutorial_count) }}</span>
                                @if ($category->description)
                                    <span class="mt-1 line-clamp-2 block text-sm text-muted-foreground">{{ $category->description }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-site.section>
        @endif

        @if ($series->isNotEmpty())
            <x-site.section title="Learning paths" description="Step-by-step series, in reading order." :href="url('/series')" link-label="All series">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($series as $item)
                        <x-card.series :series="$item" />
                    @endforeach
                </div>
            </x-site.section>
        @endif

        @if ($posts->isNotEmpty())
            <x-site.section title="From the blog" :href="url('/blog')" link-label="All posts">
                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-card.article :article="$post" />
                    @endforeach
                </div>
            </x-site.section>
        @endif

        @if ($projects->isNotEmpty())
            <x-site.section title="Projects" description="Things built along the way." :href="url('/projects')" link-label="All projects">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-card.project :project="$project" />
                    @endforeach
                </div>
            </x-site.section>
        @endif
    </div>
</x-layouts.site>

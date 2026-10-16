{{--
    Home (PUB-01, D20): the tutorial layout with no images. Categories left, the 10 latest
    tutorials with category tabs in the centre, discovery panels right.
--}}
@inject('general', 'App\Settings\GeneralSettings')
@php($tz = config('ctp.display_timezone'))
<x-layouts.tutorial :title="null" :robots="$active ? 'noindex, follow' : null">
    <x-slot:left>
        <x-site.category-sidebar :categories="$sidebar" />
    </x-slot:left>

    <x-article.mobile-bar :categories="$sidebar" />

    {{-- Slim intro: one h1 and the search, for first-time visitors and search engines. --}}
    <section class="mb-6 rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ $general->slogan ?: $general->site_name }}</h1>
                <p class="mt-1.5 text-muted-foreground">{{ number_format($tutorialCount) }} practical, tested tutorials on CakePHP, Laravel, Joomla! and web tools.</p>
            </div>
            <x-site.search-trigger class="w-full shrink-0 lg:w-80 [&_button]:hidden [&_input]:py-3 [&_input]:pr-4 [&_input]:text-sm" placeholder="Search tutorials…" />
        </div>
    </section>

    <section class="rounded-2xl border border-border bg-card shadow-sm" aria-labelledby="latest-heading">
        <div class="flex flex-col gap-4 border-b border-border px-6 pt-6 sm:px-8">
            <div class="flex items-center justify-between gap-4">
                <h2 id="latest-heading" class="text-lg font-bold tracking-tight text-foreground">Latest {{ $active ? $active->name.' ' : '' }}tutorials</h2>
                <a href="{{ $active ? url($active->publicPath()) : url('/tutorials') }}" class="hidden shrink-0 items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover sm:inline-flex">
                    View all <x-lucide-arrow-right class="size-4" />
                </a>
            </div>
            {{-- Category tabs: plain links (?category=), so they work without JS and can be shared. --}}
            <nav class="no-scrollbar -mb-px flex gap-1 overflow-x-auto" aria-label="Filter by category">
                @foreach ([null, ...$categories] as $tab)
                    @php($isActive = $tab?->id === $active?->id)
                    <a href="{{ $tab ? url('/').'?category='.$tab->slug : url('/') }}" @if ($isActive) aria-current="page" @endif
                        @class([
                            'tech-hover inline-flex shrink-0 items-center gap-1.5 border-b-2 px-3 pb-3 text-sm font-medium whitespace-nowrap transition-colors',
                            'border-primary text-primary' => $isActive,
                            'border-transparent text-muted-foreground hover:border-border hover:text-foreground' => ! $isActive,
                        ])>
                        @if ($tab?->technology)
                            <x-site.tech-icon :technology="$tab->technology" size="size-3.5" />
                        @endif
                        {{ $tab?->name ?? 'All' }}
                        @if ($tab)
                            <span class="ml-1 text-xs tabular-nums opacity-70">{{ $tab->tutorial_count }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        @if ($latest->isNotEmpty())
            <ol>
                @foreach ($latest as $article)
                    <li class="group relative flex gap-4 border-b border-border px-6 py-4 transition-colors last:border-b-0 hover:bg-surface-2 sm:px-8">
                        <x-site.article-logo :article="$article" class="mt-0.5 group-hover:bg-card" />
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex flex-wrap items-center gap-2 text-xs">
                                @if ($article->is_pinned)
                                    <x-lucide-pin class="size-3.5 text-primary" aria-label="Pinned" />
                                @endif
                                @if ($article->category)
                                    <span class="font-semibold tracking-wide text-primary uppercase">{{ $article->category->name }}</span>
                                @endif
                                <x-site.difficulty :level="$article->difficulty" />
                            </div>
                            <h3 class="font-semibold text-foreground">
                                <a href="{{ url($article->publicPath()) }}" class="after:absolute after:inset-0 group-hover:text-primary focus:outline-none focus-visible:underline">{{ $article->title }}</a>
                            </h3>
                            @if ($article->excerpt)
                                <p class="mt-1 line-clamp-1 text-sm text-muted-foreground">{{ $article->excerpt }}</p>
                            @endif
                            <p class="mt-1.5 flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground">
                                <time datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->timezone($tz)->format('M j, Y') }}</time>
                                <span aria-hidden="true">·</span> {{ $article->readingMinutes() }} min read
                                @if ($article->technologies->count() > 1)
                                    <span aria-hidden="true">·</span>
                                    <span class="sr-only">Uses</span>{{ $article->technologies->pluck('name')->join(', ') }}
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
            <div class="border-t border-border px-6 py-4 sm:px-8">
                <a href="{{ $active ? url($active->publicPath()) : url('/tutorials') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover">
                    {{ $active ? 'All '.$active->name.' tutorials' : 'View all tutorials' }} <x-lucide-arrow-right class="size-4" />
                </a>
            </div>
        @else
            <div class="p-6 sm:p-8">
                <x-site.empty icon="book-open" title="No tutorials yet">New tutorials will show up here.</x-site.empty>
            </div>
        @endif
    </section>

    {{-- Below xl the right column moves under the list. --}}
    <x-home.side-panels :levels="$levels" :tags="$tags" :popular="$popular" :series="$series" id-prefix="m"
        class="mt-8 grid gap-8 rounded-2xl border border-border bg-card p-6 shadow-sm sm:grid-cols-2 sm:p-8 xl:hidden" />

    <x-slot:right>
        <x-home.side-panels :levels="$levels" :tags="$tags" :popular="$popular" :series="$series" class="space-y-8" />
    </x-slot:right>
</x-layouts.tutorial>

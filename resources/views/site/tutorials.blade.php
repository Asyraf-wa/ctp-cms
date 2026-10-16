{{-- Tutorials index (PUB-02). Filters are plain links, so they work without JS and can be shared. --}}
@php
    $filterUrl = fn (array $change) => request()->fullUrlWithQuery([...$change, 'page' => null]);
    $title = $category?->name ?? ($tag ? '#'.$tag->name : 'All tutorials');
@endphp
<x-layouts.site :title="$filtered ? $title.' tutorials' : 'All tutorials'"
    description="Step-by-step tutorials on CakePHP, Laravel, Joomla and web development tools."
    :robots="$filtered || $articles->currentPage() > 1 ? 'noindex, follow' : null">
    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header title="All tutorials" :breadcrumbs="[['Tutorials', null]]"
            description="Practical, tested walkthroughs. Filter by topic or level, or sort by what readers found most useful." />

        <div class="flex flex-col gap-8 lg:flex-row">
            <aside class="w-full shrink-0 lg:w-64" aria-label="Filters">
                <details class="group rounded-xl border border-border bg-card p-4 lg:sticky lg:top-28 lg:border-0 lg:bg-transparent lg:p-0" {{ $filtered ? 'open' : '' }}
                    x-data x-init="const lg = matchMedia('(min-width: 1024px)'); const sync = () => lg.matches && ($el.open = true); sync(); lg.addEventListener('change', sync)">
                    <summary class="flex cursor-pointer items-center justify-between font-semibold text-foreground lg:hidden">
                        <span class="flex items-center gap-2"><x-lucide-sliders-horizontal class="size-4" /> Filters</span>
                        <x-lucide-chevron-down class="size-4 transition-transform group-open:rotate-180" />
                    </summary>

                    <div class="mt-4 space-y-8 lg:mt-0">
                        <div>
                            <h2 class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Category</h2>
                            <ul class="space-y-1 text-sm">
                                <li>
                                    <a href="{{ $filterUrl(['category' => null]) }}" @class(['flex justify-between rounded-sm px-2 py-1.5', 'bg-brand-soft font-semibold text-brand-soft-foreground' => ! $category, 'text-muted-foreground hover:bg-accent hover:text-foreground' => $category])>
                                        <span>All categories</span>
                                    </a>
                                </li>
                                @foreach ($categories as $root)
                                    @php($total = $counts->only([$root->id, ...$root->children->pluck('id')])->sum())
                                    @continue($total === 0)
                                    <li>
                                        <a href="{{ $filterUrl(['category' => $root->slug]) }}" @class(['flex justify-between rounded-sm px-2 py-1.5', 'bg-brand-soft font-semibold text-brand-soft-foreground' => $category?->id === $root->id, 'text-muted-foreground hover:bg-accent hover:text-foreground' => $category?->id !== $root->id])>
                                            <span>{{ $root->name }}</span><span class="text-xs tabular-nums">{{ $total }}</span>
                                        </a>
                                        @foreach ($root->children as $child)
                                            @continue(! $counts->get($child->id))
                                            <a href="{{ $filterUrl(['category' => $child->slug]) }}" @class(['ml-3 flex justify-between rounded-sm px-2 py-1 text-[0.8125rem]', 'bg-brand-soft font-semibold text-brand-soft-foreground' => $category?->id === $child->id, 'text-muted-foreground hover:bg-accent hover:text-foreground' => $category?->id !== $child->id])>
                                                <span>{{ $child->name }}</span><span class="text-xs tabular-nums">{{ $counts->get($child->id) }}</span>
                                            </a>
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h2 class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Level</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach (\App\Enums\Difficulty::cases() as $level)
                                    <a href="{{ $filterUrl(['difficulty' => $difficulty === $level ? null : $level->value]) }}" @if ($difficulty === $level) aria-current="true" @endif
                                        @class(['rounded-full border px-3 py-1 text-sm font-medium', 'border-primary bg-primary text-primary-foreground' => $difficulty === $level, 'border-border text-muted-foreground hover:border-primary hover:text-primary' => $difficulty !== $level])>{{ $level->getLabel() }}</a>
                                @endforeach
                            </div>
                        </div>

                        @if ($popularTags->isNotEmpty())
                            <div>
                                <h2 class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Popular tags</h2>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($popularTags as $item)
                                        <a href="{{ $filterUrl(['tag' => $tag?->id === $item->id ? null : $item->slug]) }}"
                                            @class(['rounded-sm border px-2 py-0.5 text-xs font-medium', 'border-primary bg-brand-soft text-brand-soft-foreground' => $tag?->id === $item->id, 'border-border text-muted-foreground hover:border-primary hover:text-primary' => $tag?->id !== $item->id])>#{{ $item->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </details>
            </aside>

            <div class="min-w-0 flex-1">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-muted-foreground" aria-live="polite">{{ number_format($articles->total()) }} {{ Str::plural('tutorial', $articles->total()) }}</span>
                        @foreach (array_filter(['category' => $category?->name, 'difficulty' => $difficulty?->getLabel(), 'tag' => $tag ? '#'.$tag->name : null]) as $key => $label)
                            <a href="{{ $filterUrl([$key => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-brand-soft px-2.5 py-0.5 font-medium text-brand-soft-foreground hover:bg-primary hover:text-primary-foreground">
                                {{ $label }} <x-lucide-x class="size-3.5" /><span class="sr-only">Remove filter</span>
                            </a>
                        @endforeach
                        @if ($filtered)
                            <a href="{{ url('/tutorials') }}" class="text-muted-foreground underline-offset-2 hover:text-primary hover:underline">Clear all</a>
                        @endif
                    </div>
                    <nav class="inline-flex rounded-md border border-border bg-card p-0.5 text-sm" aria-label="Sort">
                        @foreach (['latest' => 'Latest', 'popular' => 'Popular'] as $value => $label)
                            <a href="{{ $filterUrl(['sort' => $value === 'latest' ? null : $value]) }}" @if ($sort === $value) aria-current="true" @endif
                                @class(['rounded-sm px-3 py-1.5 font-medium', 'bg-primary text-primary-foreground' => $sort === $value, 'text-muted-foreground hover:text-foreground' => $sort !== $value])>{{ $label }}</a>
                        @endforeach
                    </nav>
                </div>

                @if ($articles->isNotEmpty())
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($articles as $article)
                            <x-card.article :article="$article" :eager="$loop->first" heading="h2" />
                        @endforeach
                    </div>
                    {{ $articles->links('site.partials.pagination') }}
                @else
                    <x-site.empty icon="search-x" title="No tutorials match these filters" action="Clear filters" :href="url('/tutorials')">
                        Try another level or category.
                    </x-site.empty>
                @endif
            </div>
        </div>
    </div>
</x-layouts.site>

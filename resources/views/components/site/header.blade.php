@php
    // Temporary until menu management (MENU, T1.07) supplies the `header` menu.
    $nav = [
        ['label' => 'Tutorials', 'url' => url('/tutorials')],
        ['label' => 'Series', 'url' => url('/series')],
        ['label' => 'Blog', 'url' => url('/blog')],
        ['label' => 'Projects', 'url' => url('/projects')],
        ['label' => 'About', 'url' => url('/about')],
    ];
@endphp

<header x-data="{ scrolled: false, open: false }"
    @scroll.window.throttle.50ms="scrolled = window.scrollY > 24"
    @keydown.escape.window="open = false"
    class="sticky top-0 z-40 border-b transition-[background-color,box-shadow,border-color] duration-300"
    :class="scrolled ? 'border-border bg-card/85 shadow-sm backdrop-blur-md' : 'border-transparent bg-background'">
    <div class="mx-auto flex max-w-[1440px] items-center gap-4 px-4 transition-[height] duration-300 sm:px-6 lg:px-8"
        :class="scrolled ? 'h-14' : 'h-18'">

        <a href="{{ url('/') }}" class="flex items-center gap-2.5 rounded-md" aria-label="{{ config('app.name') }} home">
            <x-brand.mark class="size-8" />
            <span class="font-bold tracking-tight text-foreground transition-[font-size] duration-300"
                :class="scrolled ? 'text-lg' : 'text-xl'">{{ config('app.name') }}</span>
        </a>

        <nav class="ml-6 hidden items-center gap-1 md:flex" aria-label="Main">
            @foreach ($nav as $item)
                <a href="{{ $item['url'] }}"
                    @class([
                        'rounded-sm px-3 py-2 text-sm font-medium transition-colors',
                        'text-primary bg-brand-soft' => request()->url() === $item['url'],
                        'text-muted-foreground hover:bg-accent hover:text-foreground' => request()->url() !== $item['url'],
                    ])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-2">
            {{-- Opens the Ctrl+K search palette (PUB-06, Phase 2). --}}
            <button type="button" @click="$dispatch('open-search')"
                class="hidden h-9 w-56 items-center gap-2 rounded-md border border-border bg-card px-3 text-sm text-muted-foreground transition-colors hover:border-line-strong sm:flex">
                <x-lucide-search class="size-4" />
                <span>Search tutorials…</span>
                <kbd class="ml-auto rounded-xs border border-border bg-surface-2 px-1.5 font-sans text-[11px] font-semibold">Ctrl K</kbd>
            </button>
            <button type="button" @click="$dispatch('open-search')" aria-label="Search"
                class="inline-flex size-9 items-center justify-center rounded-md border border-border text-muted-foreground hover:bg-accent sm:hidden">
                <x-lucide-search class="size-[18px]" />
            </button>

            <x-site.theme-toggle />

            <button type="button" @click="open = true" aria-label="Open menu" :aria-expanded="open"
                class="inline-flex size-9 items-center justify-center rounded-md border border-border text-muted-foreground hover:bg-accent md:hidden">
                <x-lucide-menu class="size-[18px]" />
            </button>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="open = false"></div>
        <div x-show="open" x-trap.noscroll="open"
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 flex w-72 max-w-[85vw] flex-col border-l border-border bg-card shadow-lg">
            <div class="flex h-16 items-center justify-between border-b border-border px-4">
                <span class="font-semibold">Menu</span>
                <button type="button" @click="open = false" aria-label="Close menu"
                    class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground hover:bg-accent">
                    <x-lucide-x class="size-5" />
                </button>
            </div>
            <nav class="flex flex-col gap-1 p-3" aria-label="Mobile">
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}" class="rounded-sm px-3 py-2.5 font-medium text-foreground hover:bg-accent">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </div>
    </div>
</header>

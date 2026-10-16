@php
    // Temporary until menu management (MENU, T1.07) supplies the `header` menu.
    $nav = [
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'All Tutorials', 'url' => url('/tutorials')],
        ['label' => 'Blog', 'url' => url('/blog')],
        ['label' => 'Projects', 'url' => url('/projects')],
    ];
@endphp

{{-- Layout follows tutorial_web_template.html: shrinks on scroll (py-6 → py-3, logo 3xl → xl). --}}
<header x-data="{ scrolled: false, open: false }"
    x-init="scrolled = window.scrollY > 50"
    @scroll.window.throttle.50ms="scrolled = window.scrollY > 50"
    @keydown.escape.window="open = false"
    class="sticky top-0 z-40 w-full border-b border-border transition-all duration-300"
    :class="scrolled ? 'py-3 shadow-sm bg-card/95 backdrop-blur' : 'py-6 bg-card'">
    <div class="mx-auto flex h-full max-w-[1440px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        <a href="{{ url('/') }}" class="flex items-center gap-2" aria-label="{{ config('app.name') }} home">
            <x-brand.mark class="size-8" />
            <span class="font-bold tracking-tight text-foreground transition-all duration-300"
                :class="scrolled ? 'text-xl' : 'text-xl sm:text-3xl'">{{ config('app.name') }}</span>
        </a>

        <nav class="hidden gap-6 md:flex" aria-label="Main">
            @foreach ($nav as $item)
                <a href="{{ $item['url'] }}"
                    @class([
                        'text-sm font-medium transition-colors',
                        'text-primary' => request()->url() === $item['url'],
                        'text-muted-foreground hover:text-primary' => request()->url() !== $item['url'],
                    ])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2 sm:gap-4">
            {{-- Plain GET form, so search works without JS; Ctrl/⌘ K focuses it (PUB-06). --}}
            <form action="{{ url('/search') }}" method="get" role="search" class="relative hidden sm:block"
                x-data @keydown.window.prevent.ctrl.k="$refs.q.focus()" @keydown.window.prevent.meta.k="$refs.q.focus()">
                <label for="site-search" class="sr-only">Search tutorials</label>
                <input x-ref="q" id="site-search" name="q" type="search" placeholder="Search..." autocomplete="off"
                    class="w-56 rounded-full border border-transparent bg-muted py-2 pr-16 pl-4 text-sm text-foreground transition-all placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:outline-none dark:border-border">
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <span class="hidden rounded border border-border bg-card px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground lg:block">Ctrl K</span>
                </div>
            </form>

            <x-site.theme-toggle />

            <button type="button" x-data="{ full: false }" x-show="document.fullscreenEnabled"
                @fullscreenchange.document="full = !!document.fullscreenElement"
                @click="full ? document.exitFullscreen() : document.documentElement.requestFullscreen().catch(() => {})"
                :title="full ? 'Exit fullscreen' : 'Fullscreen'" :aria-label="full ? 'Exit fullscreen' : 'Fullscreen'"
                class="hidden rounded-lg p-2 text-muted-foreground transition-colors hover:bg-accent focus:ring-4 focus:ring-line-strong focus:outline-none sm:inline-flex">
                <x-lucide-maximize class="size-5" x-show="!full" />
                <x-lucide-minimize class="size-5" x-show="full" x-cloak />
            </button>

            <a href="{{ url('/feed') }}"
                class="hidden rounded-full bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-hover sm:inline-flex">Subscribe</a>

            <button type="button" @click="open = true" aria-label="Open menu" :aria-expanded="open"
                class="inline-flex rounded-lg p-2 text-muted-foreground hover:bg-accent md:hidden">
                <x-lucide-menu class="size-5" />
            </button>
        </div>
    </div>

    {{-- Mobile drawer (the template has no mobile nav; this adds one). --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="open = false"></div>
        <div x-show="open" x-trap.noscroll="open"
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 flex w-80 max-w-[85vw] flex-col overflow-y-auto border-l border-border bg-card shadow-lg">
            <div class="flex items-center justify-between border-b border-border px-4 py-4">
                <span class="font-semibold">Menu</span>
                <button type="button" @click="open = false" aria-label="Close menu" class="rounded-lg p-2 text-muted-foreground hover:bg-accent">
                    <x-lucide-x class="size-5" />
                </button>
            </div>
            <form action="{{ url('/search') }}" method="get" role="search" class="border-b border-border p-4">
                <label for="mobile-search" class="sr-only">Search tutorials</label>
                <input id="mobile-search" name="q" type="search" placeholder="Search..."
                    class="w-full rounded-full bg-muted px-4 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none">
            </form>
            <nav class="flex flex-col gap-1 p-3" aria-label="Mobile">
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}" class="rounded-md px-3 py-2.5 font-medium text-foreground hover:bg-accent">{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ url('/feed') }}" class="mt-2 rounded-full bg-primary px-4 py-2 text-center text-sm font-medium text-primary-foreground">Subscribe</a>
            </nav>
        </div>
    </div>
</header>

@inject('general', 'App\Settings\GeneralSettings')
@inject('ribbon', 'App\Settings\RibbonSettings')
@php($nav = \App\Models\Menu::tree('header'))
@php($sidebar = trim($__env->yieldPushContent('mobile-sidebar')))
{{-- With a page sidebar (categories) the menu button stays until lg, where the sidebar shows. --}}
@php($drawerHidden = $sidebar !== '' ? 'lg:hidden' : 'md:hidden')

{{-- Layout follows tutorial_web_template.html: shrinks on scroll (py-6 → py-3, logo 3xl → xl). The full-size state is in the static classes so nothing moves when Alpine boots. --}}
<header x-data="{ scrolled: false, open: false }"
    {{-- No throttle: Alpine's throttle drops the last event, so a quick flick to the top left the header small.
        Shrink past 50px, grow back under 10px, so it cannot flicker around one threshold. --}}
    x-init="scrolled = window.scrollY > 50"
    @scroll.window.passive="scrolled = scrolled ? window.scrollY > 10 : window.scrollY > 50"
    @keydown.escape.window="open = false"
    class="sticky top-0 z-40 w-full border-b border-border bg-card py-6 transition-all duration-300"
    :class="scrolled && 'py-3! bg-card/95! shadow-sm backdrop-blur'">
    <div @class(['mx-auto flex h-full max-w-[1440px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8', 'sm:pr-24 lg:pr-24' => $ribbon->isVisible()])>

        <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2 whitespace-nowrap" aria-label="{{ $general->site_name }} home">
            <x-brand.mark class="w-10 transition-all duration-300" ::class="scrolled && 'w-7!'" />
            <span class="font-brand text-xl font-bold text-foreground transition-all duration-300 lg:text-3xl"
                :class="scrolled && 'lg:text-xl!'">{{ $general->site_name }}</span>
        </a>

        <nav class="hidden gap-6 whitespace-nowrap md:flex" aria-label="Main">
            @foreach ($nav as $item)
                @php($active = \App\Support\Navigation::isActive($item['href']) || collect($item['children'])->contains(fn ($c) => \App\Support\Navigation::isActive($c['href'])))
                @if ($item['children'])
                    {{-- Dropdown (MENU-03) --}}
                    <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @keydown.escape="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open"
                            @class(['inline-flex items-center gap-1 py-1 text-sm font-medium transition-colors', 'text-primary' => $active, 'text-muted-foreground hover:text-primary' => ! $active])>
                            {{ $item['label'] }} <x-lucide-chevron-down class="size-3.5 transition-transform" ::class="open && 'rotate-180'" />
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute top-full left-1/2 z-50 -translate-x-1/2 pt-3">
                            <div class="flex min-w-52 flex-col rounded-xl border border-border bg-card p-1.5 shadow-lg">
                                @if ($item['href'])
                                    <x-site.nav-link :item="$item" :active="\App\Support\Navigation::isActive($item['href'])" class="w-full rounded-sm px-3 py-2 text-sm font-medium text-foreground hover:bg-accent" />
                                @endif
                                @foreach ($item['children'] as $child)
                                    <x-site.nav-link :item="$child" :active="\App\Support\Navigation::isActive($child['href'])"
                                        @class(['w-full rounded-sm px-3 py-2 text-sm hover:bg-accent', 'text-primary font-medium' => \App\Support\Navigation::isActive($child['href']), 'text-muted-foreground hover:text-foreground' => ! \App\Support\Navigation::isActive($child['href'])]) />
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <x-site.nav-link :item="$item" :active="$active"
                        @class(['py-1 text-sm font-medium transition-colors', 'text-primary' => $active, 'text-muted-foreground hover:text-primary' => ! $active]) />
                @endif
            @endforeach
        </nav>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            {{-- Plain GET form, so search works without JS; with JS it opens the Ctrl/⌘ K palette (PUB-06). --}}
            <form action="{{ url('/search') }}" method="get" role="search" aria-label="Site search" class="relative hidden xl:block" data-search-trigger>
                <label for="site-search" class="sr-only">Search tutorials</label>
                <input id="site-search" name="q" type="search" placeholder="Search..." autocomplete="off"
                    class="w-56 rounded-full border border-transparent bg-muted py-2 pr-16 pl-4 text-sm text-foreground transition-all placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:outline-none dark:border-border">
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <span class="hidden rounded border border-border bg-card px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground lg:block">Ctrl K</span>
                </div>
            </form>

            <a href="{{ url('/search') }}" aria-label="Search" @click.prevent="$dispatch('open-search')"
                class="hidden rounded-lg p-2 text-muted-foreground transition-colors hover:bg-accent sm:inline-flex xl:hidden">
                <x-lucide-search class="size-5" />
            </a>

            <x-site.theme-toggle />

            {{-- Focus mode (PUB-05) replaces the template's fullscreen button; only on pages with sidebars. --}}
            <button type="button" x-show="$store.focus.available" x-cloak @click="$store.focus.toggle()"
                aria-label="Focus mode"
                :aria-pressed="$store.focus.on ? 'true' : 'false'"
                :title="$store.focus.on ? 'Exit focus mode (Esc)' : 'Focus mode: hide the sidebars'"
                :aria-label="$store.focus.on ? 'Exit focus mode' : 'Focus mode'"
                class="hidden rounded-lg p-2 text-muted-foreground transition-colors hover:bg-accent focus:ring-4 focus:ring-line-strong focus:outline-none lg:inline-flex"
                :class="$store.focus.on && 'bg-brand-soft text-primary'">
                <x-lucide-scan-eye class="size-5" x-show="!$store.focus.on" />
                <x-lucide-minimize-2 class="size-5" x-show="$store.focus.on" x-cloak />
            </button>

            {{-- Fullscreen: hidden where the Fullscreen API is missing (iPhone Safari). --}}
            <button type="button" x-data="{ full: false, supported: document.fullscreenEnabled }" x-show="supported" x-cloak
                @fullscreenchange.document="full = !!document.fullscreenElement"
                @click="full ? document.exitFullscreen() : document.documentElement.requestFullscreen()"
                aria-label="Fullscreen"
                :aria-pressed="full ? 'true' : 'false'"
                :title="full ? 'Exit fullscreen' : 'Fullscreen'"
                :aria-label="full ? 'Exit fullscreen' : 'Fullscreen'"
                class="hidden rounded-lg p-2 text-muted-foreground transition-colors hover:bg-accent focus:ring-4 focus:ring-line-strong focus:outline-none lg:inline-flex"
                :class="full && 'bg-brand-soft text-primary'">
                <x-lucide-maximize class="size-5" x-show="!full" />
                <x-lucide-minimize class="size-5" x-show="full" x-cloak />
            </button>

            <button type="button" @click="open = true" aria-label="Open menu" :aria-expanded="open"
                @class(['inline-flex rounded-lg p-2 text-muted-foreground hover:bg-accent', $drawerHidden])>
                <x-lucide-menu class="size-5" />
            </button>
        </div>
    </div>

    {{-- Mobile drawer from the left: search, main menu, then the page's sidebar (categories) when it has one.
        Teleported to <body>: once scrolled, the header's backdrop-blur makes it the containing block for fixed
        children, which squeezed the drawer into the header's height. --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak @class(['fixed inset-0 z-50', $drawerHidden]) role="dialog" aria-modal="true" aria-label="Menu">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="open = false"></div>
            <div x-show="open" x-trap.noscroll="open"
                x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="absolute inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col overflow-y-auto border-r border-border bg-card shadow-lg">
                <div class="flex items-center justify-between border-b border-border px-4 py-4">
                    <span class="font-semibold">Menu</span>
                    <button type="button" @click="open = false" aria-label="Close menu" class="rounded-lg p-2 text-muted-foreground hover:bg-accent">
                        <x-lucide-x class="size-5" />
                    </button>
                </div>
                <form action="{{ url('/search') }}" method="get" role="search" aria-label="Mobile search" class="border-b border-border p-4">
                    <label for="mobile-search" class="sr-only">Search tutorials</label>
                    <input id="mobile-search" name="q" type="search" placeholder="Search..."
                        class="w-full rounded-full bg-muted px-4 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none">
                </form>
                <nav class="flex flex-col gap-1 p-3" aria-label="Mobile">
                    @foreach ($nav as $item)
                        @if ($item['children'])
                            <div x-data="{ sub: false }">
                                <button type="button" @click="sub = !sub" :aria-expanded="sub"
                                    class="flex w-full items-center justify-between rounded-md px-3 py-2.5 font-medium text-foreground hover:bg-accent">
                                    {{ $item['label'] }} <x-lucide-chevron-down class="size-4 text-muted-foreground transition-transform" ::class="sub && 'rotate-180'" />
                                </button>
                                <div x-show="sub" x-collapse x-cloak class="ml-3 flex flex-col border-l border-border pl-2">
                                    @if ($item['href'])
                                        <x-site.nav-link :item="$item" class="w-full rounded-md px-3 py-2 text-sm text-muted-foreground hover:bg-accent" />
                                    @endif
                                    @foreach ($item['children'] as $child)
                                        <x-site.nav-link :item="$child" class="w-full rounded-md px-3 py-2 text-sm text-muted-foreground hover:bg-accent" />
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <x-site.nav-link :item="$item" :active="\App\Support\Navigation::isActive($item['href'])"
                                class="rounded-md px-3 py-2.5 font-medium text-foreground hover:bg-accent" />
                        @endif
                    @endforeach
                </nav>
                @if ($sidebar !== '')
                    <div class="border-t border-border p-5">{!! $sidebar !!}</div>
                @endif
            </div>
        </div>
    </template>
</header>

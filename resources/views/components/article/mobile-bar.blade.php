{{--
    Below lg: a "Categories" button opening the category drawer, and below xl a collapsible "On this page" (T2.04).
--}}
@props(['categories' => [], 'toc' => []])
<div class="mb-4 flex flex-col gap-3 xl:hidden" x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
    <div class="flex gap-2">
        @if ($categories)
            <button type="button" @click="drawer = true" :aria-expanded="drawer"
                class="inline-flex items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium text-foreground shadow-xs hover:border-primary lg:hidden">
                <x-lucide-panel-left class="size-4" /> Categories
            </button>
        @endif
    </div>

    @if ($toc)
        <details class="group rounded-xl border border-border bg-card shadow-xs">
            <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-semibold text-foreground">
                <span class="flex items-center gap-2"><x-lucide-list-tree class="size-4 text-primary" /> On this page</span>
                <x-lucide-chevron-down class="size-4 text-muted-foreground transition-transform group-open:rotate-180" />
            </summary>
            <nav class="border-t border-border px-4 py-3 text-sm" aria-label="Table of contents (mobile)">
                @foreach ($toc as $item)
                    <a href="#{{ $item['id'] }}" @click="$el.closest('details').open = false"
                        @class(['block py-1 text-muted-foreground hover:text-primary', 'pl-4' => ($item['level'] ?? 2) === 3])>{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </details>
    @endif

    @if ($categories)
        <template x-teleport="body">
            <div x-show="drawer" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Categories">
                <div x-show="drawer" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="drawer = false"></div>
                <div x-show="drawer" x-trap.noscroll="drawer"
                    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                    class="absolute inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col overflow-y-auto border-r border-border bg-card p-5 shadow-lg">
                    <div class="mb-2 flex justify-end">
                        <button type="button" @click="drawer = false" aria-label="Close categories" class="rounded-lg p-2 text-muted-foreground hover:bg-accent"><x-lucide-x class="size-5" /></button>
                    </div>
                    <x-site.category-sidebar :categories="$categories" />
                </div>
            </div>
        </template>
    @endif
</div>

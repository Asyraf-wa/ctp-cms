{{--
    Ctrl/⌘ K command palette (PUB-06, DESIGN §5): keyboard-first, grouped results, recent searches.
    Opened by the shortcut, "/" and any [data-search-trigger] form; plain forms still work without JS.
--}}
<div x-data="searchPalette(@js(route('api.search')), @js(url('/search')))" x-cloak
    @keydown.window="shortcut($event)" @open-search.window="open($event.detail?.q)">
    <template x-teleport="body">
        <div x-show="isOpen" class="fixed inset-0 z-[80] flex items-start justify-center px-4 pt-[12vh]" role="dialog" aria-modal="true" aria-label="Search">
            <div x-show="isOpen" x-transition.opacity.duration.150ms class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="close()"></div>

            <div x-show="isOpen" x-trap.noscroll="isOpen" x-transition.duration.150ms
                class="relative w-full max-w-2xl overflow-hidden rounded-xl border border-border bg-card shadow-lg" @keydown.escape.prevent.stop="close()">
                <form @submit.prevent="go()" class="flex items-center gap-3 border-b border-border px-4" role="search">
                    <x-lucide-search class="size-5 shrink-0 text-muted-foreground" />
                    <label for="palette-q" class="sr-only">Search</label>
                    <input id="palette-q" x-ref="input" x-model="q" @input.debounce.150ms="fetchResults()" type="search" autocomplete="off" spellcheck="false"
                        placeholder="Search tutorials, tags, categories…" role="combobox" aria-autocomplete="list" aria-controls="palette-results" :aria-expanded="items.length > 0"
                        :aria-activedescendant="active >= 0 ? 'palette-item-' + active : null"
                        @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                        class="h-14 w-full bg-transparent text-base text-foreground placeholder:text-muted-foreground focus:outline-none">
                    <kbd class="hidden shrink-0 rounded border border-border px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground sm:block">Esc</kbd>
                </form>

                <div id="palette-results" role="listbox" class="max-h-[60vh] overflow-y-auto p-2" aria-label="Results">
                    {{-- Recent searches --}}
                    <template x-if="q.trim().length < 2">
                        <div>
                            <template x-if="recent.length">
                                <div>
                                    <p class="px-3 pt-2 pb-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Recent</p>
                                    <template x-for="(term, i) in recent" :key="term">
                                        <button type="button" @click="q = term; fetchResults(); $refs.input.focus()"
                                            class="flex w-full items-center gap-3 rounded-sm px-3 py-2 text-left text-sm text-foreground hover:bg-accent">
                                            <x-lucide-history class="size-4 text-muted-foreground" /> <span x-text="term"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                            <p class="px-3 py-6 text-center text-sm text-muted-foreground" x-show="!recent.length">Type at least two characters to search.</p>
                        </div>
                    </template>

                    {{-- Loading skeleton --}}
                    <div x-show="loading && !items.length && q.trim().length >= 2" class="space-y-2 p-2" aria-hidden="true">
                        <div class="h-12 animate-pulse rounded-sm bg-muted"></div>
                        <div class="h-12 animate-pulse rounded-sm bg-muted"></div>
                        <div class="h-12 animate-pulse rounded-sm bg-muted"></div>
                    </div>

                    <template x-for="group in groups" :key="group.label">
                        <div class="mb-1">
                            <p class="px-3 pt-2 pb-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase" x-text="group.label"></p>
                            <template x-for="item in group.items" :key="item.url">
                                <a :href="item.url" :id="'palette-item-' + item.index" role="option" :aria-selected="active === item.index"
                                    @mouseenter="active = item.index" @click="remember()"
                                    class="flex items-start gap-3 rounded-sm px-3 py-2.5" :class="active === item.index ? 'bg-brand-soft' : ''">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium" :class="active === item.index ? 'text-brand-soft-foreground' : 'text-foreground'" x-text="item.title"></span>
                                        <span class="block truncate text-xs text-muted-foreground" x-show="item.excerpt" x-text="item.excerpt"></span>
                                    </span>
                                    <span class="shrink-0 text-xs text-muted-foreground" x-text="item.meta"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <p x-show="!loading && searched && !items.length && q.trim().length >= 2" class="px-3 py-8 text-center text-sm text-muted-foreground">
                        No matches for “<span class="font-semibold text-foreground" x-text="q"></span>”.
                    </p>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-border bg-surface-2 px-4 py-2.5 text-xs text-muted-foreground">
                    <span class="hidden items-center gap-3 sm:flex">
                        <span><kbd class="rounded border border-border bg-card px-1">↑</kbd> <kbd class="rounded border border-border bg-card px-1">↓</kbd> to navigate</span>
                        <span><kbd class="rounded border border-border bg-card px-1">Enter</kbd> to open</span>
                    </span>
                    <a :href="searchUrl + '?q=' + encodeURIComponent(q)" x-show="q.trim().length >= 2" @click="remember()" class="font-semibold text-primary hover:underline">See all results</a>
                </div>
            </div>
        </div>
    </template>
</div>

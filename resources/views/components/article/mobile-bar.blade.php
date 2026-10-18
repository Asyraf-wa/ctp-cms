{{--
    Below xl: a collapsible "On this page" (T2.04). Categories live in the header menu drawer below lg.
--}}
@props(['toc' => []])
@if ($toc)
<div class="mb-4 flex flex-col gap-3 xl:hidden">
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
</div>
@endif

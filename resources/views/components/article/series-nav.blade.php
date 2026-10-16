{{-- Series navigator (TAX-03, DESIGN §5): "Part 2 of 5" with the list of parts. --}}
@props(['article', 'parts'])
@if ($article->series && $parts->count() > 1)
    @php($index = $parts->search(fn ($p) => $p->id === $article->id))
    <nav x-data="{ open: false }" class="not-prose mt-8 rounded-xl border border-border bg-surface-2" aria-label="Series">
        <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center gap-4 p-4 text-left">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-primary"><x-lucide-list-ordered class="size-5" /></span>
            <span class="min-w-0 flex-1">
                <span class="block text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    {{ $index !== false ? 'Part '.($index + 1).' of '.$parts->count() : $parts->count().' parts' }}
                </span>
                <span class="block truncate font-semibold text-foreground">{{ $article->series->title }}</span>
            </span>
            {{-- Progress through the series --}}
            <span class="hidden h-1.5 w-24 overflow-hidden rounded-full bg-border sm:block" aria-hidden="true">
                <span class="block h-full rounded-full bg-primary" style="width: {{ $index !== false ? round(($index + 1) / $parts->count() * 100) : 0 }}%"></span>
            </span>
            <x-lucide-chevron-down class="size-4 shrink-0 text-muted-foreground transition-transform" ::class="open && 'rotate-180'" />
        </button>
        <ol x-show="open" x-collapse x-cloak class="space-y-1 border-t border-border px-4 py-3 text-sm">
            @foreach ($parts as $part)
                <li>
                    <a href="{{ url($part->publicPath()) }}" @if ($part->id === $article->id) aria-current="page" @endif
                        @class(['flex items-start gap-3 rounded-sm px-2 py-1.5', 'bg-brand-soft font-semibold text-brand-soft-foreground' => $part->id === $article->id, 'text-muted-foreground hover:bg-accent hover:text-foreground' => $part->id !== $article->id])>
                        <span class="w-5 shrink-0 text-right tabular-nums">{{ $loop->iteration }}.</span>
                        <span>{{ $part->title }}</span>
                        @if ($index !== false && $loop->index < $index)
                            <x-lucide-check class="ml-auto size-4 shrink-0 text-primary" aria-label="Earlier part" />
                        @endif
                    </a>
                </li>
            @endforeach
        </ol>
        <a href="{{ url($article->series->publicPath()) }}" class="block border-t border-border px-4 py-2.5 text-xs font-semibold tracking-wide text-primary uppercase hover:bg-accent">View the whole series</a>
    </nav>
@endif

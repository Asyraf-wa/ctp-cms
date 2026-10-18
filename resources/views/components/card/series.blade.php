@props(['series', 'heading' => 'h3'])
<article class="group relative flex flex-col overflow-hidden rounded-xl border border-border bg-card shadow-xs transition duration-200 hover:-translate-y-0.5 hover:shadow-md motion-reduce:transition-none motion-reduce:hover:translate-y-0">
    <x-site.cover :media="$series->cover" icon="list-ordered" />
    <div class="flex flex-1 flex-col p-5">
        <p class="mb-2 text-xs font-semibold tracking-wide text-primary uppercase">Series · {{ $series->articles_count }} {{ Str::plural('part', $series->articles_count) }}</p>
        <{{ $heading }} class="text-lg leading-snug font-semibold text-foreground">
            <a href="{{ url($series->publicPath()) }}" class="after:absolute after:inset-0 group-hover:text-primary focus:outline-none focus-visible:after:rounded-xl focus-visible:after:ring-2 focus-visible:after:ring-ring">{{ $series->title }}</a>
        </{{ $heading }}>
        @if ($series->description)
            <p class="mt-2 line-clamp-2 text-sm text-muted-foreground">{{ $series->description }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-primary">Start the series <x-lucide-arrow-right class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
    </div>
</article>

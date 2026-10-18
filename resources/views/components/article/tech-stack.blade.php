{{-- Right column of a tutorial: every technology it uses, main one first, with tested versions. --}}
@props(['article'])
@if ($article->technologies->isNotEmpty())
    <section class="mb-8" aria-labelledby="tech-stack-heading">
        <h3 id="tech-stack-heading" class="mb-3 text-xs font-semibold tracking-wider text-foreground uppercase">Tech stack</h3>
        <ul class="space-y-1">
            @foreach ($article->technologies as $technology)
                <li class="tech-hover flex items-center gap-3 rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent">
                    <x-site.tech-icon :technology="$technology" size="size-5" />
                    <span class="min-w-0 flex-1 truncate text-foreground">{{ $technology->name }}</span>
                    @if ($technology->pivot->version)
                        <span class="shrink-0 font-mono text-xs text-muted-foreground" title="Tested with {{ $technology->name }} {{ $technology->pivot->version }}">{{ $technology->pivot->version }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif

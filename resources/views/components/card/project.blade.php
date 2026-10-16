{{-- Project card (DESIGN §5): cover, name + status, tagline, stack chips, Live and Code. --}}
@props(['project', 'eager' => false, 'heading' => 'h3'])
<article {{ $attributes->class('group relative flex flex-col overflow-hidden rounded-xl border border-border bg-card shadow-xs transition duration-200 hover:-translate-y-0.5 hover:shadow-md motion-reduce:transition-none motion-reduce:hover:translate-y-0') }}>
    <x-site.cover :media="$project->cover" icon="rocket" :eager="$eager" />
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-start justify-between gap-3">
            <{{ $heading }} class="text-lg leading-snug font-semibold text-foreground">
                <a href="{{ url($project->publicPath()) }}" class="after:absolute after:inset-0 group-hover:text-primary focus:outline-none focus-visible:after:rounded-xl focus-visible:after:ring-2 focus-visible:after:ring-ring">{{ $project->title }}</a>
            </{{ $heading }}>
            <x-site.project-status :status="$project->status" class="mt-0.5 shrink-0" />
        </div>
        @if ($project->tagline)
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-muted-foreground">{{ $project->tagline }}</p>
        @endif
        @if ($project->technologies->isNotEmpty())
            <ul class="mt-4 flex flex-wrap gap-1.5" aria-label="Tech stack">
                @foreach ($project->technologies->take(5) as $technology)
                    <li class="inline-flex items-center gap-1.5 rounded-sm border border-border bg-surface-2 px-2 py-0.5 text-xs font-medium text-muted-foreground">
                        <x-site.tech-icon :technology="$technology" size="size-3.5" /> {{ $technology->name }}
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($project->live_url || $project->repo_url)
            {{-- Above the card's stretched link so they stay clickable. --}}
            <div class="relative z-10 mt-auto flex gap-4 pt-5 text-sm font-medium">
                @if ($project->live_url)
                    <a href="{{ $project->live_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-primary hover:underline"><x-lucide-external-link class="size-4" /> Live</a>
                @endif
                @if ($project->repo_url)
                    <a href="{{ $project->repo_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-muted-foreground hover:text-foreground"><x-lucide-github class="size-4" /> Code</a>
                @endif
            </div>
        @endif
    </div>
</article>

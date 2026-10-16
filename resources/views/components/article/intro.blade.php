{{-- Outdated notice (ART-08), TL;DR (ART-06/GEO-03), tested-with and prerequisites (ART-05). --}}
@props(['article'])
<div class="not-prose mt-8 space-y-4">
    @if ($article->isOutdated())
        <div class="callout callout--warning" role="note">
            <x-lucide-triangle-alert class="callout__icon" aria-hidden="true" />
            <div class="callout__content">
                <p class="callout__title">This tutorial may be outdated</p>
                <p>It was last reviewed {{ ($article->content_updated_at ?? $article->published_at)?->diffForHumans() ?? 'a while ago' }}. Some steps may differ in newer versions.</p>
            </div>
        </div>
    @endif

    @if ($article->tldr)
        <section class="rounded-xl border border-primary/20 bg-brand-soft p-5" aria-labelledby="tldr-heading">
            <h2 id="tldr-heading" class="mb-2 flex items-center gap-2 text-sm font-semibold tracking-wide text-brand-soft-foreground uppercase">
                <x-lucide-sparkles class="size-4" /> Key takeaways
            </h2>
            <ul class="list-disc space-y-1 pl-5 text-[0.95rem] text-foreground marker:text-primary">
                @foreach ($article->tldr as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($article->tested_with || $article->prerequisites)
        <div class="grid gap-4 rounded-xl border border-border bg-surface-2 p-5 text-sm sm:grid-cols-2">
            @if ($article->tested_with)
                <div>
                    <p class="mb-2 font-semibold text-foreground">Tested with</p>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($article->tested_with as $item)
                            <li class="rounded-xs border border-border bg-card px-2 py-0.5 font-medium">{{ $item['name'] }} <span class="text-muted-foreground">{{ $item['version'] }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($article->prerequisites)
                <div>
                    <p class="mb-2 font-semibold text-foreground">Before you start</p>
                    <ul class="list-disc space-y-1 pl-5 text-muted-foreground">
                        @foreach ($article->prerequisites as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>

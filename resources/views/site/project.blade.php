{{-- Project detail (PUB-09, DESIGN §6): hero, write-up with an "At a glance" panel, gallery, related tutorials. --}}
<x-layouts.site :meta="$meta">
    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.breadcrumbs :items="$meta->breadcrumbs" class="mb-6" />

        <header class="grid items-center gap-8 lg:grid-cols-2">
            <div>
                <div class="mb-3 flex items-center gap-3">
                    <x-site.project-status :status="$project->status" />
                    @if ($period = $project->period())
                        <span class="text-sm text-muted-foreground">{{ $period }}</span>
                    @endif
                </div>
                <h1 class="text-4xl font-bold tracking-tight text-balance text-foreground sm:text-5xl">{{ $project->title }}</h1>
                @if ($project->tagline)
                    <p class="mt-4 text-xl leading-relaxed text-muted-foreground">{{ $project->tagline }}</p>
                @endif
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($project->live_url)
                        <a href="{{ $project->live_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary-hover"><x-lucide-external-link class="size-4" /> Visit live site</a>
                    @endif
                    @if ($project->repo_url)
                        <a href="{{ $project->repo_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-5 py-2.5 text-sm font-semibold text-foreground hover:border-primary hover:text-primary"><x-lucide-github class="size-4" /> View the code</a>
                    @endif
                </div>
            </div>
            <x-site.cover :media="$project->cover" icon="rocket" :eager="true" sizes="(min-width: 1024px) 680px, 100vw" class="rounded-xl border border-border shadow-md" />
        </header>

        <div class="mt-12 flex flex-col gap-8 lg:flex-row-reverse">
            <aside class="w-full shrink-0 lg:w-80" aria-label="At a glance">
                <div class="rounded-xl border border-border bg-card p-6 lg:sticky lg:top-28">
                    <h2 class="mb-4 text-xs font-semibold tracking-wider text-muted-foreground uppercase">At a glance</h2>
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Status</dt>
                            <dd class="mt-1"><x-site.project-status :status="$project->status" /></dd>
                        </div>
                        @if ($project->started_at)
                            <div>
                                <dt class="text-muted-foreground">Timeline</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ $project->started_at->format('M Y') }} – {{ $project->ended_at?->format('M Y') ?? ($project->status->value === 'archived' ? 'archived' : 'present') }}
                                </dd>
                            </div>
                        @endif
                        @if ($project->technologies->isNotEmpty())
                            <div>
                                <dt class="text-muted-foreground">Stack</dt>
                                <dd class="mt-2 space-y-1">
                                    @foreach ($project->technologies as $technology)
                                        <a href="{{ url('/projects').'?tech='.$technology->slug }}" class="tech-hover flex items-center gap-2.5 rounded-sm px-2 py-1 text-foreground hover:bg-accent">
                                            <x-site.tech-icon :technology="$technology" size="size-4" /> {{ $technology->name }}
                                        </a>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        @if ($project->live_url || $project->repo_url)
                            <div>
                                <dt class="text-muted-foreground">Links</dt>
                                <dd class="mt-1 space-y-1">
                                    @if ($project->live_url)
                                        <a href="{{ $project->live_url }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 truncate font-medium text-primary hover:underline"><x-lucide-globe class="size-4 shrink-0" /> {{ parse_url($project->live_url, PHP_URL_HOST) }}</a>
                                    @endif
                                    @if ($project->repo_url)
                                        <a href="{{ $project->repo_url }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 truncate font-medium text-primary hover:underline"><x-lucide-github class="size-4 shrink-0" /> Source code</a>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </aside>

            <div class="min-w-0 flex-1 space-y-12">
                @if ($project->body_rendered)
                    <article class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-10">
                        <div class="prose-ctp">{!! $project->body_rendered !!}</div>
                    </article>
                @endif

                @if ($gallery->isNotEmpty())
                    <section aria-labelledby="gallery-heading">
                        <h2 id="gallery-heading" class="mb-4 text-2xl font-bold tracking-tight text-foreground">Gallery</h2>
                        <div class="grid gap-4 sm:grid-cols-2" data-gallery>
                            @foreach ($gallery as $item)
                                <figure class="overflow-hidden rounded-xl border border-border bg-card">
                                    <a href="{{ $item['media']->url() }}" data-lightbox="{{ $item['caption'] ?: $item['media']->alt }}" class="block"
                                        aria-label="View larger: {{ $item['caption'] ?: ($item['media']->alt ?: 'screenshot '.$loop->iteration) }}">
                                        <x-site.cover :media="$item['media']" sizes="(min-width: 640px) 50vw, 100vw" />
                                    </a>
                                    @if ($item['caption'])
                                        <figcaption class="px-4 py-3 text-sm text-muted-foreground">{{ $item['caption'] }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($articles->isNotEmpty())
                    <section aria-labelledby="related-heading">
                        <h2 id="related-heading" class="mb-4 text-2xl font-bold tracking-tight text-foreground">Tutorials from this project</h2>
                        <div class="grid gap-6 md:grid-cols-2">
                            @foreach ($articles as $article)
                                <x-card.article :article="$article" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-layouts.site>

{{-- Projects (PUB-09): showcase grid with a tech filter. --}}
<x-layouts.site :meta="$meta">
    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header title="Projects" :breadcrumbs="$meta->breadcrumbs"
            description="Things built and shipped: what problem each one solves, how it was made and the tutorials written along the way." />

        @if ($stack->count() > 1)
            <nav class="mb-8 flex flex-wrap gap-2" aria-label="Filter by tech">
                <a href="{{ url('/projects') }}" @if (! $tech) aria-current="true" @endif
                    @class(['rounded-full border px-3 py-1 text-sm font-medium', 'border-primary bg-primary text-primary-foreground' => ! $tech, 'border-border text-muted-foreground hover:border-primary hover:text-primary' => $tech])>All</a>
                @foreach ($stack as $item)
                    <a href="{{ url('/projects').'?tech='.$item->slug }}" @if ($tech?->is($item)) aria-current="true" @endif
                        @class(['tech-hover inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium', 'border-primary bg-primary text-primary-foreground' => $tech?->is($item), 'border-border text-muted-foreground hover:border-primary hover:text-primary' => ! $tech?->is($item)])>
                        @unless ($tech?->is($item))
                            <x-site.tech-icon :technology="$item" size="size-3.5" />
                        @endunless
                        {{ $item->name }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($projects->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($projects as $project)
                    <x-card.project :project="$project" :eager="$loop->first" heading="h2" />
                @endforeach
            </div>
        @else
            <x-site.empty icon="rocket" :title="$tech ? 'No projects use '.$tech->name : 'No projects yet'" :action="$tech ? 'Show all projects' : null" :href="$tech ? url('/projects') : null" />
        @endif
    </div>
</x-layouts.site>

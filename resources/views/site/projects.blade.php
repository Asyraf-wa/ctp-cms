{{-- Projects (PUB-09): showcase grid with a tech filter. --}}
<x-layouts.site title="Projects" description="Apps, tools and experiments built by Code The Pixel, with the tutorials written along the way."
    :robots="$tech ? 'noindex, follow' : null">
    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header title="Projects" :breadcrumbs="[['Projects', null]]"
            description="Things built and shipped: what problem each one solves, how it was made and the tutorials written along the way." />

        @if (count($stack) > 1)
            <nav class="mb-8 flex flex-wrap gap-2" aria-label="Filter by tech">
                <a href="{{ url('/projects') }}" @if (! $tech) aria-current="true" @endif
                    @class(['rounded-full border px-3 py-1 text-sm font-medium', 'border-primary bg-primary text-primary-foreground' => ! $tech, 'border-border text-muted-foreground hover:border-primary hover:text-primary' => $tech])>All</a>
                @foreach ($stack as $item)
                    <a href="{{ url('/projects').'?'.http_build_query(['tech' => $item]) }}" @if ($tech === $item) aria-current="true" @endif
                        @class(['rounded-full border px-3 py-1 text-sm font-medium', 'border-primary bg-primary text-primary-foreground' => $tech === $item, 'border-border text-muted-foreground hover:border-primary hover:text-primary' => $tech !== $item])>{{ $item }}</a>
                @endforeach
            </nav>
        @endif

        @if ($projects->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($projects as $project)
                    <x-card.project :project="$project" :eager="$loop->first" />
                @endforeach
            </div>
        @else
            <x-site.empty icon="rocket" :title="$tech ? 'No projects use '.$tech : 'No projects yet'" :action="$tech ? 'Show all projects' : null" :href="$tech ? url('/projects') : null" />
        @endif
    </div>
</x-layouts.site>

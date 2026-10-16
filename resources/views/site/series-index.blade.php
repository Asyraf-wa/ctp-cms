{{-- Series index (TAX-03): learning paths. --}}
<x-layouts.site title="Learning paths" description="Step-by-step tutorial series, in reading order.">
    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header title="Learning paths" :breadcrumbs="[['Series', null]]"
            description="Tutorial series that build on each other. Start at part one and work through in order." />
        @if ($series->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($series as $item)
                    <x-card.series :series="$item" heading="h2" />
                @endforeach
            </div>
        @else
            <x-site.empty icon="list-ordered" title="No series yet" action="Browse all tutorials" :href="url('/tutorials')" />
        @endif
    </div>
</x-layouts.site>

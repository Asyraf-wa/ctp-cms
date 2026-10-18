{{-- A home/listing section: heading with an optional "View all" link. --}}
@props(['title', 'href' => null, 'linkLabel' => 'View all', 'description' => null])
<section {{ $attributes->class('mt-16') }}>
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-foreground">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-muted-foreground">{{ $description }}</p>
            @endif
        </div>
        @if ($href)
            <a href="{{ $href }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover">
                {{ $linkLabel }} <x-lucide-arrow-right class="size-4" />
            </a>
        @endif
    </div>
    {{ $slot }}
</section>

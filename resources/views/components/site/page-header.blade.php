{{-- Title block for listing and archive pages. --}}
@props(['title', 'eyebrow' => null, 'description' => null, 'breadcrumbs' => null, 'icon' => null, 'technology' => null])
<header {{ $attributes->class('mb-8') }}>
    @if ($breadcrumbs)
        <x-site.breadcrumbs :items="$breadcrumbs" class="mb-4" />
    @endif
    <div class="flex items-start gap-4">
        @if ($technology)
            {{-- The page is about this technology, so its logo shows in colour. --}}
            <span class="tech-hover flex size-12 shrink-0 items-center justify-center rounded-xl border border-border bg-card"
                @if ($technology->brandColor()) style="color: {{ $technology->brandColor() }}" @endif>
                {{ $technology->iconHtml('size-6') }}
            </span>
        @elseif ($icon)
            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-primary">
                <x-dynamic-component :component="'lucide-'.$icon" class="size-6" />
            </span>
        @endif
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="text-sm font-semibold tracking-wide text-primary uppercase">{{ $eyebrow }}</p>
            @endif
            <h1 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-3 max-w-3xl text-lg leading-relaxed text-muted-foreground">{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
    </div>
</header>

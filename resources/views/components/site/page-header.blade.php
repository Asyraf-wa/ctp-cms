{{-- Title block for listing and archive pages. --}}
@props(['title', 'eyebrow' => null, 'description' => null, 'breadcrumbs' => null, 'icon' => null])
<header {{ $attributes->class('mb-8') }}>
    @if ($breadcrumbs)
        <x-site.breadcrumbs :items="$breadcrumbs" class="mb-4" />
    @endif
    <div class="flex items-start gap-4">
        @if ($icon)
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

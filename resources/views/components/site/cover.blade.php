{{--
    16:9 cover for cards. Without an image (most legacy tutorials) a branded tile with the category icon
    keeps the grid even and avoids layout shift.
--}}
@props(['media' => null, 'icon' => 'code-xml', 'label' => null, 'sizes' => '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw', 'eager' => false])
{{-- Fallback tiles are shorter on phones so a list of image-less cards stays scannable. --}}
<div {{ $attributes->class(['relative overflow-hidden bg-muted', 'aspect-video' => $media, 'aspect-[3/1] sm:aspect-video' => ! $media]) }}>
    @if ($media)
        <img src="{{ $media->url($media->isRaster() ? '768' : null) }}"
            @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="{{ $sizes }}" @endif
            @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
            alt="{{ $media->alt }}" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
            class="size-full object-cover transition-transform duration-300 group-hover:scale-[1.02] motion-reduce:transition-none">
    @else
        <div class="cover-fallback flex size-full items-center justify-center" aria-hidden="true">
            <x-dynamic-component :component="'lucide-'.$icon" class="size-10 text-primary/70" />
            @if ($label)
                <span class="absolute bottom-3 left-4 text-xs font-semibold tracking-wide text-primary/80 uppercase">{{ $label }}</span>
            @endif
        </div>
    @endif
</div>

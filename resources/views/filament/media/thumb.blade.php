@php($media = $getRecord())
<div class="relative aspect-[4/3] w-full overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
    @if ($media->isImage())
        <img src="{{ $media->thumbUrl() }}" alt="{{ $media->alt }}" loading="lazy"
            class="size-full {{ $media->mime === 'image/svg+xml' ? 'object-contain p-4' : 'object-cover' }}">
    @else
        <div class="flex size-full flex-col items-center justify-center gap-2 text-gray-400">
            <x-dynamic-component :component="$media->extension === 'pdf' ? 'lucide-file-text' : 'lucide-file-archive'" class="size-10" />
            <span class="text-xs font-semibold uppercase">{{ $media->extension }}</span>
        </div>
    @endif
    @if ($media->usages_count)
        <span class="absolute top-2 right-2 rounded-md bg-gray-950/70 px-1.5 py-0.5 text-[10px] font-semibold text-white">In use</span>
    @endif
</div>

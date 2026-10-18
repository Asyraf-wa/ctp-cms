@php($media = $getRecord())
<div class="ms-3 size-12 overflow-hidden rounded-md bg-gray-100 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
    @if ($media->isImage())
        <img src="{{ $media->thumbUrl() }}" alt="" loading="lazy" class="size-full object-cover">
    @else
        <div class="flex size-full items-center justify-center text-[10px] font-semibold text-gray-400 uppercase">{{ $media->extension }}</div>
    @endif
</div>

@php($media = $getMedia())
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="flex items-center gap-4 rounded-xl border border-dashed border-gray-300 p-3 dark:border-white/15">
        <div class="flex aspect-[4/3] w-36 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            @if ($media?->isImage())
                <img src="{{ $media->thumbUrl() }}" alt="{{ $media->alt }}" class="size-full {{ $media->mime === 'image/svg+xml' ? 'object-contain p-2' : 'object-cover' }}">
            @elseif ($media)
                <span class="text-xs font-semibold text-gray-500 uppercase">{{ $media->extension }}</span>
            @else
                <x-lucide-image class="size-8 text-gray-300 dark:text-gray-600" />
            @endif
        </div>
        <div class="min-w-0 flex-1 space-y-2">
            @if ($media)
                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $media->displayName() }}</p>
                <p class="text-xs text-gray-500">
                    {{ collect([$media->dimensions(), $media->humanSize()])->filter()->implode(' · ') }}
                    @if (blank($media->alt) && $media->isImage())
                        · <span class="text-warning-600 dark:text-warning-400">no alt text</span>
                    @endif
                </p>
            @elseif ($getState())
                <p class="text-sm text-danger-600">The selected file no longer exists.</p>
            @else
                <p class="text-sm text-gray-500">No image selected.</p>
            @endif
            <div class="flex flex-wrap items-center gap-3">
                {{ $getAction('chooseMedia') }}
                {{ $getAction('removeMedia') }}
            </div>
        </div>
    </div>
</x-dynamic-component>

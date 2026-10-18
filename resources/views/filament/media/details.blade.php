{{-- Slide-over left column: preview, URL, details and "Used in" (MED-05). --}}
@php($media = $getRecord())
<div class="space-y-5">
    <div class="flex items-center justify-center overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
        style="background-image: conic-gradient(rgb(0 0 0 / .04) 25%, transparent 0 50%, rgb(0 0 0 / .04) 0 75%, transparent 0); background-size: 16px 16px;">
        @if ($media->isImage())
            <img src="{{ $media->url($media->isRaster() ? '1280' : null) }}" alt="{{ $media->alt }}" class="max-h-[55vh] w-auto">
        @else
            <div class="flex flex-col items-center gap-2 py-16 text-gray-400">
                <x-lucide-file class="size-12" />
                <span class="text-sm font-semibold uppercase">{{ $media->extension }}</span>
            </div>
        @endif
    </div>

    <div x-data="{ copied: false }" class="flex items-center gap-2">
        <input type="text" readonly value="{{ $media->url() }}" aria-label="File URL"
            class="w-full rounded-lg border-gray-300 bg-gray-50 px-3 py-1.5 text-xs text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
        <button type="button" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-primary-600 ring-1 ring-gray-950/10 hover:bg-gray-50 dark:text-primary-400 dark:ring-white/20 dark:hover:bg-white/5"
            @click="navigator.clipboard.writeText(@js($media->url())); copied = true; setTimeout(() => copied = false, 1500)"
            x-text="copied ? 'Copied' : 'Copy URL'">Copy URL</button>
        <a href="{{ $media->url() }}" target="_blank" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-700 ring-1 ring-gray-950/10 hover:bg-gray-50 dark:text-gray-200 dark:ring-white/20 dark:hover:bg-white/5">Open</a>
    </div>

    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
        <dt class="text-gray-500">File name</dt><dd class="truncate" title="{{ $media->filename }}">{{ $media->filename }}</dd>
        <dt class="text-gray-500">Type</dt><dd>{{ $media->mime }}</dd>
        @if ($media->dimensions())
            <dt class="text-gray-500">Dimensions</dt><dd>{{ $media->dimensions() }} px</dd>
        @endif
        <dt class="text-gray-500">Size</dt><dd>{{ $media->humanSize() }}</dd>
        <dt class="text-gray-500">Uploaded</dt><dd>{{ $media->created_at->timezone(config('ctp.display_timezone'))->format('j M Y, g:i a') }}</dd>
        @if ($media->isRaster())
            <dt class="text-gray-500">Variants</dt>
            <dd>{{ $media->variants ? collect($media->variants)->keys()->implode(', ') : 'Generating…' }}</dd>
        @endif
    </dl>

    <div>
        <h3 class="mb-2 text-sm font-semibold">Used in</h3>
        @forelse ($media->usages()->with('usable')->get() as $usage)
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ $usage->usable && method_exists($usage->usable, 'mediaUsageLabel') ? $usage->usable->mediaUsageLabel() : ucfirst($usage->usable_type).' #'.$usage->usable_id }}
                <span class="text-gray-400">({{ $usage->field }})</span>
            </p>
        @empty
            <p class="text-sm text-gray-500">Not used anywhere yet. It can be deleted safely.</p>
        @endforelse
    </div>
</div>

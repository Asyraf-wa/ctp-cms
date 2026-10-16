@php($items = $getItems())
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if ($items->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 py-12 text-center text-sm text-gray-500 dark:border-white/15">
            Nothing found. Upload a new file below.
        </div>
    @else
        <div class="grid max-h-[50vh] grid-cols-3 gap-3 overflow-y-auto p-1 sm:grid-cols-4 lg:grid-cols-6" role="radiogroup">
            @foreach ($items as $media)
                <label wire:key="media-grid-{{ $media->id }}" class="group relative block cursor-pointer" title="{{ $media->displayName() }}">
                    <input type="radio" value="{{ $media->id }}" class="peer sr-only" {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}">
                    <div class="aspect-[4/3] overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-950/10 transition peer-checked:ring-2 peer-checked:ring-primary-600 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-600 dark:bg-white/5 dark:ring-white/10">
                        @if ($media->isImage())
                            <img src="{{ $media->thumbUrl() }}" alt="" loading="lazy" class="size-full {{ $media->mime === 'image/svg+xml' ? 'object-contain p-2' : 'object-cover' }}">
                        @else
                            <div class="flex size-full items-center justify-center text-xs font-semibold text-gray-400 uppercase">{{ $media->extension }}</div>
                        @endif
                    </div>
                    <span class="absolute top-1.5 right-1.5 hidden size-5 items-center justify-center rounded-full bg-primary-600 text-white shadow peer-checked:flex">
                        <x-lucide-check class="size-3.5" stroke-width="3" />
                    </span>
                    <p class="mt-1 truncate text-xs text-gray-600 dark:text-gray-400">{{ $media->displayName() }}</p>
                </label>
            @endforeach
        </div>
        @if ($items->count() === \App\Filament\Forms\Components\MediaGrid::LIMIT)
            <p class="mt-2 text-xs text-gray-500">Showing the {{ \App\Filament\Forms\Components\MediaGrid::LIMIT }} newest matches. Search to narrow down.</p>
        @endif
    @endif
</x-dynamic-component>

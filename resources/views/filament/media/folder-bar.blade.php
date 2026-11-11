{{-- MED-01: where you are in the library, and the folders inside it. Files below are the ones in this place. --}}
@php
    $crumb = 'rounded-md px-2 py-1 font-medium transition hover:bg-gray-100 dark:hover:bg-white/5';
    $here = 'text-gray-950 dark:text-white';
    $away = 'text-primary-600 dark:text-primary-400';
@endphp
<div class="flex flex-col gap-4 px-4 py-4 sm:px-6">
    <div class="flex flex-wrap items-center gap-x-1 gap-y-2 text-sm">
        <nav class="flex min-w-0 flex-wrap items-center gap-1" aria-label="Folder">
            <button type="button" wire:click="openFolder(null)" @class([$crumb, $mode === 'root' ? $here : $away])
                @if ($mode === 'root') aria-current="page" @endif>
                Library
            </button>
            @foreach ($trail as $folder)
                <x-lucide-chevron-right class="size-4 shrink-0 text-gray-400" aria-hidden="true" />
                <button type="button" wire:click="openFolder('{{ $folder->id }}')" @class([$crumb, $loop->last ? $here : $away])
                    @if ($loop->last) aria-current="page" @endif>
                    {{ $folder->name }}
                </button>
            @endforeach
        </nav>

        <button type="button" wire:click="openFolder('all')" @class([$crumb, 'ms-auto inline-flex items-center gap-1.5', $mode === 'all' ? $here : $away])
            @if ($mode === 'all') aria-current="page" @endif>
            <x-lucide-images class="size-4" aria-hidden="true" />
            All files
        </button>
    </div>

    @if ($folders->isNotEmpty())
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
            @foreach ($folders as $folder)
                <button type="button" wire:click="openFolder('{{ $folder->id }}')" wire:key="folder-{{ $folder->id }}"
                    class="flex min-w-0 items-center gap-3 rounded-lg px-3 py-2.5 text-start ring-1 ring-gray-950/10 transition hover:bg-gray-50 dark:ring-white/10 dark:hover:bg-white/5">
                    <x-lucide-folder class="size-6 shrink-0 text-primary-500" aria-hidden="true" />
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">{{ $folder->name }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{ $folder->media_count }} {{ Str::plural('file', $folder->media_count) }}@if ($folder->children_count) · {{ $folder->children_count }} {{ Str::plural('folder', $folder->children_count) }}@endif
                        </span>
                    </span>
                </button>
            @endforeach
        </div>
    @endif

    <p class="text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">
        @switch($mode)
            @case('search') Results from every folder @break
            @case('all') All files @break
            @case('folder') Files in this folder @break
            @default Unfiled files
        @endswitch
    </p>
</div>

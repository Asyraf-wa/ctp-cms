@php($record = $getRecord())
<div class="flex gap-3 px-3 py-3">
    <img src="{{ $record->avatarUrl(64) }}" alt="" class="size-9 shrink-0 rounded-full" referrerpolicy="no-referrer" loading="lazy">
    <div class="min-w-0 max-w-xl">
        <p class="text-sm">
            <span class="font-semibold text-gray-950 dark:text-white">{{ $record->name }}</span>
            @if ($record->user_id)
                <span class="ml-1 rounded bg-primary-50 px-1.5 py-0.5 text-[10px] font-semibold text-primary-700 uppercase dark:bg-primary-500/20 dark:text-primary-300">Author</span>
            @endif
            <span class="text-gray-500 dark:text-gray-400">{{ $record->email }}</span>
        </p>
        @if ($record->parent)
            <p class="text-xs text-gray-500 dark:text-gray-400">↳ reply to {{ $record->parent->name }}</p>
        @endif
        <div class="prose prose-sm mt-1 line-clamp-4 max-w-none text-gray-700 dark:prose-invert dark:text-gray-300">{!! $record->body_html !!}</div>
    </div>
</div>

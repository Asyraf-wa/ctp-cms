{{-- SEO-12: copy a link, select words in the editor and press the link button. --}}
@if ($suggestions->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Add tags, a category or a title to get suggestions.</p>
@else
    <ul class="space-y-2.5">
        @foreach ($suggestions as $item)
            <li class="text-sm" x-data="{ copied: false }">
                <div class="flex items-start justify-between gap-2">
                    <a href="{{ $item['url'] }}" target="_blank" class="font-medium text-gray-950 hover:underline dark:text-white">{{ $item['title'] }}</a>
                    @if ($item['linked'])
                        <span class="shrink-0 text-xs font-medium text-success-600 dark:text-success-400">Linked ✓</span>
                    @else
                        <button type="button" class="shrink-0 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400"
                            @click="navigator.clipboard.writeText(@js($item['path'])); copied = true; setTimeout(() => copied = false, 1500)"
                            x-text="copied ? 'Copied' : 'Copy link'">Copy link</button>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item['path'] }} · {{ $item['reason'] }}</p>
            </li>
        @endforeach
    </ul>
@endif

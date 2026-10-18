{{-- SEO-02: Google result and social card previews, plus the on-page checklist. --}}
@php
    $fullTitle = strtr($format, ['{page}' => (string) $title, '{site}' => $site]);
    $shownTitle = \Illuminate\Support\Str::limit($fullTitle, 60);
    $shownDescription = \Illuminate\Support\Str::limit((string) $description, 158);
    $crumb = preg_replace('#^https?://#', '', $url);
    $passed = collect($checks)->where('ok', true)->count();
@endphp
<div class="grid gap-4 lg:grid-cols-2">
    <div class="space-y-3">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Google preview</p>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $site }} · {{ str_replace('/', ' › ', trim($crumb, '/')) }}</p>
            <p class="mt-1 text-lg leading-snug text-[#1a0dab] dark:text-[#8ab4f8]">{{ $shownTitle ?: 'Title' }}</p>
            <p class="mt-1 text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $shownDescription ?: 'Add an excerpt or SEO description so Google shows your words instead of a random snippet.' }}</p>
        </div>

        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Social card</p>
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
            <div class="flex aspect-[1200/630] flex-col justify-end bg-[#0b1220] p-4 text-white" style="background-image: radial-gradient(40% 60% at 90% 0%, rgb(20 184 166 / .35), transparent)">
                <p class="text-[11px] font-semibold tracking-wide text-[#5eead4] uppercase">Generated card</p>
                <p class="line-clamp-2 text-xl leading-tight font-extrabold">{{ $title ?: 'Title' }}</p>
            </div>
            <div class="bg-gray-50 px-3 py-2 dark:bg-white/5">
                <p class="text-[11px] text-gray-500 uppercase">{{ parse_url($url, PHP_URL_HOST) }}</p>
                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $title ?: 'Title' }}</p>
                <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $description }}</p>
            </div>
        </div>
    </div>

    <div>
        <p class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Checklist · {{ $passed }}/{{ count($checks) }}</p>
        <ul class="space-y-2">
            @foreach ($checks as $check)
                <li class="flex gap-2.5 text-sm">
                    @if ($check['ok'])
                        <x-filament::icon icon="heroicon-s-check-circle" class="mt-0.5 size-5 shrink-0 text-success-600 dark:text-success-400" />
                    @else
                        <x-filament::icon icon="heroicon-s-exclamation-circle" class="mt-0.5 size-5 shrink-0 text-warning-500" />
                    @endif
                    <span>
                        <span class="font-medium text-gray-950 dark:text-white">{{ $check['label'] }}</span>
                        @unless ($check['ok'])
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $check['hint'] }}</span>
                        @endunless
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
</div>

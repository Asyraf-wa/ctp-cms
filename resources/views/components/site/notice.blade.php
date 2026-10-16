{{-- Topbar notification (PRD SET-03). Server-rendered so the layout doesn't shift; dismissal is per message hash. --}}
@inject('notice', 'App\Settings\NoticeSettings')
@inject('ribbon', 'App\Settings\RibbonSettings')
@if ($notice->isVisible())
    @php
        $styles = [
            'info' => ['bg-[#eff6ff] text-[#1d4ed8] border-[#bfdbfe] dark:bg-[#14213d] dark:text-[#93c5fd] dark:border-[#1e3a8a]', 'info'],
            'success' => ['bg-brand-soft text-brand-soft-foreground border-[#99f6e4] dark:border-[#115e59]', 'circle-check'],
            'warning' => ['bg-[#fffbeb] text-[#854d0e] border-[#fde68a] dark:bg-[#2a2410] dark:text-[#facc15] dark:border-[#854d0e]', 'triangle-alert'],
            'maintenance' => ['bg-[#fef2f2] text-[#b91c1c] border-[#fecaca] dark:bg-[#2a1215] dark:text-[#f87171] dark:border-[#7f1d1d]', 'wrench'],
        ];
        [$classes, $icon] = $styles[$notice->style] ?? $styles['info'];
    @endphp
    <div id="site-notice" x-data="{ hidden: false }" x-show="!hidden" role="region" aria-label="Site notice"
        class="border-b text-sm {{ $classes }}">
        <div @class(['mx-auto flex min-h-10 max-w-[1440px] items-center gap-3 px-4 py-2 sm:px-6 lg:px-8', 'sm:pr-24 lg:pr-24' => $ribbon->isVisible()])>
            <x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true" />
            <p class="flex-1 [&_a]:font-semibold [&_a]:underline [&_a]:underline-offset-2">
                {!! \App\Support\Html::inline($notice->message) !!}
                @if ($notice->link_url && $notice->link_label)
                    <a href="{{ $notice->link_url }}" class="ml-2 whitespace-nowrap">{{ $notice->link_label }} &rarr;</a>
                @endif
            </p>
            @if ($notice->dismissible)
                <button type="button" aria-label="Dismiss notice" class="rounded-md p-1 opacity-70 transition hover:opacity-100"
                    @click="hidden = true; try { localStorage.setItem('ctp-notice', @js($notice->hash())) } catch {}">
                    <x-lucide-x class="size-4" />
                </button>
            @endif
        </div>
    </div>
@endif

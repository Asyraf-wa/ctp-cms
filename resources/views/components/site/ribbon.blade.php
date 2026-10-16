{{-- Corner ribbon (PRD SET-04): diagonal band top-right on desktop, a small badge on mobile. --}}
@inject('ribbon', 'App\Settings\RibbonSettings')
@if ($ribbon->isVisible())
    @php
        $tag = $ribbon->url ? 'a' : 'span';
        $link = $ribbon->url ? 'href="'.e($ribbon->url).'"'.($ribbon->new_tab ? ' target="_blank" rel="noopener"' : '') : '';
        $style = 'background-color: '.e($ribbon->bg_color).'; color: '.e($ribbon->resolvedTextColor()).';';
    @endphp
    <div class="pointer-events-none fixed top-0 right-0 z-50 hidden size-28 overflow-hidden sm:block">
        <{{ $tag }} {!! $link !!} style="{{ $style }}"
            class="pointer-events-auto absolute top-[22px] -right-[42px] block w-[170px] rotate-45 py-1 text-center text-[11px] font-semibold tracking-wider uppercase shadow-[0_4px_10px_-4px_rgb(0_0_0/0.35)] transition hover:brightness-90">
            {{ $ribbon->title }}
        </{{ $tag }}>
    </div>
    <{{ $tag }} {!! $link !!} style="{{ $style }}"
        class="fixed right-3 bottom-3 z-50 rounded-full px-3 py-1 text-xs font-semibold tracking-wide uppercase shadow-md sm:hidden">
        {{ $ribbon->title }}
    </{{ $tag }}>
@endif

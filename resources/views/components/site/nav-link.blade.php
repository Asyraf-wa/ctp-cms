{{-- One menu item (MENU-02): label, optional icon and badge. --}}
@props(['item', 'active' => false])
<a href="{{ $item['href'] }}" @if ($item['target'] === '_blank') target="_blank" rel="noopener" @endif
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([$item['class'], 'inline-flex items-center gap-1.5']) }}>
    @if ($item['icon'])
        <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4" />
    @endif
    <span>{{ $item['label'] }}</span>
    @if ($item['badge'])
        <span class="rounded-xs bg-brand-soft px-1.5 py-px text-[10px] font-semibold tracking-wide text-brand-soft-foreground uppercase">{{ $item['badge'] }}</span>
    @endif
</a>

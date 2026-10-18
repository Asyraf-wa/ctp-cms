@props(['icon' => 'inbox', 'title', 'action' => null, 'href' => null])
<div {{ $attributes->class('flex flex-col items-center rounded-xl border border-dashed border-border px-6 py-14 text-center') }}>
    <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
        <x-dynamic-component :component="'lucide-'.$icon" class="size-6" />
    </span>
    <p class="font-semibold text-foreground">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <p class="mt-1 max-w-md text-sm text-muted-foreground">{{ $slot }}</p>
    @endif
    @if ($action && $href)
        <a href="{{ $href }}" class="mt-5 rounded-md border border-border px-4 py-2 text-sm font-medium hover:border-primary hover:text-primary">{{ $action }}</a>
    @endif
</div>

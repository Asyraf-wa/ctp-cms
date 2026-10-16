{{-- Light → Dark → System (PRD PUB-11). --}}
<button type="button"
    x-data
    @click="$store.theme.cycle()"
    :aria-label="'Theme: ' + $store.theme.mode + '. Click to change.'"
    :title="'Theme: ' + $store.theme.mode"
    {{ $attributes->class('inline-flex size-9 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:border-line-strong hover:bg-accent hover:text-foreground') }}>
    <x-lucide-sun class="size-[18px]" x-show="$store.theme.mode === 'light'" />
    <x-lucide-moon class="size-[18px]" x-show="$store.theme.mode === 'dark'" x-cloak />
    <x-lucide-monitor class="size-[18px]" x-show="$store.theme.mode === 'system'" x-cloak />
</button>

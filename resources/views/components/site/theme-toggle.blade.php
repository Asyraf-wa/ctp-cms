{{-- Light → Dark → System (PRD PUB-11). Styled like the template's toggle. --}}
<button type="button"
    x-data
    @click="$store.theme.cycle()"
    aria-label="Change colour theme"
    :aria-label="'Theme: ' + $store.theme.mode + '. Click to change.'"
    :title="'Theme: ' + $store.theme.mode"
    {{ $attributes->class('rounded-lg p-2 text-sm text-muted-foreground transition-colors hover:bg-accent focus:ring-4 focus:ring-line-strong focus:outline-none') }}>
    {{-- The icon shows the current mode; the title says which one. --}}
    <x-lucide-sun class="size-5" x-show="$store.theme.mode === 'light'" />
    <x-lucide-moon class="size-5" x-show="$store.theme.mode === 'dark'" x-cloak />
    <x-lucide-monitor class="size-5" x-show="$store.theme.mode === 'system'" x-cloak />
</button>

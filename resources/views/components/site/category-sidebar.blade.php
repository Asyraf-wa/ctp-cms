{{--
    Category accordion (left sidebar of the template).
    $categories: [['name', 'icon' (lucide), 'noun' (?string, default tutorials), 'technology' (?Technology, preferred), 'url', 'count', 'open' => bool, 'items' => [['title', 'url', 'active' => bool]]]]
--}}
@props(['categories' => []])
<h3 class="mb-4 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Categories</h3>

<div class="space-y-2">
    @foreach ($categories as $category)
        <div x-data="{ open: @js($category['open'] ?? false) }" class="mb-2 border-b border-border/70 pb-2">
            <button type="button" @click="open = !open" :aria-expanded="open"
                class="group flex w-full items-center justify-between py-1.5 font-medium text-foreground transition-colors hover:text-primary">
                <span class="flex items-center gap-2">
                    @if ($category['technology'] ?? null)
                        <x-site.tech-icon :technology="$category['technology']" />
                    @else
                        <x-dynamic-component :component="'lucide-'.$category['icon']" class="size-4 text-muted-foreground transition-colors group-hover:text-primary" />
                    @endif
                    {{ $category['name'] }}
                </span>
                <x-lucide-chevron-down class="size-4 text-muted-foreground transition-transform duration-300" ::class="open && 'rotate-180'" />
            </button>
            <div x-show="open" x-collapse @if (! ($category['open'] ?? false)) x-cloak @endif>
                <ul class="ml-2 space-y-2 border-l border-border py-2 pl-4">
                    @foreach ($category['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}" @if ($item['active'] ?? false) aria-current="page" @endif
                                @class([
                                    'text-sm transition-colors',
                                    'font-medium text-primary' => $item['active'] ?? false,
                                    'text-muted-foreground hover:text-foreground' => ! ($item['active'] ?? false),
                                ])>{{ $item['title'] }}</a>
                        </li>
                    @endforeach
                    <li class="mt-2 border-t border-border pt-2">
                        <a href="{{ $category['url'] }}" class="flex items-center gap-1 text-[11px] font-bold tracking-wider text-foreground uppercase transition-colors hover:text-primary">
                            View all {{ $category['count'] }} {{ $category['noun'] ?? 'tutorials' }} <x-lucide-chevron-right class="size-3" />
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    @endforeach
</div>

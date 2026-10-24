@inject('general', 'App\Settings\GeneralSettings')
@inject('social', 'App\Settings\SocialSettings')
@inject('scripts', 'App\Settings\ScriptSettings')
@php
    // Footer columns come from the footer_1..3 menus (MENU-01); empty menus are skipped.
    $columns = collect(['footer_1', 'footer_2', 'footer_3'])
        ->mapWithKeys(fn (string $location) => [$location => \App\Models\Menu::tree($location)])
        ->filter();
    $legal = \App\Models\Menu::tree('legal');
    $gridCols = ['md:grid-cols-2', 'md:grid-cols-3', 'md:grid-cols-4', 'md:grid-cols-5'][$columns->count()];
@endphp

<footer class="mt-12 border-t border-border bg-card transition-colors duration-300">
    <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 {{ $gridCols }} lg:gap-12">
            <div class="col-span-1 md:col-span-2">
                <div class="mb-4 flex items-center gap-2">
                    <x-brand.mark />
                    <span class="font-brand text-2xl font-bold text-foreground">{{ $general->site_name }}</span>
                </div>
                @if ($general->slogan)
                    <p class="mb-2 text-sm font-medium text-foreground">{{ $general->slogan }}</p>
                @endif
                <p class="max-w-sm text-sm leading-relaxed text-muted-foreground">{{ $general->description }}</p>
            </div>

            @foreach ($columns as $location => $items)
                <div>
                    <h2 class="mb-4 font-semibold text-foreground">{{ \App\Models\Menu::heading($location) }}</h2>
                    <ul class="space-y-3">
                        @foreach ($items as $item)
                            <li><x-site.nav-link :item="$item" class="text-sm text-muted-foreground transition-colors hover:text-primary" /></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-border pt-8 md:flex-row">
            <div class="flex flex-col items-center gap-2 md:flex-row md:gap-6">
                <p class="text-sm text-muted-foreground">&copy; {{ now()->year }} {{ $general->copyright ?: $general->site_name }}. All rights reserved.</p>
                @if ($legal || $scripts->cookie_notice)
                    <nav aria-label="Legal" class="flex flex-wrap justify-center gap-x-4 gap-y-1">
                        @foreach ($legal as $item)
                            <x-site.nav-link :item="$item" class="text-sm text-muted-foreground transition-colors hover:text-primary" />
                        @endforeach
                        @if ($scripts->cookie_notice)
                            <button type="button" x-data @click="$dispatch('open-consent')" class="py-1 text-sm text-muted-foreground transition-colors hover:text-primary">Cookie settings</button>
                        @endif
                    </nav>
                @endif
            </div>
            <div class="flex flex-wrap justify-center gap-x-4 gap-y-3 sm:justify-end">
                @foreach ($social->links() as $network => $url)
                    <a href="{{ $url }}" rel="me noopener" target="_blank" title="{{ \App\Settings\SocialSettings::NETWORKS[$network] }}" class="text-muted-foreground transition-colors hover:text-foreground">
                        <span class="sr-only">{{ \App\Settings\SocialSettings::NETWORKS[$network] }}</span>
                        <x-site.social-icon :network="$network" class="size-5" />
                    </a>
                @endforeach
                <a href="{{ url('/feed') }}" class="text-muted-foreground transition-colors hover:text-foreground">
                    <span class="sr-only">RSS feed</span><x-lucide-rss class="size-5" />
                </a>
            </div>
        </div>
    </div>
</footer>

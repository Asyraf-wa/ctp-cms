@inject('general', 'App\Settings\GeneralSettings')
@inject('social', 'App\Settings\SocialSettings')
@php
    // Temporary until menu management (MENU, T1.07) supplies the footer menus.
    $columns = [
        'Platform' => [['All Tutorials', url('/tutorials')], ['Series', url('/series')], ['Blog', url('/blog')], ['Projects', url('/projects')]],
        'Company' => [['About', url('/about')], ['Contact', url('/contact')], ['Privacy Policy', url('/privacy')], ['Terms of Service', url('/terms')]],
    ];
@endphp

<footer class="mt-12 border-t border-border bg-card transition-colors duration-300">
    <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-4 lg:gap-12">
            <div class="col-span-1 md:col-span-2">
                <div class="mb-4 flex items-center gap-2">
                    <x-brand.mark class="size-8" />
                    <span class="text-2xl font-bold tracking-tight text-foreground">{{ $general->site_name }}</span>
                </div>
                @if ($general->slogan)
                    <p class="mb-2 text-sm font-medium text-foreground">{{ $general->slogan }}</p>
                @endif
                <p class="max-w-sm text-sm leading-relaxed text-muted-foreground">{{ $general->description }}</p>
            </div>

            @foreach ($columns as $heading => $links)
                <div>
                    <h2 class="mb-4 font-semibold text-foreground">{{ $heading }}</h2>
                    <ul class="space-y-3">
                        @foreach ($links as [$label, $url])
                            <li><a href="{{ $url }}" class="text-sm text-muted-foreground transition-colors hover:text-primary">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-border pt-8 md:flex-row">
            <p class="text-sm text-muted-foreground">&copy; {{ now()->year }} {{ $general->copyright ?: $general->site_name }}. All rights reserved.</p>
            <div class="flex gap-4">
                @foreach ($social->links() as $network => $url)
                    <a href="{{ $url }}" rel="me noopener" target="_blank" class="text-muted-foreground transition-colors hover:text-foreground">
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

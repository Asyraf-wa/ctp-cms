@php
    // Temporary until menu management (MENU, T1.07) and settings (SET-01, SET-05) exist.
    $columns = [
        'Learn' => [['Tutorials', url('/tutorials')], ['Series', url('/series')], ['Blog', url('/blog')]],
        'CTP' => [['Projects', url('/projects')], ['About', url('/about')], ['Contact', url('/contact')]],
        'More' => [['RSS feed', url('/feed')], ['Privacy', url('/privacy')], ['Terms', url('/terms')]],
    ];
@endphp

<footer class="mt-16 border-t border-border bg-card">
    <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-5">
            <div class="md:col-span-2">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                    <x-brand.mark class="size-8" />
                    <span class="text-lg font-bold tracking-tight">{{ config('app.name') }}</span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-muted-foreground">
                    Code The Experiences. Practical tutorials from things I explore and build.
                </p>
            </div>

            @foreach ($columns as $heading => $links)
                <div>
                    <h2 class="text-xs font-semibold tracking-wider text-foreground uppercase">{{ $heading }}</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($links as [$label, $url])
                            <li><a href="{{ $url }}" class="text-sm text-muted-foreground transition-colors hover:text-primary">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-border pt-8 sm:flex-row">
            <p class="text-sm text-muted-foreground">&copy; {{ now()->year }} Code The Pixel by Asyraf Wahi Anuar</p>
            <div class="flex items-center gap-1">
                <a href="https://github.com/Asyraf-wa" rel="me noopener" target="_blank" aria-label="GitHub"
                    class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground">
                    <x-lucide-github class="size-[18px]" />
                </a>
                <a href="{{ url('/feed') }}" aria-label="RSS feed"
                    class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground">
                    <x-lucide-rss class="size-[18px]" />
                </a>
            </div>
        </div>
    </div>
</footer>

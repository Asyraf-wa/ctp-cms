@props(['title' => null, 'description' => null, 'robots' => null, 'meta' => null])
@inject('scripts', 'App\Settings\ScriptSettings')
@inject('notice', 'App\Settings\NoticeSettings')
@php
    // Pages either pass a full Meta (App\Seo\Seo) or just a title/description/robots.
    $meta ??= app(\App\Seo\Seo::class)->basic($title, $description, $robots);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo.head :meta="$meta" />

    {{-- Before first paint: theme (PUB-11) and a dismissed topbar notice (SET-03), so neither flashes. --}}
    <script>
        (() => {
            const root = document.documentElement;
            let mode = 'system';
            try {
                mode = localStorage.getItem('theme') ?? 'system';
                if (localStorage.getItem('ctp-notice') === @js($notice->hash())) root.dataset.noticeDismissed = '';
                if (localStorage.getItem('ctp-focus') === '1') root.classList.add('focus-mode');
            } catch {}
            root.classList.toggle('dark', mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches));
        })();
    </script>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#f1f5f9" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0a1120" media="(prefers-color-scheme: dark)">

    {{-- Inter and the site-name font, which every page needs: fetched alongside the CSS instead of after it, so the swap happens before first paint (no layout shift). --}}
    @unless (\Illuminate\Support\Facades\Vite::isRunningHot())
        @foreach (['node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2', 'node_modules/@fontsource/titillium-web/files/titillium-web-latin-700-normal.woff2'] as $font)
            <link rel="preload" href="{{ \Illuminate\Support\Facades\Vite::asset($font) }}" as="font" type="font/woff2" crossorigin>
        @endforeach
    @endunless
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
    {!! $scripts->head_scripts !!}
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only z-50 rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

    {{ $banner ?? '' }}
    <x-site.notice />
    <x-site.header />
    <x-site.ribbon />

    <main id="main" tabindex="-1" {{ $attributes->class('flex-1 focus:outline-none') }}>
        {{ $slot }}
    </main>

    <x-site.footer />
    <x-site.search-palette />

    {{-- Back to top (PUB-05) --}}
    <button type="button" x-data="{ show: false }" @scroll.window.passive="show = window.scrollY > 900"
        x-show="show" x-cloak x-transition.opacity @click="window.scrollTo({ top: 0, behavior: 'smooth' }); document.getElementById('main')?.focus({ preventScroll: true })"
        class="fixed right-4 bottom-4 z-30 inline-flex size-11 items-center justify-center rounded-full border border-border bg-card text-muted-foreground shadow-md transition-colors hover:border-primary hover:text-primary sm:right-6 sm:bottom-6"
        aria-label="Back to top">
        <x-lucide-arrow-up class="size-5" />
    </button>
    {!! $scripts->body_scripts !!}
</body>
</html>

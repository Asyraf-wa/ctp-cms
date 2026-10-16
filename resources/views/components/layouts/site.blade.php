@props(['title' => null, 'description' => null, 'robots' => null])
@inject('general', 'App\Settings\GeneralSettings')
@inject('seo', 'App\Settings\SeoSettings')
@inject('scripts', 'App\Settings\ScriptSettings')
@inject('notice', 'App\Settings\NoticeSettings')
@php
    $pageTitle = $title
        ? strtr($seo->title_format, ['{page}' => $title, '{site}' => $general->site_name])
        : ($seo->meta_title ?: $general->site_name);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description ?: $seo->meta_description }}">
    <meta name="robots" content="{{ $robots ?: $seo->default_robots }}">
    @if ($seo->meta_keywords)
        <meta name="keywords" content="{{ $seo->meta_keywords }}">
    @endif
    @if ($seo->meta_author)
        <meta name="author" content="{{ $seo->meta_author }}">
    @endif
    @if ($seo->meta_copyright)
        <meta name="copyright" content="{{ $seo->meta_copyright }}">
    @endif
    @if ($seo->google_verification)
        <meta name="google-site-verification" content="{{ $seo->google_verification }}">
    @endif
    @if ($seo->bing_verification)
        <meta name="msvalidate.01" content="{{ $seo->bing_verification }}">
    @endif

    {{-- Before first paint: theme (PUB-11) and a dismissed topbar notice (SET-03), so neither flashes. --}}
    <script>
        (() => {
            const root = document.documentElement;
            let mode = 'system';
            try {
                mode = localStorage.getItem('theme') ?? 'system';
                if (localStorage.getItem('ctp-notice') === @js($notice->hash())) root.dataset.noticeDismissed = '';
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
    {!! $scripts->head_scripts !!}
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only z-50 rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

    <x-site.notice />
    <x-site.header />
    <x-site.ribbon />

    <main id="main" {{ $attributes->class('flex-1') }}>
        {{ $slot }}
    </main>

    <x-site.footer />
    {!! $scripts->body_scripts !!}
</body>
</html>

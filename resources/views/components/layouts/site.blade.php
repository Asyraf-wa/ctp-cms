@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    {{-- Set the theme before first paint so there is no flash (PUB-11). --}}
    <script>
        (() => {
            let mode = 'system';
            try { mode = localStorage.getItem('theme') ?? 'system'; } catch {}
            const dark = mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
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
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only z-50 rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

    <x-site.header />

    <main id="main" {{ $attributes->class('flex-1') }}>
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>

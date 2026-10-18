{{--
    PUB-12: 500 and maintenance. Self-contained on purpose: no database, settings or built assets,
    so it renders even when those are what failed.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <style>
        :root { --bg: #f1f5f9; --card: #fff; --fg: #0f172a; --muted: #475569; --border: #e2e8f0; --primary: #0f766e; color-scheme: light dark; }
        @media (prefers-color-scheme: dark) { :root { --bg: #0a1120; --card: #111a2e; --fg: #e2e8f0; --muted: #94a3b8; --border: #223049; --primary: #2dd4bf; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: var(--bg); color: var(--fg);
            font: 16px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { width: 100%; max-width: 32rem; padding: 2.5rem 2rem; text-align: center; background: var(--card); border: 1px solid var(--border); border-radius: 14px; }
        .mark { display: inline-flex; width: 3rem; height: 3rem; align-items: center; justify-content: center; border-radius: 10px; background: var(--primary); color: #fff; font: 700 1.1rem ui-monospace, monospace; }
        .code { margin: 1.25rem 0 0; color: var(--primary); font: 600 .875rem ui-monospace, monospace; }
        h1 { margin: .5rem 0 0; font-size: 1.75rem; line-height: 1.25; }
        p { color: var(--muted); }
        a { display: inline-block; margin-top: .5rem; padding: .6rem 1.25rem; border-radius: 999px; background: var(--primary); color: #fff; font-weight: 600; text-decoration: none; }
        @media (prefers-color-scheme: dark) { a, .mark { color: #042f2e; } }
    </style>
</head>
<body>
    <main>
        <span class="mark" aria-hidden="true">&lt;/&gt;</span>
        <p class="code">@yield('code')</p>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        @hasSection('action')
            @yield('action')
        @endif
    </main>
</body>
</html>

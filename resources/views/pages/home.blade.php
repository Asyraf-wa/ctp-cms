{{-- Temporary home: shows the layout and tokens until the real home page (PUB-01, T2.01). --}}
<x-layouts.site description="Practical web development tutorials from things I explore and build.">
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_50%_at_20%_0%,rgb(20_184_166/0.14),transparent),radial-gradient(40%_40%_at_90%_10%,rgb(17_94_89/0.12),transparent)]"></div>
        <div class="relative mx-auto max-w-[1440px] px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <p class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1 text-xs font-medium text-muted-foreground">
                <span class="size-1.5 rounded-full bg-primary"></span> Rebuilding CTP
            </p>
            <h1 class="mt-6 max-w-3xl text-4xl font-bold text-foreground sm:text-5xl lg:text-6xl">
                Code The <span class="text-primary">Experiences</span>.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-muted-foreground">
                Practical tutorials on CakePHP, Laravel, Joomla and the tools I use every day, written from things I explore and build.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ url('/tutorials') }}" class="inline-flex h-11 items-center gap-2 rounded-md bg-primary px-5 font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary-hover">
                    Browse tutorials <x-lucide-arrow-right class="size-4" />
                </a>
                <button type="button" @click="$dispatch('open-search')" class="inline-flex h-11 items-center gap-2 rounded-md border border-line-strong bg-card px-5 font-medium text-foreground transition-colors hover:bg-accent">
                    <x-lucide-search class="size-4" /> Search
                </button>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([['CakePHP', 'beginner', 'CakePHP 5 Installation and Configuration'], ['Laravel', 'intermediate', 'Google-only Admin Login with Filament 5'], ['Tools & Software', 'advanced', 'Deploying Laravel to cPanel with Git']] as [$cat, $level, $title])
                <article class="group rounded-xl border border-border bg-card p-6 shadow-xs transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-center gap-2 text-xs font-medium">
                        <span class="rounded-xs bg-brand-soft px-2 py-0.5 text-brand-soft-foreground">{{ $cat }}</span>
                        <span @class(['capitalize', 'text-beginner' => $level === 'beginner', 'text-intermediate' => $level === 'intermediate', 'text-advanced' => $level === 'advanced'])>● {{ $level }}</span>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-foreground group-hover:text-primary">{{ $title }}</h2>
                    <p class="mt-2 line-clamp-2 text-sm text-muted-foreground">Sample card to check spacing, radius, elevation and both themes before the real content arrives.</p>
                    <p class="mt-4 text-xs text-muted-foreground">Oct 16, 2026 · 6 min read</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mx-auto mt-12 max-w-[1440px] px-4 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-border bg-card p-6 shadow-xs sm:p-10">
            <div class="prose-ctp mx-auto max-w-[72ch]">
                <h2>Typography preview</h2>
                <p>This is how article text will read. It has <a href="#">a link</a>, some <strong>bold text</strong> and an inline <code>php artisan migrate</code> command.</p>
                <h3>A code block</h3>
                <pre><code>Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    -&gt;name('auth.google.callback');</code></pre>
                <blockquote>Convention over configuration: plural table names map to singular entities.</blockquote>
                <ul>
                    <li>Light, dark and system themes</li>
                    <li>Self-hosted Inter and JetBrains Mono</li>
                </ul>
            </div>
        </div>
    </section>
</x-layouts.site>

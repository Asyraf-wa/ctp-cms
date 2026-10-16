{{-- Blog index (PUB-08): single column, journal feel, no sidebars. --}}
<x-layouts.site :meta="$meta">
    <div class="mx-auto max-w-3xl px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header title="Blog" :breadcrumbs="$meta->breadcrumbs" description="Notes, opinions and project updates. Tutorials live in their own section." />

        @forelse ($posts as $post)
            <article class="group relative border-b border-border py-8 first:pt-2 last:border-b-0">
                <p class="text-sm text-muted-foreground">
                    <time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->published_at->timezone(config('ctp.display_timezone'))->format('F j, Y') }}</time>
                    · {{ $post->readingMinutes() }} min read
                </p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-foreground">
                    <a href="{{ url($post->publicPath()) }}" class="after:absolute after:inset-0 group-hover:text-primary">{{ $post->title }}</a>
                </h2>
                @if ($post->excerpt)
                    <p class="mt-3 text-lg leading-relaxed text-muted-foreground">{{ $post->excerpt }}</p>
                @endif
                <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary">Read post <x-lucide-arrow-right class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
            </article>
        @empty
            <x-site.empty icon="pen-line" title="No posts yet" action="Browse tutorials" :href="url('/tutorials')">The first post is being written.</x-site.empty>
        @endforelse

        {{ $posts->links('site.partials.pagination') }}
    </div>
</x-layouts.site>

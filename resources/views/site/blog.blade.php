{{-- Blog index (PUB-08): the tutorial layout (Blog is a section of the left sidebar), posts as a plain list. --}}
<x-layouts.tutorial :meta="$meta">
    <x-slot:left>
        <x-site.category-sidebar :categories="$sidebar" />
    </x-slot:left>

    <x-site.page-header title="Blog" icon="pen-line" :breadcrumbs="$meta->breadcrumbs" description="Notes, opinions and project updates. Tutorials live in their own section." />

    @forelse ($posts as $post)
        <article class="group relative flex gap-4 border-b border-border py-6 first:pt-0 last:border-b-0">
            <div class="min-w-0 flex-1">
                <p class="text-xs text-muted-foreground">
                    <time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->published_at->timezone(config('ctp.display_timezone'))->format('M j, Y') }}</time>
                    · {{ $post->readingMinutes() }} min read
                </p>
                <h2 class="mt-1 text-xl font-semibold text-foreground">
                    <a href="{{ url($post->publicPath()) }}" class="after:absolute after:inset-0 group-hover:text-primary focus:outline-none focus-visible:underline">{{ $post->title }}</a>
                </h2>
                @if ($post->excerpt)
                    <p class="mt-2 line-clamp-3 text-muted-foreground">{{ $post->excerpt }}</p>
                @endif
            </div>
            <x-lucide-chevron-right class="mt-7 size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary" />
        </article>
    @empty
        <x-site.empty icon="pen-line" title="No posts yet" action="Browse tutorials" :href="url('/tutorials')">The first post is being written.</x-site.empty>
    @endforelse

    {{ $posts->links('site.partials.pagination') }}
</x-layouts.tutorial>

{{-- Blog index (PUB-08): same layout and cards as a category archive; Blog is a section of the Browse sidebar. --}}
<x-layouts.tutorial :meta="$meta">
    <x-slot:left>
        <x-site.category-sidebar :categories="$sidebar" />
    </x-slot:left>

    <x-site.page-header title="Blog" eyebrow="Section" icon="pen-line" :breadcrumbs="$meta->breadcrumbs"
        description="Notes, opinions and project updates. Tutorials live in their own section.">
        <p class="mt-2 text-sm text-muted-foreground">{{ number_format($posts->total()) }} {{ Str::plural('post', $posts->total()) }}</p>
    </x-site.page-header>

    @if ($posts->isNotEmpty())
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($posts as $post)
                <x-card.article :article="$post" :eager="$loop->first" heading="h2" />
            @endforeach
        </div>
        {{ $posts->links('site.partials.pagination') }}
    @else
        <x-site.empty icon="pen-line" title="No posts yet" action="Browse tutorials" :href="url('/tutorials')">The first post is being written.</x-site.empty>
    @endif
</x-layouts.tutorial>

{{-- Category archive (PUB-07): the tutorial layout with the category accordion opened on this category. --}}
<x-layouts.tutorial :meta="$meta">
    <x-slot:left>
        <x-site.category-sidebar :categories="$sidebar" />
    </x-slot:left>

    <x-site.page-header :title="$category->name" eyebrow="Category" :icon="$category->icon ?: 'folder'" :technology="$category->technology" :breadcrumbs="$meta->breadcrumbs"
        :description="$category->description">
        <p class="mt-2 text-sm text-muted-foreground">{{ number_format($articles->total()) }} {{ Str::plural('tutorial', $articles->total()) }}</p>
    </x-site.page-header>

    @if ($category->children->isNotEmpty())
        <div class="mb-6 flex flex-wrap gap-2">
            @foreach ($category->children as $child)
                <a href="{{ url($child->publicPath()) }}" class="rounded-full border border-border bg-card px-3 py-1 text-sm font-medium text-muted-foreground hover:border-primary hover:text-primary">{{ $child->name }}</a>
            @endforeach
        </div>
    @endif

    @if ($articles->isNotEmpty())
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($articles as $article)
                <x-card.article :article="$article" :eager="$loop->first" heading="h2" />
            @endforeach
        </div>
        {{ $articles->links('site.partials.pagination') }}
    @else
        <x-site.empty icon="folder-open" title="Nothing here yet" action="Browse all tutorials" :href="url('/tutorials')">Tutorials in {{ $category->name }} will show up here.</x-site.empty>
    @endif
</x-layouts.tutorial>

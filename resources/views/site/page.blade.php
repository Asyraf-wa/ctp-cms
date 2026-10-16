{{-- Static page (PUB-10). Template variants are styled in Phase 2 (T2.11). --}}
<x-layouts.site
    :title="$page->seo?->title ?: $page->title"
    :description="$page->seo?->description ?: $page->excerpt"
    :robots="$preview ? 'noindex, nofollow' : $page->seo?->robots">
    @if ($preview)
        <x-slot:banner>
            <div class="sticky top-0 z-[60] bg-[#854d0e] px-4 py-2 text-center text-sm font-medium text-white">
                Preview · {{ $page->is_published ? 'Published' : 'Unpublished' }} · This page is not public and not indexed.
            </div>
        </x-slot:banner>
    @endif

    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <article @class([
            'mx-auto rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-10',
            'max-w-4xl' => $page->template !== 'wide',
            'max-w-6xl' => $page->template === 'wide',
        ])>
            <div class="prose-ctp">
                <h1>{{ $page->title }}</h1>
                @if ($page->excerpt)
                    <p class="lead">{{ $page->excerpt }}</p>
                @endif
                {!! $page->body_rendered !!}
            </div>
        </article>
    </div>
</x-layouts.site>

{{-- Static page (PUB-10). Styled properly in Phase 2 (T2.11). --}}
<x-layouts.site :title="$page->title">
    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <article class="mx-auto max-w-4xl rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-10">
            <div class="prose-ctp">
                <h1>{{ $page->title }}</h1>
                {!! $body !!}
            </div>
        </article>
    </div>
</x-layouts.site>

{{--
    Static page (PUB-10). Templates: default (reading column), toc (long read with "On this page"),
    about (author card first, for E-E-A-T) and wide.
--}}
@inject('general', 'App\Settings\GeneralSettings')
@php
    $author = $page->template === 'about'
        ? \App\Models\User::find($general->default_author_id) ?? \App\Models\User::query()->oldest('id')->first()
        : null;
    $withToc = $page->template === 'toc' && $page->toc;
@endphp
<x-layouts.site :meta="$meta">
    @if ($preview)
        <x-slot:banner>
            <div class="sticky top-0 z-[60] bg-[#854d0e] px-4 py-2 text-center text-sm font-medium text-white">
                Preview · {{ $page->is_published ? 'Published' : 'Unpublished' }} · This page is not public and not indexed.
            </div>
        </x-slot:banner>
    @endif

    <div class="mx-auto flex max-w-[1440px] gap-8 px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <article @class([
            'mx-auto min-w-0 flex-1 rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-10',
            'max-w-4xl' => ! in_array($page->template, ['wide'], true),
            'max-w-6xl' => $page->template === 'wide',
        ])>
            <x-site.breadcrumbs :items="$meta->breadcrumbs" class="mb-6" />
            <h1 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">{{ $page->title }}</h1>
            @if ($page->excerpt)
                <p class="mt-4 text-lg leading-relaxed text-muted-foreground">{{ $page->excerpt }}</p>
            @endif
            @if ($withToc)
                <p class="mt-3 text-sm text-muted-foreground">Last updated <time datetime="{{ $page->updated_at->toAtomString() }}">{{ $page->updated_at->timezone(config('ctp.display_timezone'))->format('F j, Y') }}</time></p>
            @endif

            @if ($author)
                <div class="mt-8 flex flex-col gap-5 rounded-xl border border-border bg-surface-2 p-5 sm:flex-row sm:items-center">
                    @if ($author->avatarUrl())
                        <img src="{{ $author->avatarUrl() }}" alt="" width="80" height="80" class="size-20 shrink-0 rounded-full object-cover" referrerpolicy="no-referrer">
                    @endif
                    <div class="min-w-0">
                        <a href="{{ url($author->publicPath() ?? '/') }}" class="text-lg font-semibold text-foreground hover:text-primary">{{ $author->authorName() }}</a>
                        @if ($author->job_title)
                            <p class="text-sm font-medium text-primary">{{ $author->job_title }}</p>
                        @endif
                        @if ($author->bio_short)
                            <p class="mt-2 text-sm text-muted-foreground">{{ $author->bio_short }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="prose-ctp mt-8">
                {!! $page->body_rendered !!}
            </div>
        </article>

        @if ($withToc)
            <aside class="hidden w-64 shrink-0 xl:block" aria-label="On this page">
                <div class="sticky top-28"><x-site.toc :items="$page->toc" /></div>
            </aside>
        @endif
    </div>
</x-layouts.site>

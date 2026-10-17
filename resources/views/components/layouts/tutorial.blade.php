{{--
    3-column tutorial layout from tutorial_web_template.html: categories · article · "On this page".
    Focus mode (PUB-05) hides both sidebars; the header button only appears on pages using this layout.
--}}
@props(['title' => null, 'description' => null, 'robots' => null, 'meta' => null])
<x-layouts.site :title="$title" :description="$description" :robots="$robots" :meta="$meta">
    @isset($head)
        <x-slot:head>{{ $head }}</x-slot:head>
    @endisset
    @isset($banner)
        <x-slot:banner>{{ $banner }}</x-slot:banner>
    @endisset
    {{-- Reading progress (PUB-05): filled by app.js from the [data-article] element. --}}
    <div class="pointer-events-none fixed inset-x-0 top-0 z-[70] h-0.5" aria-hidden="true">
        <div class="h-full origin-left scale-x-0 bg-primary" data-reading-progress></div>
    </div>
    <div class="mx-auto flex max-w-[1440px] flex-col gap-8 px-4 pt-8 pb-12 sm:px-6 lg:flex-row lg:px-8" x-data x-init="$store.focus.available = true">
        @isset($left)
            {{-- Below lg the same sidebar sits in the header's menu drawer. --}}
            @push('mobile-sidebar')
                {{ $left }}
            @endpush
            <aside class="hidden w-full shrink-0 lg:block lg:w-64" aria-label="Categories" data-sidebar>
                <div class="no-scrollbar sticky top-28 h-[calc(100vh-8rem)] overflow-y-auto pr-4 pb-8">
                    {{ $left }}
                </div>
            </aside>
        @endisset

        <div class="min-w-0 flex-1" data-focus-main>
            {{ $slot }}
        </div>

        @isset($right)
            <aside class="hidden w-full shrink-0 xl:block xl:w-64" aria-label="On this page" data-sidebar>
                <div class="no-scrollbar sticky top-28 max-h-[calc(100vh-8rem)] overflow-y-auto pb-8">
                    {{ $right }}
                </div>
            </aside>
        @endisset
    </div>
</x-layouts.site>

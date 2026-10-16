{{-- 3-column tutorial layout from tutorial_web_template.html: categories · article · "On this page". --}}
@props(['title' => null, 'description' => null, 'robots' => null])
<x-layouts.site :title="$title" :description="$description" :robots="$robots">
    @isset($banner)
        <x-slot:banner>{{ $banner }}</x-slot:banner>
    @endisset
    <div class="mx-auto flex max-w-[1440px] flex-col gap-8 px-4 pt-8 pb-12 sm:px-6 lg:flex-row lg:px-8">
        @isset($left)
            <aside class="hidden w-full shrink-0 lg:block lg:w-64" aria-label="Categories">
                <div class="no-scrollbar sticky top-28 h-[calc(100vh-8rem)] overflow-y-auto pr-4 pb-8">
                    {{ $left }}
                </div>
            </aside>
        @endisset

        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>

        @isset($right)
            <aside class="hidden w-full shrink-0 xl:block xl:w-64" aria-label="On this page">
                <div class="sticky top-28">
                    {{ $right }}
                </div>
            </aside>
        @endisset
    </div>
</x-layouts.site>

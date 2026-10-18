{{-- A one-off confirmation page (unsubscribe, etc.). --}}
<x-layouts.site :title="$title" robots="noindex, nofollow">
    <div class="mx-auto max-w-xl px-4 py-20 text-center sm:px-6">
        <span class="mx-auto mb-5 flex size-14 items-center justify-center rounded-full bg-brand-soft text-primary"><x-lucide-check class="size-7" /></span>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ $heading }}</h1>
        <p class="mt-3 text-lg text-muted-foreground">{{ $message }}</p>
        @if (! empty($link))
            <a href="{{ $link[1] }}" class="mt-8 inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 font-semibold text-primary-foreground hover:bg-primary-hover">
                Back to {{ \Illuminate\Support\Str::limit($link[0], 50) }} <x-lucide-arrow-right class="size-4" />
            </a>
        @endif
    </div>
</x-layouts.site>

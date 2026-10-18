@props(['articles', 'title' => 'Related tutorials'])
@if ($articles->isNotEmpty())
    <section class="mt-12" aria-labelledby="related-heading">
        <h2 id="related-heading" class="mb-5 text-xl font-bold tracking-tight text-foreground">{{ $title }}</h2>
        <div class="grid gap-6 md:grid-cols-2 2xl:grid-cols-3">
            @foreach ($articles as $item)
                <x-card.article :article="$item" />
            @endforeach
        </div>
    </section>
@endif

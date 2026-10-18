{{-- ART-12: "This helped me" (legacy kudos carried over). The given state is per browser; the server enforces one per visitor. --}}
@props(['article', 'preview' => false])
<div class="not-prose mt-12 flex flex-col items-center gap-3 rounded-xl border border-border bg-surface-2 px-6 py-6 text-center sm:flex-row sm:justify-between sm:text-left"
    x-data="kudos(@js($preview ? null : route('api.articles.kudos', $article)), {{ (int) $article->kudos }}, {{ $article->id }})">
    <div>
        <p class="font-semibold text-foreground">Did this tutorial help?</p>
        <p class="text-sm text-muted-foreground" aria-live="polite">
            <span x-text="count.toLocaleString()">{{ number_format($article->kudos) }}</span>
            <span x-text="count === 1 ? 'reader found this helpful' : 'readers found this helpful'">{{ $article->kudos === 1 ? 'reader found this helpful' : 'readers found this helpful' }}</span>
        </p>
    </div>
    <button type="button" @click="give()" :disabled="given || busy" :aria-pressed="given ? 'true' : 'false'"
        class="inline-flex shrink-0 items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-semibold transition"
        :class="given ? 'border-primary bg-brand-soft text-brand-soft-foreground' : 'border-border bg-card text-foreground hover:-translate-y-0.5 hover:border-primary hover:text-primary motion-reduce:hover:translate-y-0'">
        <span aria-hidden="true" class="text-base" :class="pop && 'animate-bounce'">👏</span>
        <span x-text="given ? 'Thanks for the kudos!' : 'This helped me'">This helped me</span>
    </button>
</div>

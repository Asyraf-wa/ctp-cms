{{-- One comment (CMT-01). Dates are absolute: article pages are cached, so "3 days ago" would go stale. --}}
@props(['comment', 'replyable' => false])
<article id="{{ $comment->anchor() }}" class="scroll-mt-28" data-comment>
    <div class="flex gap-3 sm:gap-4">
        <img src="{{ $comment->avatarUrl() }}" alt="" width="40" height="40" loading="lazy" decoding="async" referrerpolicy="no-referrer"
            class="size-10 shrink-0 rounded-full border border-border bg-muted">
        <div class="min-w-0 flex-1">
            <header class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                <span class="font-semibold text-foreground">{{ $comment->name }}</span>
                @if ($comment->isByAuthor())
                    <span class="rounded-xs bg-brand-soft px-1.5 py-0.5 text-[11px] font-semibold tracking-wide text-brand-soft-foreground uppercase">Author</span>
                @endif
                <a href="#{{ $comment->anchor() }}" class="text-muted-foreground hover:text-primary">
                    <time datetime="{{ $comment->created_at->toAtomString() }}">{{ $comment->created_at->timezone(config('ctp.display_timezone'))->format('M j, Y · g:i a') }}</time>
                </a>
            </header>
            <div class="comment-body mt-1.5">{!! $comment->body_html !!}</div>
            @if ($replyable)
                <button type="button" class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-primary"
                    @click="$dispatch('comment-reply', { id: {{ $comment->id }}, name: @js($comment->name) })">
                    <x-lucide-reply class="size-4" /> Reply
                </button>
            @endif
        </div>
    </div>
</article>

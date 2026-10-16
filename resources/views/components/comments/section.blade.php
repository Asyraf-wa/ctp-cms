{{-- CMT-01..05: approved comments with one level of replies, and the form (when open). --}}
@props(['article', 'comments'])
@php($count = $comments->count() + $comments->sum(fn ($c) => $c->replies->count()))
@php($open = $article->commentsOpen())
<section id="comments" class="not-prose mt-12 scroll-mt-24 border-t border-border pt-10" aria-labelledby="comments-heading"
    x-data="commentForm(@js(route('api.articles.comments', $article)), @js(\App\Support\Spam\Turnstile::siteKey()))">
    <h2 id="comments-heading" class="flex items-center gap-2 text-xl font-bold tracking-tight text-foreground">
        <x-lucide-message-square class="size-5 text-primary" /> {{ $count ? $count.' '.Str::plural('comment', $count) : 'Comments' }}
    </h2>

    <div class="mt-6 space-y-6" data-comment-list>
        @foreach ($comments as $comment)
            <div class="space-y-5">
                <x-comments.item :comment="$comment" :replyable="$open" />
                @if ($comment->replies->isNotEmpty())
                    <div class="ml-6 space-y-5 border-l-2 border-border pl-4 sm:ml-14 sm:pl-6">
                        @foreach ($comment->replies as $reply)
                            <x-comments.item :comment="$reply" />
                        @endforeach
                    </div>
                @endif
                {{-- The reply form moves here when "Reply" is pressed. --}}
                <div data-reply-slot="{{ $comment->id }}" class="ml-6 sm:ml-14"></div>
            </div>
        @endforeach
        @if ($comments->isEmpty() && $open)
            <p class="text-muted-foreground" data-no-comments>No comments yet. Questions about this tutorial are welcome here: the answer helps the next reader too.</p>
        @endif
    </div>

    @if ($open)
        <div x-ref="home" class="mt-8">
            <form x-ref="form" @submit.prevent="submit" novalidate class="rounded-xl border border-border bg-surface-2 p-5 sm:p-6" aria-labelledby="comment-form-heading">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 id="comment-form-heading" class="font-semibold text-foreground" x-text="replyTo ? 'Reply to ' + replyTo.name : 'Leave a comment'">Leave a comment</h3>
                    <button type="button" x-show="replyTo" x-cloak @click="cancelReply" class="text-sm text-muted-foreground hover:text-primary">Cancel reply</button>
                </div>

                <div x-show="message" x-cloak role="status" aria-live="polite"
                    class="mb-4 rounded-md px-4 py-3 text-sm" :class="error ? 'bg-[#fee2e2] text-[#991b1b] dark:bg-[#7f1d1d]/40 dark:text-[#fecaca]' : 'bg-brand-soft text-brand-soft-foreground'" x-text="message"></div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="c-name" class="mb-1 block text-sm font-medium text-foreground">Name</label>
                        <input id="c-name" x-model="name" name="name" autocomplete="name" maxlength="80" required
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-ring/30 focus:outline-none" :aria-invalid="!!errors.name">
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-destructive"></p>
                    </div>
                    <div>
                        <label for="c-email" class="mb-1 block text-sm font-medium text-foreground">Email <span class="font-normal text-muted-foreground">(never shown)</span></label>
                        <input id="c-email" x-model="email" name="email" type="email" autocomplete="email" maxlength="190" required
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-ring/30 focus:outline-none" :aria-invalid="!!errors.email">
                        <p x-show="errors.email" x-text="errors.email" class="mt-1 text-xs text-destructive"></p>
                    </div>
                </div>
                <div class="mt-4">
                    <label for="c-body" class="mb-1 flex justify-between text-sm font-medium text-foreground">
                        <span>Comment</span>
                        <span class="font-normal text-muted-foreground tabular-nums" x-text="body.length + ' / 5000'">0 / 5000</span>
                    </label>
                    <textarea id="c-body" x-ref="body" x-model="body" name="body" rows="5" maxlength="5000" required
                        class="w-full rounded-md border border-border bg-card px-3 py-2 font-[inherit] text-sm focus:border-primary focus:ring-2 focus:ring-ring/30 focus:outline-none" :aria-invalid="!!errors.body"
                        aria-describedby="c-body-help"></textarea>
                    <p x-show="errors.body" x-text="errors.body" class="mt-1 text-xs text-destructive"></p>
                    <p id="c-body-help" class="mt-1 text-xs text-muted-foreground">Markdown works: <code class="rounded-xs bg-muted px-1">`code`</code>, <code class="rounded-xs bg-muted px-1">```</code> blocks, <code class="rounded-xs bg-muted px-1">**bold**</code>, links.</p>
                </div>

                {{-- Honeypot: hidden from people, filled by bots. --}}
                <div class="absolute -left-[9999px]" aria-hidden="true">
                    <label>Website <input type="text" name="website" x-model="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                        <input type="checkbox" x-model="notify" class="size-4 rounded border-border text-primary focus:ring-ring"> Email me when someone replies
                    </label>
                    <div x-ref="turnstile"></div>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <button type="submit" :disabled="busy" class="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary-hover disabled:opacity-60">
                        <x-lucide-loader-circle class="size-4 animate-spin" x-show="busy" x-cloak />
                        <span x-text="replyTo ? 'Post reply' : 'Post comment'">Post comment</span>
                    </button>
                    <p class="text-xs text-muted-foreground">First comments are reviewed before they appear.</p>
                </div>
                <noscript><p class="mt-3 text-sm text-destructive">Commenting needs JavaScript.</p></noscript>
            </form>
        </div>
    @else
        <p class="mt-6 rounded-md border border-border bg-surface-2 px-4 py-3 text-sm text-muted-foreground">Comments are closed for this article.@if ($comments->isEmpty()) Questions are welcome on the <a href="{{ url('/contact') }}" class="text-primary underline">contact page</a>.@endif</p>
    @endif
</section>

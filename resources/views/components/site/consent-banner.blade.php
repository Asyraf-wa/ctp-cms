{{-- Cookie notice: analytics snippets (Settings → Scripts) load only after Accept. Hidden until JS knows there is no saved choice. --}}
<section x-data="consent" x-show="open" x-cloak @open-consent.window="show()" @keydown.escape.window="open = false"
    aria-label="Cookie notice"
    class="fixed inset-x-4 bottom-4 z-[60] rounded-2xl border border-border bg-card p-5 shadow-lg sm:right-auto sm:max-w-md sm:p-6">
    <p class="text-sm leading-relaxed text-foreground">
        With your permission, this site uses Google Analytics cookies to count visits and see which tutorials are read.
        Nothing is set until you accept. <a href="{{ url('/privacy') }}" class="font-medium text-primary underline underline-offset-2">Privacy Policy</a>
    </p>
    <div class="mt-4 flex flex-wrap gap-3">
        <button type="button" @click="accept()" class="min-h-11 rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-hover focus:ring-4 focus:ring-line-strong focus:outline-none">Accept</button>
        <button type="button" @click="decline()" class="min-h-11 rounded-full border border-border bg-card px-5 text-sm font-medium text-foreground transition-colors hover:bg-accent focus:ring-4 focus:ring-line-strong focus:outline-none">Decline</button>
    </div>
</section>

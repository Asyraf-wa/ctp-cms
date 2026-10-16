{{-- Contact (CONTACT-01..05, DESIGN §5 Contact page). The form posts with fetch, so the page can be cached. --}}
@php
    $config = [
        'endpoint' => route('api.contact'),
        'search' => route('api.search'),
        'topic' => $topic,
        'topics' => collect($topics)->map(fn ($t) => ['company' => $t['company'] ?? false, 'article' => $t['article'] ?? false])->all(),
        'article' => $article ? ['id' => $article->id, 'title' => $article->title] : null,
        'turnstile' => $turnstileKey,
        'min' => \App\Http\Controllers\Site\ContactController::MIN_LENGTH,
        'max' => \App\Http\Controllers\Site\ContactController::MAX_LENGTH,
    ];
    $input = 'w-full rounded-md border bg-card px-3 py-2.5 text-sm text-foreground transition-colors placeholder:text-muted-foreground focus:ring-2 focus:ring-ring/30 focus:outline-none';
@endphp
<x-layouts.site :meta="$meta">
    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-16 sm:px-6 lg:px-8" x-data="contactForm(@js($config))">
        <x-site.breadcrumbs :items="[['Contact', null]]" class="mb-6" />

        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] lg:gap-14">
            {{-- Left: context --}}
            <div>
                <p class="text-sm font-semibold tracking-wide text-primary uppercase">Contact</p>
                <h1 class="mt-2 text-4xl font-bold tracking-tight text-balance text-foreground">Let’s talk</h1>
                @if ($settings->intro)
                    <p class="mt-4 max-w-xl text-lg leading-relaxed text-muted-foreground">{{ $settings->intro }}</p>
                @endif

                <h2 class="mt-10 mb-4 text-sm font-semibold tracking-wider text-foreground uppercase">What can I help with?</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($topics as $key => $item)
                        <button type="button" @click="pick(@js($key))" :aria-pressed="topic === @js($key)"
                            class="group flex items-start gap-3 rounded-xl border bg-card p-4 text-left shadow-xs transition hover:-translate-y-0.5 hover:shadow-md motion-reduce:hover:translate-y-0"
                            :class="topic === @js($key) ? 'border-primary ring-2 ring-primary/20' : 'border-border hover:border-primary/40'">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors"
                                :class="topic === @js($key) ? 'bg-primary text-primary-foreground' : 'bg-brand-soft text-primary'">
                                <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4.5" />
                            </span>
                            <span>
                                <span class="block font-semibold text-foreground">{{ $item['label'] }}</span>
                                <span class="block text-sm text-muted-foreground">{{ $item['line'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 text-sm">
                    <span class="inline-flex items-center gap-2 rounded-full bg-brand-soft px-3 py-1.5 font-medium text-brand-soft-foreground">
                        <x-lucide-clock class="size-4" /> Usually replies within {{ $settings->reply_time }}
                    </span>
                    @if ($email)
                        <span class="inline-flex items-center gap-2 rounded-full border border-border bg-card py-1 pr-1 pl-3" x-data="{ copied: false }">
                            <x-lucide-mail class="size-4 text-muted-foreground" />
                            <a href="mailto:{{ $email }}" class="font-medium text-foreground hover:text-primary">{{ $email }}</a>
                            <button type="button" @click="navigator.clipboard.writeText(@js($email)); copied = true; setTimeout(() => copied = false, 1500)"
                                class="rounded-full px-2 py-0.5 text-xs font-semibold text-muted-foreground hover:bg-accent hover:text-foreground" :aria-label="copied ? 'Copied' : 'Copy email address'">
                                <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                            </button>
                        </span>
                    @endif
                </div>

                @if ($socials)
                    <div class="mt-5 flex gap-3">
                        @foreach ($socials as $network => $url)
                            <a href="{{ $url }}" rel="me noopener" target="_blank" class="flex size-10 items-center justify-center rounded-full border border-border bg-card text-muted-foreground hover:border-primary hover:text-primary">
                                <x-site.social-icon :network="$network" class="size-4.5" /><span class="sr-only">{{ \App\Settings\SocialSettings::NETWORKS[$network] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                <aside class="callout callout--tip mt-8" role="note" aria-label="Before you ask">
                    <x-lucide-lightbulb class="callout__icon" aria-hidden="true" />
                    <div class="callout__content">
                        <p class="callout__title">Before you ask</p>
                        <p>Questions about a tutorial get the fastest answer in that tutorial’s comments, and the answer helps the next reader too. <a href="{{ url('/search') }}" class="font-semibold underline">Search the tutorials</a> first; it may already be covered.</p>
                    </div>
                </aside>
            </div>

            {{-- Right: the form, or the success screen --}}
            <div>
                <div x-show="!sent" class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                    <form @submit.prevent="submit" novalidate x-ref="form" aria-labelledby="contact-form-heading">
                        <h2 id="contact-form-heading" class="text-xl font-bold tracking-tight text-foreground">Send a message</h2>
                        <p class="mt-1 text-sm text-muted-foreground">All fields are required unless marked optional.</p>

                        {{-- Accessible error summary (CONTACT-03) --}}
                        <div x-show="summary.length" x-cloak x-ref="summary" tabindex="-1" role="alert"
                            class="mt-5 rounded-md border border-[#fca5a5] bg-[#fef2f2] px-4 py-3 text-sm text-[#991b1b] focus:outline-none dark:border-[#7f1d1d] dark:bg-[#7f1d1d]/30 dark:text-[#fecaca]">
                            <p class="font-semibold">Please fix the following:</p>
                            <ul class="mt-1 list-disc pl-5">
                                <template x-for="item in summary" :key="item.field">
                                    <li><a :href="'#f-' + item.field" @click.prevent="focusField(item.field)" class="underline" x-text="item.message"></a></li>
                                </template>
                            </ul>
                        </div>
                        <div x-show="serverError" x-cloak role="alert" class="mt-5 rounded-md bg-[#fef2f2] px-4 py-3 text-sm text-[#991b1b] dark:bg-[#7f1d1d]/30 dark:text-[#fecaca]" x-text="serverError"></div>

                        <fieldset class="mt-6">
                            <legend class="mb-2 text-sm font-medium text-foreground">Topic</legend>
                            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Topic">
                                @foreach ($topics as $key => $item)
                                    <button type="button" role="radio" :aria-checked="topic === @js($key)" @click="pick(@js($key), false)"
                                        class="rounded-full border px-3 py-1.5 text-sm font-medium transition-colors"
                                        :class="topic === @js($key) ? 'border-primary bg-primary text-primary-foreground' : 'border-border text-muted-foreground hover:border-primary hover:text-primary'">{{ $item['label'] }}</button>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="f-name" class="mb-1 block text-sm font-medium text-foreground">Name</label>
                                <input id="f-name" x-model="name" @blur="touch('name')" autocomplete="name" maxlength="80"
                                    class="{{ $input }}" :class="errors.name ? 'border-destructive' : 'border-border focus:border-primary'" :aria-invalid="!!errors.name" aria-describedby="e-name">
                                <p id="e-name" x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-destructive"></p>
                            </div>
                            <div>
                                <label for="f-email" class="mb-1 block text-sm font-medium text-foreground">Email</label>
                                <input id="f-email" x-model="email" @blur="touch('email')" type="email" autocomplete="email" maxlength="190"
                                    class="{{ $input }}" :class="errors.email ? 'border-destructive' : 'border-border focus:border-primary'" :aria-invalid="!!errors.email" aria-describedby="e-email">
                                <p id="e-email" x-show="errors.email" x-text="errors.email" class="mt-1 text-xs text-destructive"></p>
                            </div>
                        </div>

                        {{-- Fields that depend on the topic slide in (200 ms). --}}
                        <div x-show="shows('company')" x-collapse.duration.200ms x-cloak>
                            <div class="pt-4">
                                <label for="f-company" class="mb-1 block text-sm font-medium text-foreground">Company or organisation <span class="font-normal text-muted-foreground">(optional)</span></label>
                                <input id="f-company" x-model="company" autocomplete="organization" maxlength="120" class="{{ $input }} border-border focus:border-primary">
                            </div>
                        </div>
                        <div x-show="shows('article')" x-collapse.duration.200ms x-cloak>
                            <div class="relative pt-4" @click.outside="results = []">
                                <label for="f-article" class="mb-1 block text-sm font-medium text-foreground">Which tutorial? <span class="font-normal text-muted-foreground">(optional)</span></label>
                                <template x-if="article">
                                    <div class="flex items-center justify-between gap-3 rounded-md border border-primary/40 bg-brand-soft px-3 py-2.5 text-sm">
                                        <span class="font-medium text-brand-soft-foreground" x-text="article.title"></span>
                                        <button type="button" @click="article = null; $nextTick(() => $refs.articleInput?.focus())" class="text-xs font-semibold text-muted-foreground hover:text-foreground">Change</button>
                                    </div>
                                </template>
                                <template x-if="!article">
                                    <div>
                                        <input id="f-article" x-ref="articleInput" x-model="articleQuery" @input.debounce.200ms="findArticles" @keydown.escape="results = []"
                                            placeholder="Start typing a tutorial title…" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="article-results" :aria-expanded="results.length > 0"
                                            class="{{ $input }} border-border focus:border-primary">
                                        <ul id="article-results" x-show="results.length" x-cloak role="listbox"
                                            class="absolute inset-x-0 z-20 mt-1 max-h-64 overflow-y-auto rounded-md border border-border bg-card p-1 shadow-lg">
                                            <template x-for="item in results" :key="item.id">
                                                <li role="option"><button type="button" @click="article = { id: item.id, title: item.title }; results = []" class="w-full rounded-sm px-3 py-2 text-left text-sm hover:bg-accent">
                                                    <span class="block font-medium text-foreground" x-text="item.title"></span>
                                                    <span class="block text-xs text-muted-foreground" x-text="item.meta"></span>
                                                </button></li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="f-message" class="mb-1 flex justify-between text-sm font-medium text-foreground">
                                <span>Message</span>
                                <span class="font-normal tabular-nums" :class="message.trim().length < min || message.length > max ? 'text-muted-foreground' : 'text-primary'"
                                    x-text="message.length + ' / ' + max" aria-live="polite">0 / {{ $config['max'] }}</span>
                            </label>
                            <textarea id="f-message" x-model="message" @blur="touch('message')" rows="7" :maxlength="max"
                                class="{{ $input }} font-[inherit]" :class="errors.message ? 'border-destructive' : 'border-border focus:border-primary'" :aria-invalid="!!errors.message" aria-describedby="e-message"
                                :placeholder="placeholder()"></textarea>
                            <p id="e-message" x-show="errors.message" x-text="errors.message" class="mt-1 text-xs text-destructive"></p>
                        </div>

                        <div class="absolute -left-[9999px]" aria-hidden="true">
                            <label>Website <input type="text" x-model="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="mt-5">
                            <label class="flex items-start gap-2.5 text-sm text-muted-foreground">
                                <input id="f-consent" type="checkbox" x-model="consent" @change="touch('consent')" class="mt-0.5 size-4 shrink-0 rounded border-border text-primary focus:ring-ring" aria-describedby="e-consent">
                                <span>I agree that my name, email and message are stored to answer me, and deleted after 12 months, as described in the <a href="{{ url('/privacy') }}" target="_blank" class="font-medium text-primary underline">privacy policy</a>.</span>
                            </label>
                            <p id="e-consent" x-show="errors.consent" x-text="errors.consent" class="mt-1 text-xs text-destructive"></p>
                        </div>

                        <div x-ref="turnstile" class="mt-5 empty:hidden"></div>

                        <button type="submit" :disabled="busy"
                            class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 font-semibold text-primary-foreground transition-colors hover:bg-primary-hover disabled:opacity-70">
                            <x-lucide-loader-circle class="size-5 animate-spin" x-show="busy" x-cloak />
                            <span x-text="busy ? 'Sending…' : 'Send message'">Send message</span>
                        </button>
                        <noscript><p class="mt-3 text-sm text-destructive">This form needs JavaScript.@if ($email) Email <a href="mailto:{{ $email }}" class="underline">{{ $email }}</a> instead.@endif</p></noscript>
                    </form>
                </div>

                {{-- Success (CONTACT-03) --}}
                <div x-show="sent" x-cloak class="rounded-2xl border border-border bg-card p-8 text-center shadow-sm sm:p-10">
                    <span class="contact-success mx-auto flex size-16 items-center justify-center rounded-full bg-brand-soft text-primary">
                        <x-lucide-circle-check class="size-9" />
                    </span>
                    <h2 x-ref="success" tabindex="-1" class="mt-5 text-2xl font-bold tracking-tight text-foreground focus:outline-none">Thanks, <span x-text="sentName"></span>. Message received.</h2>
                    <p class="mt-2 text-muted-foreground">You can expect a reply within <strong class="text-foreground">{{ $settings->reply_time }}</strong>@if ($settings->auto_reply). A copy is on its way to your inbox.@else.@endif</p>
                    @if ($popular->isNotEmpty())
                        <div class="mt-8 text-left">
                            <p class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">While you wait</p>
                            <div class="rounded-xl border border-border px-5">
                                @foreach ($popular as $item)
                                    <x-card.article-row :article="$item" />
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <button type="button" @click="reset()" class="mt-6 text-sm font-medium text-muted-foreground underline-offset-2 hover:text-primary hover:underline">Send another message</button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.site>

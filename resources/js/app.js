import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

// Light / Dark / System (PRD PUB-11). The initial class is set inline in <head> to avoid a flash.
Alpine.store('theme', {
    mode: (() => { try { return localStorage.getItem('theme') ?? 'system'; } catch { return 'system'; } })(),

    set(mode) {
        this.mode = mode;
        try {
            mode === 'system' ? localStorage.removeItem('theme') : localStorage.setItem('theme', mode);
        } catch {}
        this.apply();
    },

    cycle() {
        const order = ['light', 'dark', 'system'];
        this.set(order[(order.indexOf(this.mode) + 1) % order.length]);
    },

    apply() {
        const dark = this.mode === 'dark' || (this.mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    },
});

matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => Alpine.store('theme').apply());

// Focus mode (PUB-05): hides both sidebars on the tutorial layout. The class is restored in <head>.
Alpine.store('focus', {
    available: false,
    on: document.documentElement.classList.contains('focus-mode'),

    toggle(on = !this.on) {
        this.on = on;
        document.documentElement.classList.toggle('focus-mode', on);
        try { on ? localStorage.setItem('ctp-focus', '1') : localStorage.removeItem('ctp-focus'); } catch {}
    },
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && Alpine.store('focus').on && !document.querySelector('dialog[open]')) Alpine.store('focus').toggle(false);
});

// Scroll-spy for the "On this page" TOC: highlights the heading nearest the top of the viewport.
Alpine.data('toc', () => ({
    active: null,

    init() {
        const ids = [...this.$el.querySelectorAll('[data-toc]')].map((a) => a.dataset.toc);
        const headings = ids.map((id) => document.getElementById(id)).filter(Boolean);
        if (!headings.length) return;
        this.active = ids[0];

        const observer = new IntersectionObserver(
            (entries) => {
                entries.filter((e) => e.isIntersecting).forEach((e) => (this.active = e.target.id));
            },
            { rootMargin: '-100px 0px -60% 0px' },
        );
        headings.forEach((h) => observer.observe(h));
    },
}));

// Ctrl/⌘ K search palette (PUB-06). Results come from /api/search; recent searches stay in this browser.
Alpine.data('searchPalette', (endpoint, searchUrl) => ({
    isOpen: false,
    q: '',
    groups: [],
    items: [],
    active: -1,
    loading: false,
    searched: false,
    recent: [],
    searchUrl,
    controller: null,

    init() {
        try { this.recent = JSON.parse(localStorage.getItem('ctp-recent') ?? '[]').slice(0, 5); } catch { this.recent = []; }

        // Any [data-search-trigger] form (header, hero, 404) opens the palette instead of submitting.
        document.addEventListener('focusin', (e) => {
            const form = e.target.closest?.('[data-search-trigger]');
            if (form && e.target.matches('input')) {
                e.target.blur();
                this.open(e.target.value);
            }
        });
    },

    shortcut(e) {
        const typing = e.target.closest?.('input, textarea, select, [contenteditable]');
        if ((e.key === 'k' || e.key === 'K') && (e.metaKey || e.ctrlKey)) {
            e.preventDefault();
            this.isOpen ? this.close() : this.open();
        } else if (e.key === '/' && !typing && !this.isOpen) {
            e.preventDefault();
            this.open();
        }
    },

    open(q = '') {
        this.isOpen = true;
        if (q) { this.q = q; this.fetchResults(); }
        this.$nextTick(() => this.$refs.input?.focus());
    },

    close() {
        this.isOpen = false;
    },

    async fetchResults() {
        const q = this.q.trim();
        if (q.length < 2) { this.groups = []; this.items = []; this.active = -1; this.searched = false; return; }

        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;
        try {
            const res = await fetch(`${endpoint}?q=${encodeURIComponent(q)}`, { signal: this.controller.signal, headers: { Accept: 'application/json' } });
            const data = await res.json();
            let index = 0;
            this.groups = data.groups.map((g) => ({ ...g, items: g.items.map((item) => ({ ...item, index: index++ })) }));
            this.items = this.groups.flatMap((g) => g.items);
            this.active = this.items.length ? 0 : -1;
            this.searched = true;
        } catch (e) {
            if (e.name !== 'AbortError') { this.groups = []; this.items = []; }
        } finally {
            this.loading = false;
        }
    },

    move(step) {
        if (!this.items.length) return;
        this.active = (this.active + step + this.items.length) % this.items.length;
        document.getElementById(`palette-item-${this.active}`)?.scrollIntoView({ block: 'nearest' });
    },

    remember() {
        const q = this.q.trim();
        if (q.length < 2) return;
        this.recent = [q, ...this.recent.filter((t) => t !== q)].slice(0, 5);
        try { localStorage.setItem('ctp-recent', JSON.stringify(this.recent)); } catch {}
    },

    go() {
        this.remember();
        const item = this.items[this.active];
        window.location = item ? item.url : `${this.searchUrl}?q=${encodeURIComponent(this.q.trim())}`;
    },
}));

// ART-12 kudos. Remembered per browser so the button stays "given" on cached pages.
Alpine.data('kudos', (endpoint, initial, id) => ({
    count: initial,
    given: false,
    busy: false,
    pop: false,

    init() {
        try { this.given = JSON.parse(localStorage.getItem('ctp-kudos') ?? '[]').includes(id); } catch {}
    },

    async give() {
        if (this.given || this.busy || !endpoint) return;
        this.busy = true;
        try {
            const res = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            this.count = data.count;
            this.given = true;
            this.pop = true;
            setTimeout(() => (this.pop = false), 900);
            try {
                const ids = JSON.parse(localStorage.getItem('ctp-kudos') ?? '[]');
                localStorage.setItem('ctp-kudos', JSON.stringify([...new Set([...ids, id])].slice(-500)));
            } catch {}
        } finally {
            this.busy = false;
        }
    },
}));

// Cloudflare Turnstile, loaded only when a form that needs it is used (CMT-02, CONTACT-04).
let turnstileLoading;
window.loadTurnstile = () => turnstileLoading ??= new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
    script.async = true;
    script.onload = () => resolve(window.turnstile);
    script.onerror = reject;
    document.head.append(script);
});

// Comment form (CMT-01). Posted with fetch: article pages are cached, so there is no CSRF token to rely on.
Alpine.data('commentForm', (endpoint, turnstileKey) => ({
    name: '', email: '', body: '', notify: false, website: '',
    busy: false, message: '', error: false, errors: {}, replyTo: null,
    startedAt: Date.now(), widget: null,

    init() {
        try {
            const saved = JSON.parse(localStorage.getItem('ctp-commenter') ?? '{}');
            this.name = saved.name ?? ''; this.email = saved.email ?? '';
            this.body = localStorage.getItem(`ctp-draft:${endpoint}`) ?? '';
        } catch {}
        this.$watch('body', (value) => { try { localStorage.setItem(`ctp-draft:${endpoint}`, value); } catch {} });
        window.addEventListener('comment-reply', (e) => this.reply(e.detail));
        if (turnstileKey) this.$refs.form.addEventListener('focusin', () => this.mountTurnstile(), { once: true });
    },

    async mountTurnstile() {
        if (this.widget !== null) return;
        try {
            const turnstile = await window.loadTurnstile();
            this.widget = turnstile.render(this.$refs.turnstile, { sitekey: turnstileKey, theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light', size: 'flexible' });
        } catch {}
    },

    reply({ id, name }) {
        this.replyTo = { id, name };
        document.querySelector(`[data-reply-slot="${id}"]`)?.append(this.$refs.form);
        this.$nextTick(() => this.$refs.body.focus());
    },

    cancelReply() {
        this.replyTo = null;
        this.$refs.home.append(this.$refs.form);
    },

    validate() {
        this.errors = {};
        if (this.name.trim().length < 2) this.errors.name = 'Please enter your name.';
        if (!/^\S+@\S+\.\S+$/.test(this.email.trim())) this.errors.email = 'Please enter a valid email.';
        if (this.body.trim().length < 2) this.errors.body = 'Write a little more.';
        return Object.keys(this.errors).length === 0;
    },

    async submit() {
        this.message = '';
        if (!this.validate()) return;
        this.busy = true;
        try { localStorage.setItem('ctp-commenter', JSON.stringify({ name: this.name, email: this.email })); } catch {}

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    name: this.name, email: this.email, body: this.body, notify_replies: this.notify,
                    parent_id: this.replyTo?.id ?? null, website: this.website, _elapsed: Date.now() - this.startedAt,
                    'cf-turnstile-response': this.widget !== null ? window.turnstile?.getResponse(this.widget) : null,
                }),
            });
            const data = await res.json().catch(() => ({}));

            if (res.status === 422) {
                this.errors = Object.fromEntries(Object.entries(data.errors ?? {}).map(([k, v]) => [k, v[0]]));
                this.error = true;
                this.message = data.errors ? 'Please check the highlighted fields.' : (data.message ?? 'Please try again.');
            } else if (!res.ok) {
                this.error = true;
                this.message = res.status === 429 ? 'Too many comments in a short time. Please wait a few minutes.' : (data.message ?? 'Something went wrong. Please try again.');
            } else {
                this.error = false;
                this.message = data.message;
                if (data.html) this.insert(data.html, data.id);
                this.body = '';
                try { localStorage.removeItem(`ctp-draft:${endpoint}`); } catch {}
            }
        } catch {
            this.error = true;
            this.message = 'You seem to be offline. Your comment is kept here; try again in a moment.';
        } finally {
            this.busy = false;
            if (this.widget !== null) window.turnstile?.reset(this.widget);
        }
    },

    insert(html, id) {
        const list = this.$root.querySelector('[data-comment-list]');
        this.$root.querySelector('[data-no-comments]')?.remove();
        const wrap = document.createElement('div');
        wrap.innerHTML = html;
        const slot = this.replyTo ? list.querySelector(`[data-reply-slot="${this.replyTo.id}"]`) : null;
        if (slot) {
            wrap.firstElementChild.classList.add('ml-6', 'sm:ml-14', 'border-l-2', 'border-border', 'pl-4', 'sm:pl-6');
            slot.before(wrap.firstElementChild);
            this.cancelReply();
        } else {
            list.append(wrap.firstElementChild);
        }
        document.getElementById(`comment-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    },
}));

// Contact form (CONTACT-02, CONTACT-03): live validation, error summary, draft, autocomplete, success screen.
Alpine.data('contactForm', (config) => ({
    topic: config.topic, name: '', email: '', company: '', message: '', consent: false, website: '',
    article: config.article, articleQuery: '', results: [],
    errors: {}, touched: {}, summary: [], serverError: '', busy: false, sent: false, sentName: '',
    min: config.min, max: config.max, startedAt: Date.now(), widget: null,
    draftKey: 'ctp-contact-draft',

    init() {
        try {
            const draft = JSON.parse(localStorage.getItem(this.draftKey) ?? '{}');
            ['name', 'email', 'company', 'message'].forEach((k) => { if (draft[k]) this[k] = draft[k]; });
            // ?topic= in the link wins over a saved draft.
            if (draft.topic && !new URLSearchParams(location.search).has('topic')) this.topic = draft.topic;
        } catch {}
        this.$watch(() => [this.topic, this.name, this.email, this.company, this.message].join('|'), () => this.saveDraft());
        if (config.turnstile) this.$refs.form.addEventListener('focusin', () => this.mountTurnstile(), { once: true });
    },

    shows(field) { return !!config.topics[this.topic]?.[field]; },

    pick(topic, scroll = true) {
        this.topic = topic;
        if (scroll && window.matchMedia('(max-width: 1023px)').matches) this.$refs.form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    placeholder() {
        return {
            tutorial: 'Which step are you on, what did you expect and what happened instead? Paste the error message if there is one.',
            problem: 'What is wrong, and where? A link or the section heading helps.',
            collaboration: 'Tell me about the project, the timeline and how I can help.',
            speaking: 'The event, the audience, the date and the topic you have in mind.',
        }[this.topic] ?? 'How can I help?';
    },

    saveDraft() {
        try { localStorage.setItem(this.draftKey, JSON.stringify({ topic: this.topic, name: this.name, email: this.email, company: this.company, message: this.message })); } catch {}
    },

    rules: {
        name: (v) => v.trim().length >= 2 || 'Please enter your name.',
        email: (v) => /^\S+@\S+\.\S+$/.test(v.trim()) || 'Please enter a valid email address.',
        message: function (v) { return v.trim().length >= this.min || `Please write at least ${this.min} characters so I can help.`; },
        consent: (v) => v === true || 'Please agree to the privacy policy so I can store and answer your message.',
    },

    check(field) {
        const result = this.rules[field].call(this, this[field]);
        if (result === true) delete this.errors[field]; else this.errors[field] = result;
        this.errors = { ...this.errors };
        return result === true;
    },

    touch(field) {
        this.touched[field] = true;
        this.check(field);
    },

    focusField(field) { document.getElementById(`f-${field}`)?.focus(); },

    async findArticles() {
        const q = this.articleQuery.trim();
        if (q.length < 2) { this.results = []; return; }
        try {
            const data = await (await fetch(`${config.search}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })).json();
            this.results = (data.groups ?? []).filter((g) => g.label === 'Tutorials' || g.label === 'Blog').flatMap((g) => g.items).slice(0, 8);
        } catch { this.results = []; }
    },

    async mountTurnstile() {
        if (this.widget !== null) return;
        try {
            const turnstile = await window.loadTurnstile();
            this.widget = turnstile.render(this.$refs.turnstile, { sitekey: config.turnstile, theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' });
        } catch {}
    },

    async submit() {
        this.serverError = '';
        const ok = ['name', 'email', 'message', 'consent'].map((f) => this.check(f)).every(Boolean);
        this.summary = Object.entries(this.errors).map(([field, message]) => ({ field, message }));
        if (!ok) { this.$nextTick(() => this.$refs.summary.focus()); return; }

        this.busy = true;
        try {
            const res = await fetch(config.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    topic: this.topic, name: this.name, email: this.email, message: this.message, consent: this.consent,
                    company: this.shows('company') ? this.company : null,
                    article_id: this.shows('article') ? this.article?.id ?? null : null,
                    website: this.website, _elapsed: Date.now() - this.startedAt,
                    'cf-turnstile-response': this.widget !== null ? window.turnstile?.getResponse(this.widget) : null,
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 422 && data.errors) {
                this.errors = Object.fromEntries(Object.entries(data.errors).map(([k, v]) => [k, v[0]]));
                this.summary = Object.entries(this.errors).map(([field, message]) => ({ field, message }));
                this.$nextTick(() => this.$refs.summary.focus());
            } else if (!res.ok) {
                this.serverError = data.message ?? 'Something went wrong. Please try again.';
            } else {
                this.sentName = data.name ?? this.name;
                this.sent = true;
                this.message = ''; this.summary = [];
                try { localStorage.removeItem(this.draftKey); } catch {}
                this.$nextTick(() => { this.$refs.success.focus(); this.$refs.success.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
            }
        } catch {
            this.serverError = 'You seem to be offline. Your message is kept in this browser; try again in a moment.';
        } finally {
            this.busy = false;
            if (this.widget !== null) window.turnstile?.reset(this.widget);
        }
    },

    reset() {
        this.sent = false;
        this.consent = false;
        this.startedAt = Date.now();
    },
}));

// Copy buttons on code blocks and terminals (PUB-04). Line numbers are not copied.
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-code], [data-copy-text]');
    if (!button) return;

    let text = button.dataset.copyText;
    if (text === undefined) {
        const code = button.closest('.code-block')?.querySelector('pre code')?.cloneNode(true);
        code?.querySelectorAll('.hl-gutter').forEach((g) => g.remove());
        text = code?.textContent ?? '';
    }

    try {
        await navigator.clipboard.writeText(text);
        button.textContent = 'Copied';
        button.dataset.copied = '';
        setTimeout(() => { button.textContent = 'Copy'; delete button.dataset.copied; }, 1500);
    } catch {}
});

// Click-to-load embeds (YouTube via youtube-nocookie, CodePen): nothing third-party loads before the click.
document.addEventListener('click', (event) => {
    const figure = event.target.closest('[data-embed-src]');
    if (!figure || figure.querySelector('iframe')) return;
    event.preventDefault();

    const iframe = document.createElement('iframe');
    iframe.src = figure.dataset.embedSrc;
    iframe.title = figure.dataset.embedTitle ?? 'Embedded content';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture; fullscreen';
    iframe.allowFullscreen = true;
    iframe.loading = 'lazy';
    figure.querySelector('a')?.replaceWith(iframe);
});

// Reading progress bar (PUB-05): how far through the article body the reader is.
const progress = document.querySelector('[data-reading-progress]');
const article = document.querySelector('[data-article]');
if (progress && article) {
    const update = () => {
        const rect = article.getBoundingClientRect();
        const total = rect.height - window.innerHeight;
        const ratio = total > 0 ? Math.min(Math.max(-rect.top / total, 0), 1) : 1;
        progress.style.transform = `scaleX(${ratio})`;
    };
    document.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
    window.addEventListener('resize', update);
    update();
}

// View beacon (ART-10): counted after 5 s of the page being visible, which filters most bots and bounces.
const beacon = document.querySelector('[data-view-beacon]');
if (beacon) {
    let visibleFor = 0;
    let sent = false;
    const timer = setInterval(() => {
        if (document.visibilityState === 'visible') visibleFor += 1;
        if (visibleFor >= 5 && !sent) {
            sent = true;
            clearInterval(timer);
            navigator.sendBeacon?.(beacon.dataset.viewBeacon) || fetch(beacon.dataset.viewBeacon, { method: 'POST', keepalive: true });
        }
    }, 1000);
}

// Heading anchors (PUB-05): a "#" link on every H2–H4 that has an id.
document.querySelectorAll('.prose-ctp :is(h2, h3, h4)[id]').forEach((heading) => {
    const link = document.createElement('a');
    link.href = `#${heading.id}`;
    link.className = 'heading-anchor';
    link.textContent = '#';
    link.setAttribute('aria-label', `Link to section: ${heading.textContent.trim()}`);
    heading.append(link);
});

// Wrap toggle on code blocks (PUB-04). Highlight bands are hidden while wrapped (lines no longer align).
document.querySelectorAll('.code-block__header').forEach((header) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'code-block__wrap';
    button.textContent = 'Wrap';
    button.setAttribute('aria-pressed', 'false');
    button.addEventListener('click', () => {
        const wrapped = header.closest('.code-block').classList.toggle('code-block--wrap');
        button.setAttribute('aria-pressed', String(wrapped));
    });
    header.querySelector('[data-copy-code]')?.before(button);
});

// Image lightbox (PUB-05): article images, [data-zoomable] and gallery links ([data-lightbox]).
let lightbox;
const openLightbox = (src, alt, caption) => {
    if (!lightbox) {
        lightbox = document.createElement('dialog');
        lightbox.className = 'lightbox';
        lightbox.setAttribute('aria-label', 'Image viewer');
        lightbox.innerHTML = '<button type="button" class="lightbox__close" aria-label="Close image">×</button><figure><img alt=""><figcaption></figcaption></figure>';
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox || e.target.tagName === 'IMG' || e.target.closest('.lightbox__close')) lightbox.close(); });
        document.body.append(lightbox);
    }
    const img = lightbox.querySelector('img');
    img.src = src;
    img.alt = alt ?? '';
    lightbox.querySelector('figcaption').textContent = caption ?? '';
    lightbox.showModal();
};
const largest = (img) => {
    const candidates = (img.getAttribute('srcset') ?? '').split(',').map((c) => c.trim().split(/\s+/)).filter((c) => c[0]);
    candidates.sort((a, b) => parseInt(b[1] ?? '0') - parseInt(a[1] ?? '0'));
    return candidates[0]?.[0] ?? img.currentSrc ?? img.src;
};
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-lightbox]');
    if (link) {
        event.preventDefault();
        openLightbox(link.href, link.querySelector('img')?.alt, link.dataset.lightbox);
        return;
    }
    const img = event.target.closest('[data-article-body] img, .prose-ctp figure img, img[data-zoomable]');
    if (img && !img.closest('a')) {
        openLightbox(largest(img), img.alt, img.closest('figure')?.querySelector('figcaption')?.textContent);
    }
});

Alpine.plugin([collapse, focus]);
window.Alpine = Alpine;
Alpine.start();

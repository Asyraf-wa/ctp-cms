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

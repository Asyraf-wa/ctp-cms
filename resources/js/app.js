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

Alpine.plugin([collapse, focus]);
window.Alpine = Alpine;
Alpine.start();

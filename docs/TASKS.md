# Task List — CTP

> **Version:** v0.3 · Mark `[x]` when done. IDs in parentheses refer to features in [PRD.md](PRD.md).
> Don't start the next phase before the current phase's **Exit criteria** are met.

---

## Phase 0 — Setup & Foundations

### 0A. Repo & Docker
- [x] T0.01 `git init`, `main` and `develop` branches, `.gitignore` (incl. `legacy/`). *GitHub remote `Asyraf-wa/ctp` (private) still to be created and pushed by the owner (no `gh` CLI here)*
- [x] T0.02 `docker-compose.yml`: app (php 8.4-fpm), nginx :8080, **mysql:8.0.44**, **phpMyAdmin :8081**, node (Vite), mailpit :8025, scheduler (OPS-01)
- [x] T0.03 `./dev` helper script (`up`, `down`, `artisan`, `composer`, `npm`, `test`, `fresh`, `legacy-import`)
- [x] T0.04 Install Laravel 13 (13.36) at the repo root. MySQL, `ctp_testing` and the `ctp_legacy` DB (loaded with `./dev legacy-import`)
- [x] T0.05 Pest 4 (on MySQL), Larastan level 6, Pint. GitHub Actions CI (lint, stan, build, tests)

### 0B. Theme & Layout
- [x] T0.06 Tailwind v4 tokens from [DESIGN.md](DESIGN.md), typography plugin themed, Inter and JetBrains Mono self-hosted
- [x] T0.07 Base layout: header (shrink on scroll), footer, Light/Dark/System toggle with no flash, mobile nav drawer
- [x] T0.08 Placeholder CTP logo mark (gradient), favicon set, web manifest

### 0C. Admin & Auth
- [x] T0.09 Install Filament 5 at `/admin`. Teal/Slate theme, Inter
- [x] T0.10 Google OAuth via Socialite with the `ADMIN_EMAILS` allow-list (AUTH-01..03)
- [x] T0.11 Local-only Quick login (AUTH-04)

### 0D. Spikes
- [x] T0.12 **Editor spike:** Filament RichEditor with a custom code block (language and filename), a callout block and a media-picker action. Test it with a sample of legacy HTML. Decide between RichEditor and TinyMCE (EDIT-01). → **RichEditor (D16)**, results in ARCH §5.1
- [ ] T0.13 **Hosting check (cPanel):**
  - cPanel Git can clone a private repo with a deploy key
  - `.cpanel.yml` tasks run `php artisan`
  - Composer is available, or not
  - symlinks or document root change are allowed
  - GD has WebP/AVIF
  - cron works
  - SMTP is available

  Document the results in ARCH §9. *Owner: Asyraf, any time before Phase 6. It doesn't block development.*

**Exit criteria:** `./dev up` starts every service. phpMyAdmin is reachable (`localhost:8101`). Google login works and other emails are rejected. The editor decision is recorded.

---

## Phase 1 — CMS Core

### 1A. Settings, Authors, Menus, Slugs
- [x] T1.01 `spatie/laravel-settings`. Settings page with the tabs General, SEO meta, Topbar notification, Ribbon, Social, Scripts, Integrations (SET-01..07)
- [x] T1.02 Topbar notification component: styles, schedule window (MYT in the admin, UTC stored), dismiss by message hash with no flash (SET-03). *Cache flush at the start/end time moves to T4.06, when the response cache arrives*
- [x] T1.03 Ribbon component: top-right corner ribbon on desktop, badge on mobile, **background colour picker with presets**, auto text contrast, live preview in the admin (SET-04)
- [x] T1.04 Author profile fields on `users` and a Filament "My profile" page (AUTHOR-02)
- [x] T1.05 `HasSlug` trait: generate, unique per type, **slug history → automatic 301** (SLUG-01..04)
- [x] T1.06 **Root slug namespace (D9):**
  - `root_slugs` table shared by tutorials and pages
  - reserved words collected automatically from the route prefixes
  - catch-all `/{slug}` route registered last
  - `ctp:check-slug-conflicts` command
  - a Pest test that fails on any route/slug collision
- [x] T1.07 Menus and menu items: Filament resource with a drag-and-drop tree (2 levels), link types (URL, route, content by ID), icons, badges, enable toggle (MENU-01..05)
- [x] T1.08 Seed the default menus (header, footer_1..3, legal)

### 1B. Taxonomy
- [x] T1.09 Migrations and models: categories (tree), tags, series (TAX-01..03)
- [x] T1.10 Filament resources: Categories (reorder, icon picker), Tags (merge action, counts), Series (ordered articles)

### 1C. Media Manager
- [x] T1.11 `media`, `media_folders` and `media_usages` tables plus models (MED-01)
- [x] T1.12 Upload service: validation, EXIF strip, SHA1 dedupe, SVG sanitising (MED-02)
- [x] T1.13 Variant job: WebP 320/768/1280, OG 1200×630, AVIF behind `MEDIA_AVIF` (GD AVIF is slow, off by default) (MED-03)
- [x] T1.14 Filament Media page: grid and list views, folders, drag-drop upload, metadata side panel, replace file (MED-01, 04, 07)
- [x] T1.15 Reusable **media picker** modal and form field (MED-06)
- [x] T1.16 Usage tracking plus delete protection (MED-05)

### 1D. Articles
- [x] T1.17 `articles`, `article_tag`, `article_revisions` and `seo_meta` migrations (ART-01)
- [x] T1.18 Article resource:
  - tabs for Content, Tutorial info, SEO and Settings
  - fields for type, **author**, status, schedule, featured image, series, tutorial fields, TL;DR and FAQ (ART-01, 02, 05, 06)
- [x] T1.19 Editor configured (toolbar, code block, callout, code tabs, terminal, steps, YouTube, insert from media) (EDIT-01..05)
- [x] T1.20 Render pipeline (sanitise → headings → TOC → highlight → images → blocks → exports) with tests ([ARCH §5](ARCHITECTURE.md))
- [x] T1.21 Autosave and revisions with diff and restore (ART-03)
- [x] T1.22 Signed preview URL (ART-04)
- [x] T1.23 Scheduled publishing in the scheduler (ART-02)
- [x] T1.24 "Meaningful update" checkbox and the `needs_review` / freshness rule (ART-07, 08)
- [x] T1.25 Table filters and bulk actions (ART-11)

### 1E. Projects & Pages
- [ ] T1.26 Projects resource: cover, gallery, stack tags, status, links, related tutorials
- [ ] T1.27 Pages resource (About, Privacy, Terms, Uses)

**Exit criteria:** a tutorial with 5 images, 3 code blocks, a callout and tags is written, previewed, scheduled and auto-published from the admin. *Status: covered by Pest tests, plus a browser E2E run (Playwright) on 2026-10-16: create, slug autofill, heading and callout block, category and tags, schedule, signed preview, then the `scheduler` container auto-published it on the minute (404 → 200).*

---

## Phase 2 — Public Site
- [ ] T2.00 Header and footer read from MENU; topbar notification and ribbon wired in (PUB-13, 14)
- [ ] T2.01 Home page (PUB-01)
- [ ] T2.02 Tutorials index with filters, sorting and pagination (PUB-02)
- [ ] T2.03 Article page, 3 columns (PUB-03):
  - category accordion
  - TOC with scroll-spy
  - meta row, TL;DR, Tested-with, series navigator
  - prev/next, tags, author box, related articles
- [ ] T2.04 Mobile: sidebar drawer and collapsible TOC (PUB-03)
- [ ] T2.05 Code block UX: copy, label, line numbers, highlight lines, wrap toggle (PUB-04)
- [ ] T2.06 Reading progress, Focus mode, lightbox, heading anchors, back-to-top (PUB-05)
- [ ] T2.07 Ctrl+K search palette, the `/api/search` endpoint and the `/search` page (FULLTEXT) (PUB-06)
- [ ] T2.08 Category, tag and series archive pages (PUB-07)
- [ ] T2.09 Blog index and post, single-column style (PUB-08)
- [ ] T2.10 Projects index (filter by tech) and project detail (PUB-09)
- [ ] T2.11 Static pages, About page, **author page `/author/{slug}`**, author meta row and author box on articles (PUB-10, AUTHOR-01, 03)
- [ ] T2.12 404, 500 and maintenance pages (PUB-12)
- [ ] T2.13 View beacon and daily rollup (ART-10). Kudos button (ART-12)
- [ ] T2.14 Accessibility pass: keyboard, focus, skip link, reduced motion, contrast check in both themes

**Exit criteria:** every page is responsive (360 px → 1440 px) in light and dark mode. Lighthouse ≥ 95 on the home and article pages (mobile).

---

## Phase 3 — SEO & GEO
- [ ] T3.01 MetaBuilder: title, description, canonical, robots, OG and Twitter, with fallbacks (SEO-01)
- [ ] T3.02 Admin SEO tab: counters, SERP preview, social preview, checklist (SEO-02)
- [ ] T3.03 OG image generator (GD, brand template) with cache (SEO-03)
- [ ] T3.04 JSON-LD builders for every page type (SEO-04)
- [ ] T3.05 Sitemaps (index plus children, images) (SEO-05)
- [ ] T3.06 `robots.txt` from settings, including the AI crawler policy (SEO-06, GEO-05)
- [ ] T3.07 RSS/Atom feeds (SEO-07)
- [ ] T3.08 IndexNow on publish (SEO-08)
- [ ] T3.09 URL normalisation middleware, Redirect manager and 404 log (SEO-10, 11)
- [ ] T3.10 `llms.txt`, `llms-full.txt` and the `.md` article endpoints with `rel=alternate` (GEO-01, 02)
- [ ] T3.11 "Copy as Markdown" and "Open in ChatGPT/Claude" buttons (GEO-07)
- [ ] T3.12 Internal link suggestions in the editor sidebar (SEO-12)

**Exit criteria:** Google Rich Results Test passes for a tutorial, a blog post and a project. Sitemaps validate. The `.md` and `llms.txt` endpoints serve correct content.

---

## Phase 4 — Comments, Settings & Ops
- [ ] T4.01 Comments: form, Turnstile, honeypot, rate limit, Markdown-lite rendering, one level of threading (CMT-01, 02)
- [ ] T4.02 Moderation resource with an "Author" reply (CMT-03)
- [ ] T4.03 Comment email notifications (CMT-04, 05)
- [ ] T4.04 Dashboard widgets (ADM-01)
- [ ] T4.05 Response cache, cleared on any content change (OPS-04)
- [ ] T4.06 Security headers and CSP (OPS-05)
- [ ] T4.07 Backups to an off-site disk, plus a **test restore** (OPS-03)

- [ ] T4.09 **Contact page** (CONTACT-01..03): two-column layout, topic cards, conditional fields, related-article autocomplete, live validation, draft kept, success screen, `?topic=` prefill
- [ ] T4.10 Contact anti-spam: Turnstile, honeypot, min time-to-submit, rate limit, heuristics (CONTACT-04)
- [ ] T4.11 Contact emails: admin notification (reply-to the sender) plus a branded auto-reply (CONTACT-05)
- [ ] T4.12 Filament **inbox**: unread badge, filters, reply action, spam and archive, dashboard widget (CONTACT-06)
- [ ] T4.13 Hashed IP, PDPA consent, 12-month auto-purge job (CONTACT-07)

**Exit criteria:** a comment goes from submit to moderate to approve to reply, with an email. A contact message goes from submit to the inbox to a reply, and both emails are received in Mailpit. The cache invalidates on publish. A backup restore is verified.

---

## Phase 5 — Legacy Migration ([ARCH §10](ARCHITECTURE.md))
- [x] T5.01 Inspect the legacy dump and write the mapping into ARCH §10 *(done 2026-10-16)*
- [x] T5.02 Look for the legacy inline images: live site 404, Wayback Machine none *(checked 2026-10-16)*. Fallback chain plus placeholder (D11)
- [ ] T5.03 `./dev legacy-import`: load the dump into `ctp_legacy`
- [ ] T5.04 `ctp:import-legacy` for categories, tags (prune and merge), settings, **tutorials only** (`body`, views, kudos, SEO; the 4 blog posts are skipped), with `--dry-run` (MIG-01, 02)
- [ ] T5.05 Poster download from the live site. Inline images through the fallback chain, with a placeholder and `needs_review` (MIG-03)
- [ ] T5.06 `HtmlCleaner` with **fixture tests** built from 5 real legacy articles: headings, `<br>` paragraphs, File Location → filename, code blocks, tables, styles (MIG-04)
- [ ] T5.07 Redirect map: `/codethepixel/articles/{Slug}` → `/{slug}`, tags, the index, poster image URLs, and the legacy blog posts (Emmet → tutorial, others 410) (MIG-05)
- [ ] T5.08 Import report, and a manual QA pass over all 60 imported tutorials (MIG-06, 07)
- [ ] T5.09 Crawl the old site's URL list and assert that each one returns a 301 to a 200

**Exit criteria:** every legacy article renders correctly. Every legacy URL returns a 301 to a 200.

---

## Phase 6 — Launch Prep (the owner runs the live deploy, D15)
- [ ] T6.01 GitHub Actions: tests, then `composer install --no-dev`, then `npm run build`, then a force-push to the `production` branch
- [ ] T6.02 `.cpanel.yml` (rsync to the app dir, migrate, optimize, cache clear)
- [ ] T6.03 **`docs/DEPLOY.md`**, a step-by-step guide:
  - cPanel Git clone with a deploy key
  - document root or symlink
  - `.env` template
  - `storage:link`
  - cron
  - SMTP
  - first-time `ctp:import-legacy` on production
- [ ] T6.04 `docs/LAUNCH-CHECKLIST.md`:
  - smoke tests (login, publish, comment, contact, OG image, sitemap)
  - DNS and HTTPS
  - Search Console and Bing
  - watch the 404 log for 2 weeks

**Exit criteria:** following DEPLOY.md on a clean cPanel account gives a working site with no extra steps.

---

## Backlog (post-v1)
- Reader reactions ("Was this helpful?"), which help find weak tutorials
- Downloadable cheat-sheet or PDF per series
- PWA offline reading for saved tutorials
- Multi-language (if ever)

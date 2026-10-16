# Task List — CTP

> **Version:** v0.2 · Mark `[x]` when done. IDs in parentheses refer to features in [PRD.md](PRD.md).
> Don't start the next phase before the current phase's **Exit criteria** are met.

---

## Phase 0 — Setup & Foundations

### 0A. Repo & Docker
- [ ] T0.01 `git init`, GitHub repo `Asyraf-wa/ctp` (private), `main` and `develop` branches, `.gitignore` (incl. `legacy/`)
- [ ] T0.02 `docker-compose.yml`: app (php 8.4-fpm), nginx :8080, **mysql:8.0.44**, **phpMyAdmin :8081**, node (Vite), mailpit :8025, scheduler (OPS-01)
- [ ] T0.03 `./dev` helper script (`up`, `down`, `artisan`, `composer`, `npm`, `test`, `fresh`, `legacy-import`)
- [ ] T0.04 Install Laravel 13 in `src/`. MySQL connection plus a second `legacy` connection
- [ ] T0.05 Pest, Larastan, Pint. GitHub Actions CI (lint + tests)

### 0B. Theme & Layout
- [ ] T0.06 Tailwind v4 tokens from [DESIGN.md](DESIGN.md), typography plugin themed, Inter and JetBrains Mono self-hosted
- [ ] T0.07 Base layout: header (shrink on scroll), footer, Light/Dark/System toggle with no flash, mobile nav drawer
- [ ] T0.08 Placeholder CTP logo mark (gradient), favicon set, web manifest

### 0C. Admin & Auth
- [ ] T0.09 Install Filament 5 at `/admin`. Teal/Slate theme, Inter
- [ ] T0.10 Google OAuth via Socialite with the `ADMIN_EMAILS` allow-list (AUTH-01..03)
- [ ] T0.11 Local-only Quick login (AUTH-04)

### 0D. Spikes
- [ ] T0.12 **Editor spike:** Filament RichEditor with a custom code block (language and filename), a callout block and a media-picker action. Test it with a sample of legacy HTML. Decide between RichEditor and TinyMCE (EDIT-01)
- [ ] T0.13 **Hosting check (cPanel):**
  - cPanel Git can clone a private repo with a deploy key
  - `.cpanel.yml` tasks run `php artisan`
  - Composer is available, or not
  - symlinks or document root change are allowed
  - GD has WebP/AVIF
  - cron works
  - SMTP is available

  Document the results in ARCH §9.
- [ ] T0.14 **Staging early:** the GitHub Actions `production`-branch build, `.cpanel.yml`, and a deploy of the skeleton app to a **staging subdomain** (e.g. `new.codethepixel.com`, `noindex` plus basic auth). This proves the hosting pipeline in week 1, not at launch

**Exit criteria:** `./dev up` starts every service. phpMyAdmin is reachable. Google login works and other emails are rejected. The editor decision is recorded.

---

## Phase 1 — CMS Core

### 1A. Settings, Authors, Menus, Slugs
- [ ] T1.01 `spatie/laravel-settings`. Settings page with the tabs General, SEO meta, Topbar notification, Ribbon, Social, Scripts, Integrations (SET-01..07)
- [ ] T1.02 Topbar notification component: styles, schedule window, dismiss by message hash, scheduler cache flush at the start or end time (SET-03)
- [ ] T1.03 Ribbon component: corner ribbon on desktop, badge on mobile (SET-04)
- [ ] T1.04 Author profile fields on `users` and a Filament "My profile" page (AUTHOR-02)
- [ ] T1.05 `HasSlug` trait: generate, unique per type, reserved list, **slug history → automatic 301** (SLUG-01..04)
- [ ] T1.06 Menus and menu items: Filament resource with a drag-and-drop tree (2 levels), link types (URL, route, content by ID), icons, badges, enable toggle (MENU-01..05)
- [ ] T1.07 Seed the default menus (header, footer_1..3, legal)

### 1B. Taxonomy
- [ ] T1.08 Migrations and models: categories (tree), tags, series (TAX-01..03)
- [ ] T1.09 Filament resources: Categories (reorder, icon picker), Tags (merge action, counts), Series (ordered articles)

### 1C. Media Manager
- [ ] T1.10 `media`, `media_folders` and `media_usages` tables plus models (MED-01)
- [ ] T1.11 Upload service: validation, EXIF strip, SHA1 dedupe, SVG sanitising (MED-02)
- [ ] T1.12 Variant job: WebP 320/768/1280, OG 1200×630, AVIF if supported (MED-03)
- [ ] T1.13 Filament Media page: grid and list views, folders, drag-drop upload, metadata side panel, replace file (MED-01, 04, 07)
- [ ] T1.14 Reusable **media picker** modal and form field (MED-06)
- [ ] T1.15 Usage tracking plus delete protection (MED-05)

### 1D. Articles
- [ ] T1.16 `articles`, `article_tag`, `article_revisions` and `seo_meta` migrations (ART-01)
- [ ] T1.17 Article resource:
  - tabs for Content, Tutorial info, SEO and Settings
  - fields for type, **author**, status, schedule, featured image, series, tutorial fields, TL;DR and FAQ (ART-01, 02, 05, 06)
- [ ] T1.18 Editor configured (toolbar, code block, callout, code tabs, terminal, steps, YouTube, insert from media) (EDIT-01..05)
- [ ] T1.19 Render pipeline (sanitise → headings → TOC → highlight → images → blocks → exports) with tests ([ARCH §5](ARCHITECTURE.md))
- [ ] T1.20 Autosave and revisions with diff and restore (ART-03)
- [ ] T1.21 Signed preview URL (ART-04)
- [ ] T1.22 Scheduled publishing in the scheduler (ART-02)
- [ ] T1.23 "Meaningful update" checkbox and the `needs_review` / freshness rule (ART-07, 08)
- [ ] T1.24 Table filters and bulk actions (ART-11)

### 1E. Projects & Pages
- [ ] T1.25 Projects resource: cover, gallery, stack tags, status, links, related tutorials
- [ ] T1.26 Pages resource (About, Privacy, Terms, Uses)

**Exit criteria:** a tutorial with 5 images, 3 code blocks, a callout and tags is written, previewed, scheduled and auto-published from the admin.

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
- [ ] T2.11 Static pages, About page, **author page `/authors/{slug}`**, author meta row and author box on articles (PUB-10, AUTHOR-01, 03)
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

**Exit criteria:** a comment goes from submit to moderate to approve to reply, with an email. The cache invalidates on publish. A backup restore is verified.

---

## Phase 5 — Legacy Migration ([ARCH §10](ARCHITECTURE.md))
- [x] T5.01 Inspect the legacy dump and write the mapping into ARCH §10 *(done 2026-10-16)*
- [ ] T5.02 Get the old `webroot` image folders into `legacy/webroot/` *(owner: Asyraf)*
- [ ] T5.03 `./dev legacy-import`: load the dump into `ctp_legacy`
- [ ] T5.04 `ctp:import-legacy` for categories, tags (prune and merge), settings, articles (`body`, views, kudos, SEO), with `--dry-run` (MIG-01, 02)
- [ ] T5.05 Poster download from the live site, plus inline images from `legacy/webroot/`, into the media library (MIG-03)
- [ ] T5.06 `HtmlCleaner` with **fixture tests** built from 5 real legacy articles: headings, `<br>` paragraphs, File Location → filename, code blocks, tables, styles (MIG-04)
- [ ] T5.07 Redirect map for every legacy URL pattern, including the poster image URLs (MIG-05)
- [ ] T5.08 Import report, and a manual QA pass over all 64 articles (MIG-06, 07)
- [ ] T5.09 Crawl the old site's URL list and assert that each one returns a 301 to a 200

**Exit criteria:** every legacy article renders correctly. Every legacy URL returns a 301 to a 200.

---

## Phase 6 — Launch
- [ ] T6.01 Re-check the `production`-branch workflow from T0.14 (tests must pass before the build)
- [ ] T6.02 Production deploy via **cPanel Git** (`production` branch + `.cpanel.yml`): document root or symlink, `storage:link`, `.env`, deploy key
- [ ] T6.03 cPanel cron for `schedule:run`, and a queue health check
- [ ] T6.04 Production smoke test: login, publish, comment, mail, OG image, sitemap
- [ ] T6.05 DNS cut-over, HTTPS, HSTS
- [ ] T6.06 Google Search Console and Bing Webmaster: verify, submit sitemaps, use the URL inspection tool on top pages
- [ ] T6.07 Watch the 404 log and Search Console coverage every day for 2 weeks

**Exit criteria:** production is live with no un-triaged 404s, and traffic is at the baseline.

---

## Backlog (post-v1)
- Reader reactions ("Was this helpful?"), which help find weak tutorials
- Downloadable cheat-sheet or PDF per series
- PWA offline reading for saved tutorials
- Multi-language (if ever)

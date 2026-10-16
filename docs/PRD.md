# PRD — CTP (Code The Pixel) Tutorial CMS

> **Status:** v0.3 (draft) · **Date:** 2026-10-16 · **Owner:** Asyraf Wahi Anuar
> See [ARCHITECTURE.md](ARCHITECTURE.md) · [DESIGN.md](DESIGN.md) · [TASKS.md](TASKS.md)

### Changelog
- **v0.3 (2026-10-16):**
  - **Tutorials at the root URL `domain/{slug}`** (D9)
  - a pro **contact form** (CONTACT)
  - ribbon background colour
  - Kudos confirmed
  - the legacy blog posts are not imported
  - lost legacy images handled with placeholders
  - no staging; the owner runs the live deploy
- **v0.2 (2026-10-16):** Added the Settings page (SET: general, meta, topbar notification, ribbon), authors shown on articles (AUTHOR), menu management (MENU) and slugs with slug history (SLUG). The open questions became a decisions log: cPanel Git deploy, MySQL 8.0.44, legacy `body` column, no newsletter.
- **v0.1 (2026-10-16):** First draft. Stack is Laravel + Filament. The site is in English only. Legacy articles are imported. Projects and Blog start fresh. Nerd Stats is removed.

---

## 1. Summary

A full rebuild of **codethepixel.com** as **CTP**. The site publishes tutorials about things the author explores. A custom CMS replaces the current CakePHP site:

- **Public site:** a fast, SEO-first and AI-search-friendly (GEO) reading experience. It uses a 3-column tutorial layout, dark mode and the CTP SaaS brand.
- **Admin (`/admin`):** a Filament panel with Google sign-in for the owner only. It manages articles, categories, tags, series, a WordPress-style media library, comments, projects, pages and site settings.

## 2. Goals & Non-Goals

**Goals**
1. Writing and publishing a tutorial with images and code takes minutes. Images come from the media library and are inserted from inside the editor.
2. The site reaches **Lighthouse ≥ 95** on all four categories, on shared hosting.
3. Rich results are valid, with structured data on every public page. Each article is also exposed in machine-readable form for AI engines (GEO).
4. Migration loses no SEO: every legacy URL 301-redirects to its new URL.
5. Light, Dark and System themes. **WCAG 2.2 AA**.

**Non-Goals (v1)**
- Multiple authors, roles or public user accounts. There is one admin only.
- More than one language. The site is **English only**.
- Paid content, memberships or a newsletter sending engine. RSS is v1; a newsletter is considered later.
- A headless or API-first CMS.

## 3. Personas

| Persona | Needs |
|---|---|
| **Author (Asyraf)** | Fast writing, good code blocks, easy image handling, an SEO checklist while writing, scheduling. |
| **Reader / developer** | Finds a solution fast, copies code easily, knows whether the tutorial still applies (versions), reads comfortably in dark mode and on mobile. |
| **Search & AI crawlers** | Clean HTML, structured data, sitemaps, `llms.txt`, Markdown versions of articles. |

## 4. Content Model (overview)

| Entity | Purpose |
|---|---|
| **Article** | `type` = `tutorial` or `blog`. Shares the editor, SEO, tags and comments. |
| **Category** | Hierarchical (parent/child), with an icon, colour accent, description and order. For tutorials. |
| **Tag** | Flat, many-to-many with articles. |
| **Series** | Ordered group of tutorials, such as "CakePHP 5 from Zero". |
| **Project** | Portfolio showcase with its own model and layout. |
| **Page** | Static pages: About, Privacy, Terms, Uses. |
| **Media** | Global media library with folders, alt text, captions and generated variants. |
| **Comment** | Threaded one level deep, moderated. |
| **Redirect** | From-path to to-path with a status code and hit count. Seeded by the legacy import. |

Full schema: [ARCHITECTURE.md §4](ARCHITECTURE.md).

## 5. Features

### AUTH — Admin Authentication
- **AUTH-01** Sign-in with **Google OAuth** (Laravel Socialite) only. There is no password form.
- **AUTH-02** Allow-list of emails in config/env (`ADMIN_EMAILS`). Any other Google account is rejected and logged.
- **AUTH-03** Session hardening: remember-me for 30 days, regenerate on login, rate-limit the callback.
- **AUTH-04** Local only: a "Quick login" button for development.

### ART — Articles
- **ART-01** CRUD for tutorials and blog posts: title, **slug** (SLUG), **author** (AUTHOR), excerpt, body (WYSIWYG), featured image, category (tutorials), tags, series and position.
- **ART-02** Status values are `draft`, `scheduled` and `published`, with `published_at`. Scheduled posts go live through the scheduler.
- **ART-03** **Autosave** (every 30 s while editing) and **revision history**, with a diff and restore of the last 20 revisions.
- **ART-04** **Preview** of drafts through a signed URL, rendered in the real public layout.
- **ART-05** Fields specific to tutorials:
  - difficulty (`beginner`, `intermediate`, `advanced`)
  - estimated time (auto-calculated from word count, can be overridden)
  - prerequisites (list)
  - **Tech stack**: ordered technologies from the Technologies list, each with an optional version (`Laravel 13.x`, `PHP 8.4`). The first is the main one: its logo shows in lists. Versions are the "Tested with" facts (D18)
  - source code / repo URL
  - demo URL
- **ART-06** **TL;DR / Key takeaways** field, shown at the top of the article and used for GEO.
- **ART-07** `updated_at` shown publicly as "Last updated" whenever a **meaningful update** is flagged. A checkbox on save keeps typo fixes from bumping the date.
- **ART-08** **Freshness flag:** articles whose "Tested with" versions are older than a threshold, or that were flagged by hand, show a *"This tutorial may be outdated"* notice. The admin sees a "Needs review" list.
- **ART-09** Featured and pinned articles for the homepage.
- **ART-10** Hit counter that filters bots and dedupes per session per day.
- **ART-11** Admin list with filters (status, type, category, tag, needs review), search, and bulk publish or unpublish.
- **ART-12** **Kudos button** ("This helped me 👏"), carried over from the legacy site (11k kudos). One per visitor per article (cookie plus IP hash), a count shown under the article, and an admin "most helpful" widget. It is a cheap quality signal that shows which tutorials work.

### EDIT — Editor
- **EDIT-01** WYSIWYG rich editor: the **Filament RichEditor (TipTap)**, stored as JSON (D16, [ARCH §5.1](ARCHITECTURE.md)).
- **EDIT-02** Toolbar: headings H2–H4, bold, italic, strike, inline code, link, lists, blockquote, table, horizontal rule, undo/redo.
- **EDIT-03** **Code block** as a custom block that edits in a real code editor (Filament `CodeEditor`, CodeMirror), with language, filename, highlighted lines and line numbers. The native code block remains for quick pastes. Highlighting is done server-side at render time.
- **EDIT-04** **Insert from Media Library** opens the media manager modal and inserts a responsive image with alt text and caption. Drag-drop or paste uploads straight into the library.
- **EDIT-05** Custom blocks:
  - **Callout** (note, tip, warning, danger)
  - **Code tabs** (the same step in several languages or OSes)
  - **Terminal / output** block
  - **YouTube embed** (privacy-friendly, click to load)
  - **Steps** (numbered procedure, which also feeds `HowTo` schema)
- **EDIT-06** Live word count, reading time and a heading-outline preview, which is the future TOC.

### MED — Media Manager
- **MED-01** Global library with a grid and list view, **folders**, search, filter by type and date, and pagination.
- **MED-02** Drag-drop multi-upload with progress. Allowed types are images (jpg, png, gif, webp, svg sanitised), pdf and zip. The size limit can be configured.
- **MED-03** On upload: strip EXIF, generate **WebP variants** (thumbnail 320, medium 768, large 1280, og 1200×630 crop). AVIF is added when the server's GD supports it. The original is kept.
- **MED-04** Metadata: title, **alt text** (required before an image can be inserted), caption and credit.
- **MED-05** "Used in" lists the articles, projects and pages that reference each file. Deleting a file that is in use is blocked or warned.
- **MED-06** The same picker modal is reused for featured image, OG image, project cover and editor insert.
- **MED-07** Replace a file while keeping its URL and ID, for example to fix a screenshot.

### TAX — Categories, Tags, Series
- **TAX-01** Categories: tree with two levels, drag ordering, icon (Lucide name), short description, SEO fields.
- **TAX-02** Tags: inline creation from the article form (autocomplete), **merge tags**, usage count.
- **TAX-03** Series: title, description, cover, and an ordered list of tutorials. Each article in a series shows a "Part X of Y" navigator.

### PUB — Public Site
- **PUB-01** **Home** (D20): the 3-column tutorial layout, text only.
  - Left: the category accordion.
  - Centre: a slim intro (h1, tutorial count, search), then the 10 latest tutorials (pinned first) with category tabs (`/?category=…`, not indexed). Each row shows the main technology logo.
  - Right: Level, Popular tags, Popular this month (30-day views) and Learning paths.
- **PUB-02** **Tutorials index:** filter by category, tag and difficulty; sort by latest or popular; paginated. Facet state is kept in the URL.
- **PUB-03** **Article page (3 columns, based on the template):**
  - Left: category tree as an accordion. The current article and category are expanded.
  - Centre:
    - breadcrumbs, then category, title, meta (published or updated, reading time, difficulty)
    - TL;DR, prerequisites and "Tested with" box
    - body
    - series navigator, prev/next, tags, author box, related articles, comments
  - Right: **auto-generated TOC** from H2/H3 with scroll-spy, plus a "Copy page as Markdown" button and an "Edit history" date.
  - Mobile: left sidebar becomes a drawer, and the TOC becomes a collapsible "On this page" block.
- **PUB-04** **Code UX:** syntax highlighting, **copy button**, filename and language label, optional line numbers and highlighted lines, wrap toggle.
- **PUB-05** **Reading UX:**
  - reading progress bar
  - **Focus mode** (hides both sidebars, replacing the template's fullscreen button)
  - image lightbox on click
  - heading anchor links
  - back-to-top
- **PUB-06** **Search:** a **Ctrl/⌘ K command palette** with instant results (title, excerpt, category). Searches cover articles, tags and categories, with a full results page at `/search?q=`.
- **PUB-07** Category, tag and series archive pages.
- **PUB-08** **Blog:** single-column, wider typography, no category sidebar. It covers opinion, updates and journal posts.
- **PUB-09** **Projects** (new design):
  - grid of cards with cover, name, one-line pitch, tech-stack chips, status badge (Active, Maintained, Archived) and links (Live, GitHub, related tutorials)
  - project detail page with gallery, problem → solution write-up, stack, timeline and related tutorials
  - filter by tech
- **PUB-10** Static pages: About (author bio, E-E-A-T), Privacy, Terms, Uses or Setup (optional).
- **PUB-11** Theme toggle for Light, Dark and System, with no flash on load.
- **PUB-12** Custom 404 with search and popular articles. Custom 500 and maintenance pages.
- **PUB-13** Footer:
  - brand (logo, abbreviation, slogan)
  - three link columns from **MENU** (`footer_1..3`)
  - social links (SET-05)
  - bottom bar: `© {year} {copyright holder}` and the `legal` menu
- **PUB-14** Header that shrinks on scroll, as in the template. The nav comes from the **`header` menu** (MENU), with the **topbar notification** above it (SET-03) and the optional **ribbon** (SET-04).

### CMT — Comments
- **CMT-01** Native comments on articles. Each one has a name, email (private, used for a Gravatar), body as plain text with Markdown-lite for inline code and code fences, and replies one level deep.
- **CMT-02** Spam defence: **Cloudflare Turnstile**, honeypot, rate limit, link-count rule. New commenters are held for moderation; an approved email is auto-approved next time.
- **CMT-03** Filament moderation queue (approve, spam, delete, reply as author). The author's replies get an "Author" badge.
- **CMT-04** Email notifications to the admin on a new comment, and optionally to a commenter when someone replies.
- **CMT-05** Comments can be switched on or off globally and per article. Comments can auto-close after N days (optional).

### SEO — Search Engine Optimisation
- **SEO-01** Per entity: meta title, meta description (with character counters), canonical, robots (index or noindex), OG image override. Each has fallbacks.
- **SEO-02** **Google SERP preview** and **social card preview** in the admin. An SEO checklist covers title length, description, alt text on images, one H1 only, internal links and a slug that has a keyword.
- **SEO-03** **Auto-generated OG images** (1200×630): brand template with title, category and logo, rendered with GD. They are cached and regenerated when the title changes.
- **SEO-04** JSON-LD:
  - `WebSite` + `SearchAction`
  - `Organization` / `Person`
  - `BreadcrumbList`
  - `TechArticle` (tutorials) / `BlogPosting` (blog)
  - `HowTo` (when there is a Steps block)
  - `CollectionPage` (archives)
  - `SoftwareSourceCode` (projects)
- **SEO-05** `sitemap.xml` index, with sitemaps for articles, categories, tags, series, projects, pages and images. Regenerated on publish.
- **SEO-06** `robots.txt` managed from settings.
- **SEO-07** **RSS and Atom feeds**: all, per category, and blog.
- **SEO-08** **IndexNow** ping (Bing, Yandex and others) on publish or update.
- **SEO-09** Semantic HTML: a single `<h1>`, `<article>`, `<time datetime>`, descriptive link text, `lang="en"`.
- **SEO-10** Clean lowercase URLs (see [ARCH §6](ARCHITECTURE.md)). Trailing-slash and case normalisation use a 301.
- **SEO-11** **Redirect manager** in the admin, plus a **404 log** (path, count, last seen, referrer) and "create redirect" from a 404 row.
- **SEO-12** Internal linking helper: when an article is edited, suggest related articles to link to, based on shared tags and title words.

### GEO — Generative Engine Optimisation (AI search)
- **GEO-01** `/llms.txt`: a site summary with curated links to key tutorials and series. `/llms-full.txt` holds the full Markdown of every published article.
- **GEO-02** A **Markdown version** of every article at `/{slug}.md` (tutorials and pages) or `/blog/{slug}.md`, linked with `<link rel="alternate" type="text/markdown">`.
- **GEO-03** TL;DR / Key takeaways block (ART-06) in a clearly marked `<section>` near the top.
- **GEO-04** Explicit facts on the page: "Tested with" versions, published or updated dates, a strong author page with credentials and links.
- **GEO-05** An **AI crawler policy** in settings: allow or block GPTBot, ClaudeBot, PerplexityBot, Google-Extended and CCBot individually. This writes `robots.txt`.
- **GEO-06** Optional FAQ block (question and answer pairs), rendered as `FAQPage` JSON-LD.
- **GEO-07** "Copy as Markdown" or "Open in ChatGPT/Claude" buttons for readers (low effort, signals AI-friendliness).

### SET — Site Settings (admin, `/admin/settings`)
A single Filament settings page with tabs. Values are stored in the `settings` table, cached forever, and the cache is cleared on save. Every field has a sensible default, so the site works before anything is filled in.

- **SET-00** All fields are **pre-filled from the legacy `settings` row** on import (MIG-02).
- **SET-01 General:**
  - site name (`Code The Pixel`)
  - abbreviation (`CTP`)
  - slogan
  - organisation name
  - domain name
  - contact email
  - copyright holder
  - logo (light and dark variants)
  - favicon
  - default OG image
  - default author (for new articles)

  The domain is used in JSON-LD, emails and `llms.txt`. `APP_URL` in `.env` stays the source of truth for routing, and the admin warns when the two differ.
- **SET-02 SEO meta (site-wide defaults):**
  - meta title (the format is configurable, such as `{page} · {site}`)
  - meta description
  - **meta keywords**
  - **meta copyright**
  - **meta author**
  - default robots
  - Google Search Console and Bing verification codes

  Per-page values override these defaults.
  *Note: Google ignores `meta keywords`. It is output because some engines and tools still read it, and it costs nothing.*
- **SET-03 Topbar notification:** a full-width bar above the header for maintenance, announcements or new series.
  - enable or disable
  - message (short, inline links allowed)
  - style (`info`, `success`, `warning`, `maintenance`)
  - optional link label and URL
  - optional **start and end datetime** (shows and hides itself automatically)
  - **dismissible** toggle

  A dismissal is remembered in localStorage by message hash, so a new message shows again. The bar is rendered server-side, so the layout doesn't shift (no CLS). Saving clears the page cache, and a start or end time also clears it via the scheduler. On or off takes effect within a minute or less.
- **SET-04 Ribbon:** a diagonal corner ribbon at the top-right of the page, such as "NEW: Laravel 13 series".
  - enable or disable
  - title (max ~20 chars)
  - link URL
  - open in new tab
  - **background colour**: a colour picker with presets (brand teal, amber, red, slate, plus custom hex)
  - **text colour**: `auto` by default, which picks white or near-black for the best contrast (WCAG AA). It can be overridden

  It is hidden on mobile below 640 px, or shrunk to a small badge. The admin shows a live preview of the ribbon.
- **SET-05 Social links:** GitHub, X, LinkedIn, YouTube, Facebook, Instagram, TikTok and others. They are used in the footer, the author box fallback and `Organization.sameAs`.
- **SET-06 Analytics & scripts:**
  - head and body script fields (CTP SaaS snippet, Plausible or GA4)
  - cookie notice toggle (needed only if GA4 is used)
- **SET-07 Integrations:**
  - comments (global on/off, auto-close after N days)
  - Turnstile keys
  - IndexNow key
  - AI crawler policy (GEO-05)
  - SMTP "from" name and address

### AUTHOR — Authors
- **AUTHOR-01** Every article has an **author** (`author_id` → `users`), shown publicly:
  - avatar, name and job title in the article meta row
  - a full **author box** after the article (bio, social links, "More from {name}")
- **AUTHOR-02** Each admin user has a **profile**: display name, slug, avatar (from the media library, falling back to the Google avatar), job title, short bio, long bio, website, social links.
- **AUTHOR-03** **Author page** at `/author/{slug}`, listing the author's articles. It carries `Person` JSON-LD and feeds E-E-A-T and GEO.
- **AUTHOR-04** More than one author is possible by adding emails to `ADMIN_EMAILS`. Everyone gets the same admin rights in v1 (no roles).
- **AUTHOR-05** Legacy articles are assigned to the default author on import.

### MENU — Menu Management
- **MENU-01** Named **menu locations**: `header`, `footer_1`, `footer_2`, `footer_3`, `legal` (bottom bar). The theme reads menus by location.
- **MENU-02** Menu items:
  - label
  - **type**: custom URL, article, category, tag, series, page, project, or a built-in route (Home, Tutorials, Blog, Projects, Series, Search, RSS)
  - target (same or new tab)
  - optional Lucide icon
  - optional "badge" text (such as `New`)
  - CSS class
  - enabled toggle
- **MENU-03** Items can nest **2 levels**. In the header, nested items show as a dropdown on desktop and an accordion in the mobile drawer. Items are reordered by drag and drop.
- **MENU-04** Links to content are stored by ID, not URL. When a slug changes, the menu follows it automatically. Items that point to deleted content are flagged in the admin.
- **MENU-05** Menus are cached and the cache is cleared on save. The response cache is also cleared.
- **MENU-06** The active item is highlighted using the current route or URL.

### SLUG — Slugs & Permalinks
- **SLUG-01** Every public entity (article, category, tag, series, project, page, author) has a **slug**. It is generated from the title, can be edited, is lowercase kebab-case ASCII, and is unique within its type.
- **SLUG-02** **Root URLs (D9):** tutorials and static pages live at `domain/{slug}` and share **one slug namespace**, so they can never collide. Every route prefix (`blog`, `tag`, `category`, `admin`, `contact`…) is **reserved automatically** and can't be used as a slug. Details in [ARCH §6](ARCHITECTURE.md).
- **SLUG-03** **Slug history:** changing the slug of published content automatically creates a 301 redirect from the old URL (`redirects.source = slug`). Links never break.
- **SLUG-04** The admin warns before a published slug is changed.

### CONTACT — Contact Form (`/contact`)
A professional contact page, not a bare form.

- **CONTACT-01 Layout:** two columns on desktop, stacked on mobile.
  - **Left (context):**
    - short intro
    - **"What can I help with?"** topic cards
    - typical reply time (from settings, e.g. "1–3 working days")
    - email address (click to copy)
    - social links
    - a **"Before you ask"** box: tutorial questions get the fastest answer in that tutorial's comments, which also helps other readers. It links to search
  - **Right:** the form in a card.
- **CONTACT-02 Fields:**
  - **topic** (segmented buttons: General, Tutorial question, Collaboration / Hire, Speaking / Workshop, Report a problem, Other)
  - name, email
  - company (optional, shown for Collaboration and Speaking)
  - **related article** (an autocomplete search over tutorials, shown for Tutorial question and Report a problem)
  - message with a live character counter (20–5,000)
  - **PDPA consent** checkbox, linking to the privacy policy
- **CONTACT-03 UX:**
  - inline validation on blur
  - accessible error summary
  - the submit button shows a loading state
  - the draft is kept in localStorage if the user navigates away
  - a **success screen** ("Thanks, {name}. Message received.") with the expected reply time and links to popular tutorials
  - prefill from the query (`/contact?topic=collaboration`), so other pages can deep-link to it
- **CONTACT-04 Anti-spam:** Turnstile (invisible or managed mode), a honeypot, a **minimum time-to-submit** (more than 3 s), a rate limit (3 per hour per IP), a link-count and keyword heuristic, and messages marked as spam kept out of the inbox.
- **CONTACT-05 Email:**
  - a notification to `notification_email` (from settings), with reply-to set to the sender
  - an **auto-reply** to the sender with a copy of their message and the expected reply time (branded HTML email; can be turned off)
- **CONTACT-06 Admin inbox (Filament):**
  - list with an unread badge in the navigation
  - filter by topic and status (`new`, `read`, `replied`, `archived`, `spam`)
  - detail view with the related article link
  - **Reply** action that sends an email from the admin and stores the reply
  - Mark as spam, Archive
  - dashboard widget
- **CONTACT-07 Privacy:** the IP is stored only as a salted hash. Messages are **purged automatically after 12 months** by the scheduler (PDPA). The legacy contacts are not imported.

### ADM — Admin Dashboard
- **ADM-01** Widgets:
  - drafts and scheduled posts
  - comments awaiting moderation
  - top articles over 7 and 30 days, from hits
  - "Needs review" (outdated)
  - recent 404s
- **ADM-02** Global search in the admin across articles, media and comments.

### MIG — Legacy Migration
The full mapping is in [ARCH §10](ARCHITECTURE.md). The legacy dump has 64 articles, 6 categories, 118 tags and a settings row.

- **MIG-01** The legacy dump (`legacy/codethepixel_full.sql`, CakePHP / ReCRUD v2) is loaded into a separate `ctp_legacy` database in Docker.
- **MIG-02** `php artisan ctp:import-legacy` imports the **tutorials only** (categories 1–4 and 6). The 4 Blog-category posts are skipped (D10). It imports:
  - categories
  - tags (`tags_tags` / `tags_tagged`), with unused tags dropped and duplicates merged
  - articles: content from the **`body`** column, `hits` → `views`, `kudos` → `kudos`, `meta_key` and `meta_description` → SEO
  - the legacy **settings** row (name, abbreviation, slogan, organisation, domain, email, meta, notification, ribbon), with no secrets

  It is idempotent (upserts by `legacy_id`) and supports `--dry-run`.
- **MIG-03** Images:
  - posters are downloaded from the live site (still online)
  - the 25 inline images **already 404 on the live site and in the Wayback Machine**. The importer tries `legacy/webroot/`, then the live URL, then the Wayback Machine, and finally inserts a "Screenshot unavailable" placeholder and flags the article (D11)
  - all of them go into the media library, and `src` is rewritten
- **MIG-04** The legacy HTML converter turns `<strong>` / 14pt spans into **real H2/H3**, which the TOC and SEO need because the legacy bodies have no headings. It also:
  - splits `<br><br>` into paragraphs
  - turns "File Location: …" lines into code block filenames
  - normalises Prism code blocks
  - strips the inline styles and Bootstrap / Font Awesome classes
  - cleans up tables
- **MIG-05** It creates **301 redirects** for every legacy URL:
  - `/codethepixel/articles/{Slug}` → `/{slug}`
  - `/codethepixel/articles?tag=x` → `/tag/x`
  - the 4 legacy blog posts: *Using Emmet in VS Code* → the related Emmet tutorial; the other 3 return **410 Gone**
  - `/codethepixel` (with any query) → `/`
  - legacy poster image URLs → their new media URLs
- **MIG-06** An import report lists the counts, warnings (missing images, unconverted HTML, headings guessed) and a checklist of the articles that need manual review.
- **MIG-07** Titles marked `[Deprecated]` are imported with `needs_review = true` and show the outdated notice (ART-08).

### OPS — Operations
- **OPS-01** Docker for development: PHP 8.4, nginx, MySQL, **phpMyAdmin**, Mailpit, Node/Vite.
- **OPS-02** Deployment to **shared hosting** (PHP 8.4 and MySQL, no Node and no long-running workers in production). See [ARCH §9](ARCHITECTURE.md).
- **OPS-03** Daily **backups** of the database and media with `spatie/laravel-backup`, sent to an off-site disk (Google Drive or S3-compatible). Retention is 7 daily and 4 weekly.
- **OPS-04** **Full-page response cache** for public pages (`spatie/laravel-responsecache`), cleared on content change. This is the main performance lever on shared hosting.
- **OPS-05** Security headers (CSP, HSTS, X-Frame-Options, Referrer-Policy), rate limiting, HTML sanitising for comments and the editor (`symfony/html-sanitizer`).
- **OPS-06** Error tracking through the Laravel log plus email on exceptions (Sentry is optional).

## 6. Non-Functional Requirements

| Area | Requirement |
|---|---|
| Performance | LCP < 2.0 s and CLS < 0.05 on 4G mobile. Public JS < 30 KB gz (Alpine.js plus small modules). No Tailwind CDN. |
| Accessibility | WCAG 2.2 AA: keyboard navigation, focus rings, skip link, `prefers-reduced-motion`. |
| SEO | Every public page has a title, description, canonical, OG and valid JSON-LD (Rich Results Test). |
| Security | Only allow-listed Google accounts can log in. All user HTML is sanitised. CSRF on all forms. |
| Compatibility | Last 2 versions of evergreen browsers. Works without JS for reading; JS adds enhancements. |
| Hosting | Runs on shared hosting: PHP 8.4, MySQL, cron only. |
| Privacy | PDPA (Malaysia) compliant privacy page. No tracking cookies unless analytics is enabled. |

## 7. Phases & Exit Criteria

| Phase | Scope | Exit criteria |
|---|---|---|
| **0. Setup** | Docker, Laravel, Filament, Tailwind theme, Google login, editor spike | `./dev up` works. Admin login with Google works. The editor spike has a decision. |
| **1. CMS core** | Settings, menus, authors, articles, categories, tags, series, media manager, editor blocks | An article with images and code can be written and published from the admin. |
| **2. Public site** | Layouts, home, listing, article, search, blog, projects, pages | All pages are responsive and work in dark mode. Lighthouse ≥ 95 locally. |
| **3. SEO & GEO** | Meta, JSON-LD, sitemaps, feeds, OG images, llms.txt, redirects, 404 log | Rich Results Test passes. Sitemaps validate. |
| **4. Comments & extras** | Comments, moderation, dashboard, backups, cache | A comment can go through moderation end to end. A backup is restored successfully. |
| **5. Migration** | Legacy import, redirects, content QA | Every legacy URL returns a 301 to a 200 page. |
| **6. Launch prep** | `.cpanel.yml`, CI `production` build, deploy guide (`docs/DEPLOY.md`), launch checklist. **The owner runs the live deploy** | Production is live. Sitemaps are submitted. There are no 404s in the first week's log that weren't triaged. |

## 8. Success Metrics
- Organic clicks (Search Console) back to the pre-migration baseline within 4 weeks, then growing.
- Lighthouse ≥ 95 across the board on the article page (mobile).
- Time to publish a new tutorial with 5 images: under 10 minutes of admin work.
- Citations or referrals from AI engines (tracked by the referrer: chatgpt.com, perplexity.ai and others).

## 9. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Shared hosting has no queue worker and no Node | Cron-driven `schedule:run` plus `queue:work --stop-when-empty`. Assets are built in CI or locally and uploaded. |
| The TipTap editor strips legacy HTML it does not know | Spike in phase 0. Clean the HTML on import (MIG-04). Fallback: TinyMCE. |
| A Filament 5 plugin is not ready (media manager) | Build our own media resource and picker. The scope is small and controlled. |
| SEO drop after migration | Full redirect map, 404 log watched daily during launch week, sitemaps resubmitted. |
| Comment spam | Turnstile, moderation of first-time commenters, rate limits. |
| Losing data on shared hosting | Off-site daily backups with a tested restore. |

## 10. Decisions Log

| # | Question | Decision (2026-10-16) |
|---|---|---|
| D1 | Deploy method | **cPanel Git Version Control** (clone and pull) + `.cpanel.yml`. FTP as a fallback. See [ARCH §9](ARCHITECTURE.md). |
| D2 | Production DB | **MySQL 8.0.44**. Docker uses `mysql:8.0.44`. |
| D3 | Repo | GitHub `Asyraf-wa/ctp` (private). |
| D4 | Legacy content | Import from `legacy/codethepixel_full.sql`. Article content comes from the **`body` column**. Legacy hits become `views`. Categories, tags and settings are imported too. Contacts, menus and projects are not. |
| D5 | Newsletter | **No.** RSS is enough, because nobody would maintain a newsletter. |
| D6 | Language | English only. |
| D7 | Projects & Blog | Kept, but start fresh (not imported). |
| D8 | Kudos | **Yes:** the "This helped me" button (ART-12). Legacy kudos are imported. |
| D9 | URL structure | **Tutorials and pages at `domain/{slug}`.** Blog at `/blog/{slug}`, projects at `/projects/{slug}`, archives at `/category/x` and `/tag/x`. Shared slug namespace and auto-reserved words ([ARCH §6](ARCHITECTURE.md)). |
| D10 | Legacy blog posts | **Not imported.** The blog starts empty. Their URLs are redirected (Emmet) or return 410. |
| D11 | Lost legacy images | Fallback chain, then a placeholder plus `needs_review`. Screenshots are redone by hand when needed. |
| D12 | Ribbon | Top-right corner ribbon with a configurable background colour (SET-04). |
| D13 | Contact | Pro contact form with an admin inbox (CONTACT). |
| D14 | Legacy FAQs and Books | Not needed. Not imported. |
| D16 | Editor | **Filament RichEditor (TipTap), stored as JSON**, with custom blocks (Code with CodeEditor, Callout, Tabs, Terminal, Steps, YouTube, CodePen) and a Media Library tool. Verified against real legacy articles (T0.12). |
| D17 | Dev ports | app 8100 · phpMyAdmin 8101 · Mailpit 8125 · MySQL 3317 · Vite 5183 (8080/8081/3306 are taken on this machine). |
| D18 | Technology logos | A **Technologies** list (brand logos from Simple Icons, CC0, bundled; or an uploaded SVG; or a Lucide icon) replaces free-text "Tested with" and project stacks. Tutorials pick an ordered tech stack with optional versions; the first logo shows in lists, the whole stack in the article's right column. Categories may link one technology as their logo, which is also the fallback for tutorials without a stack (all legacy articles). Logos are grey and take the brand colour on hover. |
| D19 | Sidebar length | Tutorials per category in the left sidebar is a setting (Site settings → Layout), default 5. |
| D20 | Home page | **Text-only home in the tutorial layout** instead of an image-card magazine page: it matches the template, legacy articles have no covers, and it is lighter (148 KiB, Lighthouse mobile 99/100/100/100). Blog and projects are reached from the menu and footer. |
| D15 | Staging and live deploy | **No staging.** Development is local in Docker. The owner does the live deploy; we supply `.cpanel.yml`, the CI workflow and a deploy guide. |

## 11. Open Questions
_None at the moment._ New questions are added here as they come up during the build.

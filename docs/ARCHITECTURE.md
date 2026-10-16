# Architecture — CTP

> **Status:** v0.1 (draft) · **Date:** 2026-10-16 · See [PRD.md](PRD.md) · [DESIGN.md](DESIGN.md) · [TASKS.md](TASKS.md)

## 1. Stack

| Layer | Choice | Why |
|---|---|---|
| Language | **PHP 8.4** | Same as the shared hosting. |
| Framework | **Laravel 13** | Same major version as the CTP SaaS project. |
| Admin | **Filament 5** at `/admin` | Forms, tables, rich editor and widgets out of the box. Teal and slate theme. |
| Admin auth | **Laravel Socialite** (Google) and an email allow-list | No passwords to leak. |
| Public frontend | **Blade + Tailwind CSS v4 + Alpine.js** (plugins: collapse, focus) | Server-rendered HTML for SEO, a tiny JS bundle, and no Node needed at runtime. Not Inertia/Vue: the public site is content, not an app. |
| Typography | `@tailwindcss/typography` (themed `prose`) | Article body. |
| Icons | Lucide (`mallardduck/blade-lucide-icons`) | Same icon set as the SaaS. |
| Editor | Filament **RichEditor (TipTap)**, stored as **JSON**, with custom blocks and tools (see §5.1) | Decided by the spike T0.12 (D16). TinyMCE isn't needed. |
| Syntax highlighting | **`tempest/highlight`** (PHP, server-side) | No client JS. Works on shared hosting. Output is cached. |
| Images | **`intervention/image` v3 (GD driver)** | WebP variants and OG image generation without Imagick or Chrome. |
| Search | MySQL **FULLTEXT** through Laravel Scout's `database` engine | No external service. Enough for hundreds of articles. |
| HTML sanitising | `symfony/html-sanitizer` | Editor output and comments. |
| Page cache | `spatie/laravel-responsecache` (file store) | Big performance win on shared hosting. |
| Sitemap | `spatie/laravel-sitemap` | |
| Feeds | `spatie/laravel-feed` | RSS and Atom. |
| Backups | `spatie/laravel-backup` | Off-site, every day. |
| Bot protection | Cloudflare **Turnstile** | Comments. |
| Tests | **Pest**, plus Larastan and Pint | Same tooling as the SaaS. |
| Build | **Vite** (`laravel-vite-plugin`, `@tailwindcss/vite`) | Built in dev or CI. Only `public/build` is shipped. |

## 2. High-Level Diagram

```
 Browser ──► nginx/Apache (shared host) ──► Laravel
                                            ├─ Public (Blade) ── ResponseCache ──► MySQL
                                            ├─ /admin (Filament, Google OAuth)
                                            ├─ /sitemap.xml, /feed, /llms.txt, /*.md
                                            └─ Scheduler (cron every minute)
                                                 ├─ publish scheduled articles
                                                 ├─ queue:work --stop-when-empty (image variants, OG images, IndexNow, mail)
                                                 ├─ sitemap regenerate (when dirty)
                                                 └─ backup:run (daily)
```

## 3. Directory Layout

```
ctp/
├─ docker/              # php (8.4-fpm), nginx, mysql config
├─ docker-compose.yml
├─ dev                  # helper script: ./dev up|down|artisan|composer|npm|test|lint|stan|mysql|legacy-import
├─ docs/                # PRD, ARCHITECTURE, DESIGN, TASKS
├─ legacy/              # the legacy .sql dumps (git-ignored)
└─ (Laravel app at the repo root: simpler for cPanel Git and .cpanel.yml)
   ├─ app/
   │  ├─ Filament/      # Resources: Articles, Categories, Tags, Series, Media, Comments, Projects, Pages, Redirects, NotFoundLogs, Settings
   │  ├─ Content/       # Rendering pipeline (see §5): HtmlSanitizer, CodeHighlighter, TocBuilder, ImageResolver, MarkdownExporter
   │  ├─ Seo/           # MetaBuilder, JsonLd/*, OgImageGenerator, IndexNow
   │  ├─ Media/         # Uploader, VariantGenerator, UsageTracker
   │  └─ Legacy/        # LegacyImporter, HtmlCleaner, RedirectMapper
   ├─ resources/views/
   │  ├─ layouts/       # app (3-col), reading (1-col), base
   │  ├─ components/    # header, footer, theme-toggle, search-palette, toc, code-block, callout, card…
   │  └─ pages/
   └─ resources/css/app.css   # tokens shared with the CTP SaaS (DESIGN.md)
```

## 4. Database Schema (MySQL)

```
users            id, name, email (unique), google_id, google_avatar, last_login_at,
                 -- author profile (AUTHOR-02)
                 display_name, slug (unique), avatar_path, job_title, bio_short, bio_long,
                 website_url, socials (json {github,x,linkedin,youtube,…}), timestamps
articles         id, type enum(tutorial,blog), author_id → users, title, slug (unique per type), excerpt, tldr (text),
                 body (longtext, sanitised HTML), body_rendered (longtext, cached), toc (json),
                 featured_media_id, category_id (null for blog), series_id, series_position,
                 status enum(draft,scheduled,published), published_at, content_updated_at,
                 difficulty enum(beginner,intermediate,advanced) null, reading_minutes, reading_minutes_override,
                 prerequisites (json), tested_with (json [{name,version}]), repo_url, demo_url,
                 faq (json), is_featured, is_pinned, needs_review, comments_enabled,
                 views (unsigned int), kudos (unsigned int), legacy_id, timestamps, softDeletes
                 FULLTEXT(title, excerpt, body_text)   -- body_text = stripped plain text
article_revisions id, article_id, title, body, tldr, created_at, is_autosave
categories       id, parent_id, name, slug (unique), description, icon, sort, seo_title, seo_description, timestamps
tags             id, name, slug (unique), description, timestamps
article_tag      article_id, tag_id
series           id, title, slug, description, cover_media_id, sort, timestamps
projects         id, title, slug, tagline, body (TipTap json), body_rendered, toc (json), cover_media_id,
                 gallery (json [{media_id, caption}]), stack (json list of strings),
                 status (active|maintained|archived), live_url, repo_url, started_at, ended_at,
                 is_featured, is_published, sort, timestamps
project_article  project_id, article_id, sort    -- related tutorials, in reading order
pages            id, title, slug, excerpt, body (TipTap json), body_rendered, toc (json),
                 template (default|toc|about|wide), is_published, sort, timestamps
media            id, folder_id, disk, path, filename, mime, size, width, height,
                 alt, title, caption, credit, variants (json), hash (sha1, for dedupe), timestamps
media_folders    id, parent_id, name, timestamps
media_usages     media_id, usable_type, usable_id           -- "Used in" + delete protection
seo_meta         id, seoable_type, seoable_id, title, description, keywords, canonical, robots, og_media_id
comments         id, article_id, parent_id, author_name, author_email, author_url, body,
                 status enum(pending,approved,spam), is_author, ip_hash, user_agent, timestamps
redirects        id, from_path (unique), to_path, status_code (301|302|410), hits, last_hit_at,
                 source enum(manual,legacy,slug), timestamps          -- slug = auto from SLUG-03
not_found_logs   id, path (unique), hits, referrer, last_seen_at
article_views    article_id, date, views                    -- daily rollups for dashboard charts
settings         group, name, payload (json), locked        -- spatie/laravel-settings (cached; turnstile secret encrypted)
                 groups: general, seo, notice (topbar), ribbon (title, link, new_tab, bg_color, text_color auto), social, scripts, integrations, contact
root_slugs       slug (unique), sluggable_type (article|page), sluggable_id   -- D9 shared root namespace
contact_messages id, topic enum(general,tutorial_question,collaboration,speaking,bug_report,other), name, email,
                 company, article_url, message, status enum(new,read,replied,archived,spam),
                 ip_hash, user_agent, consent_at, replied_at, reply_body, timestamps   -- purged after 12 months
menus            id, location (unique: header, footer_1, footer_2, footer_3, legal), name, timestamps
menu_items       id, menu_id, parent_id (max depth 2), sort, label,
                 type enum(url,route,article,category,tag,series,page,project),
                 linkable_type, linkable_id (null for url/route), url, route_name,
                 target enum(_self,_blank), icon, badge, css_class, is_enabled, timestamps
```

## 5. Content Rendering Pipeline

The editor HTML is processed **once on save**, never on every request:

1. **Sanitise** with `symfony/html-sanitizer` using an allow-list of tags, attributes and our custom block markup.
2. **Normalise headings**: H1 becomes H2, and every H2/H3 gets a slug `id`, deduped.
3. **Build the TOC** from H2/H3 and store it in `articles.toc` (JSON).
4. **Highlight code**: `<pre><code class="language-x" data-filename>` becomes `tempest/highlight` output with the copy button and label markup.
5. **Resolve images**: a `data-media-id` reference becomes `<img srcset sizes width height loading="lazy" decoding="async">` from the media variants. The first image is `fetchpriority="high"`.
6. **Expand custom blocks**: callout, code tabs, steps and YouTube (lite embed).
7. **Exports**: `body_text` (plain, for FULLTEXT and the word count) and a Markdown version, which is generated on demand and cached.

The result is stored in `body_rendered`. Pages and projects use the same editor and pipeline through the `HasRenderedBody` trait. After pipeline changes, `php artisan ctp:rerender` re-renders articles, pages and projects (`--only=page` to narrow it).

### 5.1 Editor (spike T0.12, 2026-10-16, decision D16)

**Filament 5 RichEditor** (TipTap). The editor state is stored as **TipTap JSON** (`->json()`). The render pipeline produces the HTML.

What was verified by round-tripping 4 real legacy articles (ids 9, 30, 45, 66) through the editor schema (HTML → JSON → HTML):

| Content | Result |
|---|---|
| Paragraphs, `<br>`, `<strong>`, links, images, tables (30 cells) | Kept exactly |
| Code text inside `<pre><code>` | **Identical**. Only `\r\n` becomes `\n`, which is what we want |
| Code language | **Lost** in the legacy form `<pre class="language-php"><code>` (Prism style). It **is kept** when on `<code class="language-php">`, so the converter moves it (§10.3) |
| Inline `style`, `<span>` | Dropped. Fine, because the converter strips them anyway |
| `<iframe>` (CodePen) | Dropped, so the converter must turn it into the CodePen custom block **before** loading it into the editor |

**Features we build on (all native Filament 5 APIs, no third-party plugin):**
- **Custom blocks** (`RichContentCustomBlock`): an editor modal form, a preview in the editor, and `toHtml()` for output.
  - **Code**: Filament's `CodeEditor` (CodeMirror) for the code, plus language, filename, highlight lines and line numbers
  - Callout, Code tabs, Terminal, Steps, YouTube, CodePen
- **Custom tool** (`RichEditorTool` + an Action), modelled on `AttachFilesAction` and `EditorCommand`: **Insert from Media Library** opens our picker and inserts the image node with `data-media-id`.
- The native `codeBlock` stays for quick pasted snippets. The pipeline highlights both kinds.
- Tables, links and H2–H4 are native.

## 6. URL Structure

**Decision D9: tutorials live at the root (`domain/{slug}`).** Tutorials are the core content, so they get the shortest URL. Every other content type has a prefix, so it never competes for the root namespace.

| URL | Page |
|---|---|
| `/` | Home |
| **`/{slug}`** | **Tutorial**, such as `codethepixel.com/cakephp-4-crud` |
| **`/{slug}`** | **Static page**, such as `/about` or `/privacy`. Same namespace as tutorials (see the rules below) |
| `/{slug}.md` | Markdown version of a tutorial or page (GEO) |
| `/tutorials` | Tutorials index (`?category=&tag=&difficulty=&sort=`) |
| `/category/{slug}` | Category archive (flat slugs; breadcrumbs show the hierarchy) |
| `/tag/{slug}` | Tag archive |
| `/series`, `/series/{slug}` | Series index and detail |
| `/blog`, `/blog/{slug}`, `/blog/{slug}.md` | Blog |
| `/projects`, `/projects/{slug}` | Projects |
| `/author/{slug}` | Author profile and their articles |
| `/contact` | Contact form (a built-in route) |
| `/search?q=` | Search results. `/api/search?q=` returns JSON for the Ctrl+K palette |
| `/feed`, `/feed/blog`, `/feed/category/{slug}` | RSS/Atom |
| `/sitemap.xml` (+ children), `/robots.txt`, `/llms.txt`, `/llms-full.txt` | SEO/GEO |
| `/og/{type}/{id}.png` | Generated OG images (cached on disk) |
| `/media/...` | Uploaded media |
| `/admin/*` | Filament |
| `/auth/google/redirect`, `/auth/google/callback` | Admin login |

**Rules that make `/{slug}` safe:**

1. **One shared namespace.** A `root_slugs` table (`slug` unique, `sluggable_type`, `sluggable_id`) holds every tutorial and page slug. A tutorial and a page can never share a slug. A unique index enforces it at DB level.
2. **Reserved words, generated automatically.** At boot, the first path segment of every registered route (`tutorials`, `blog`, `category`, `tag`, `series`, `projects`, `author`, `contact`, `search`, `feed`, `admin`, `api`, `auth`, `media`, `og`, `build`, `storage`, `sitemap.xml`…) is collected and blocked as a slug. A static list adds future-proof words (`login`, `docs`, `shop`, `account`). Validation rejects them in the admin.
3. **The catch-all route is registered last** (`Route::get('/{slug}', …)->where('slug', '[a-z0-9-]+')`). It resolves `root_slugs` and dispatches to the tutorial or page controller. Missing slugs fall through to the redirect check and the 404 log.
4. **Adding a new section later** (for example `/courses`) first runs `php artisan ctp:check-slug-conflicts`, which lists any existing tutorial or page that would be shadowed. A Pest test fails CI if a route and a slug collide.
5. Slug history (SLUG-03) applies here too: renaming `/old-slug` creates `/old-slug` → `/new-slug` as a 301.

**Trade-offs we accept:**
- Search Console can't filter "all tutorials" by folder. Use the tutorials sitemap as a filter instead, which works the same way.
- The URL doesn't say the content type. That's fine for readers, and the breadcrumbs and JSON-LD say it explicitly.

**Middleware order on public routes:**

1. `RedirectMiddleware` (checks the `redirects` table, cached map)
2. Normalise URLs: lowercase and strip the trailing slash, with a 301
3. `ResponseCache`
4. Controller

On a 404, the path is logged to `not_found_logs`.

## 7. Caching

- **Response cache** (file) for anonymous GET requests on public pages, with a TTL of 1 week. Cache tags aren't available on file stores, so the **whole cache is cleared** on any content save. That is acceptable at this scale.
- Admin and preview requests bypass the cache.
- The view counter stays accurate with cached pages: a tiny `POST /api/views/{id}` beacon (via `navigator.sendBeacon`) after 5 seconds on the page. It filters bots by user agent and dedupes per session.
- Rendered body, TOC and Markdown are stored in the DB (§5).
- **Settings and menus** are cached forever and the cache is cleared on save. This also clears the response cache, because they appear on every page.
- **Topbar notification and ribbon:** the server renders them into the page as normal, and the response cache is cleared when they are saved. A scheduled-window notice is enforced by a minutely check in the scheduler, which clears the cache when a start or end time passes. Rendering them server-side, instead of fetching them with JS, avoids layout shift.

## 8. Auth Flow (admin)

`/admin/login` shows a single **Continue with Google** button. Socialite then redirects to the callback, which checks the email against `config('ctp.admin_emails')`:

- If it matches, the user is upserted and logged in, then sent to `/admin`.
- If not, the response is a 403 and the attempt is logged.

Filament's `canAccessPanel()` re-checks the allow-list. In the local environment only, a "Quick login" button skips OAuth.

## 9. Environments & Deployment

### Development (Docker)

| Service | Image | Port |
|---|---|---|
| `app` | php:8.4-fpm with extensions gd (webp, avif), intl, pdo_mysql, zip, exif, bcmath, opcache | — |
| `nginx` | nginx:alpine | **8100** |
| `mysql` | **mysql:8.0.44** (same as production) | **3317** |
| `phpmyadmin` | phpmyadmin:5.2 | **8101** |
| `vite` | the app image, which includes Node 22 (`npm run dev`, HMR) | **5183** |
| `mailpit` | axllent/mailpit | **8125** |
| `queue` | the app image, `queue:listen` (dev only) | — |
| `scheduler` | same as `app`, runs `schedule:work` | — |

A second database, `ctp_legacy`, is created in the same MySQL for the import.

### Production (shared hosting, cPanel)

Constraints: PHP 8.4, MySQL 8.0.44, **no Node, no Supervisor, cron only**. Deployment uses **cPanel Git™ Version Control**.

**Branch strategy**

| Branch | Contents | Who writes it |
|---|---|---|
| `develop` | Daily work | You |
| `main` | Source code, release-ready | Merge from `develop` |
| `production` | **Built release**: source, `vendor/` (no dev) and `public/build/` (Vite output) | **GitHub Actions only**, on every push to `main` |

**Compression and caching.** `public/.htaccess` turns on `mod_deflate` and sets `Cache-Control` (a year, `immutable`, for the hashed `/build/` files; 30 days for `/storage/` and `/media/`). The dev nginx config mirrors these rules, so local Lighthouse runs match production. Without compression, the 130 KB stylesheet alone costs about 10 Lighthouse points on mobile.

The server has no Node, so `public/build` must arrive pre-built. Composer *may* exist on the host (`/opt/cpanel/composer/bin/composer`). Shipping `vendor/` in the `production` branch removes that dependency entirely. T0.13 confirms it.

**Flow**

1. Push to `main`. GitHub Actions runs the tests, then `composer install --no-dev -o` and `npm ci && npm run build`, and force-pushes the result to the `production` branch.
2. In cPanel, open Git Version Control, select the repo, and choose **Pull or Deploy → Update from Remote**, then **Deploy HEAD Commit**.
   - The repo is cloned once via SSH URL with a read-only **deploy key**, because it's a private repo.
3. `.cpanel.yml` runs the deployment tasks:

```yaml
---
deployment:
  tasks:
    - export APP=/home/USER/ctp-app            # live app dir (outside public_html)
    - /bin/rsync -a --delete --exclude='.env' --exclude='storage/' --exclude='.git' ./ $APP/
    - cd $APP && /usr/local/bin/php artisan migrate --force
    - cd $APP && /usr/local/bin/php artisan optimize
    - cd $APP && /usr/local/bin/php artisan responsecache:clear
```

**Server layout**

```
/home/USER/
├─ repositories/ctp/      # cPanel Git clone (production branch)
├─ ctp-app/               # live app (rsync target); .env and storage/ live here permanently
└─ public_html/  →  symlink to ctp-app/public   (or the domain doc root set to ctp-app/public)
```

If symlinks are not allowed: set the domain's **document root** to `ctp-app/public` in cPanel → Domains. As a last resort, a `public_html/index.php` shim that requires `../ctp-app/...`.

**Fallback:** FTP upload of the `production` branch as a zip, then the same artisan commands from the cPanel Terminal.

**Runtime**
- **Cron (cPanel → Cron Jobs):** `* * * * * /usr/local/bin/php /home/USER/ctp-app/artisan schedule:run >> /dev/null 2>&1`. The scheduler runs `queue:work --stop-when-empty --max-time=50` every minute with `withoutOverlapping()`.
- **Drivers:** `QUEUE_CONNECTION=database`, `CACHE_STORE=file`, `SESSION_DRIVER=database`. Mail goes through the hosting SMTP (`noreply@codethepixel.com`).
- **Storage:** run `php artisan storage:link` once. If that is blocked, use a `public_media` disk that writes straight into `public/media`.

## 10. Legacy Import

### 10.1 Source

The dumps live in `legacy/` (git-ignored):

- `codethepixel_full.sql`: the full CakePHP / ReCRUD v2 database, MySQL 8.0.44
- `articles_only.sql`: a subset

`./dev legacy-import` loads the full dump into the `ctp_legacy` database.

**What is in it (inspected 2026-10-16):**

| Legacy table | Rows | Action |
|---|---|---|
| `articles` | 64 (all published, 2018-05-05 → 2025-07-17, 292k hits, 11k kudos) | **Import** |
| `categories` | 6 | **Import** (see the mapping below) |
| `tags_tags` / `tags_tagged` | 118 tags / 206 links (`fk_model='Articles'`) | **Import**. Drop tags with no links (`counter = 0`) and merge near-duplicates such as `CakePHP4` and `cakephp-4` |
| `settings` | 1 | **Import** into settings (general, seo, notice, ribbon). Captcha and Telegram keys are **not** imported |
| `users` | — | Only author 1 is needed. Create the author profile manually; no passwords are imported |
| `projects` | 8 | Not imported (decision D7). Kept for reference |
| `menus` | 15 | Not imported. They are ReCRUD admin menus, not public navigation |
| `contacts` | 183 | **Not imported** (personal data, PDPA) |
| `faqs` 4, `books` 11, `quotes` 3, `todos`, `audit*`, `user_logs`, `phinxlog` | — | Not imported (see the open questions in PRD §11 for faqs and books) |

### 10.2 Field mapping: `articles` → `articles`

| Legacy | New | Notes |
|---|---|---|
| `id` | `legacy_id` | The upsert key, so the import is idempotent |
| `user_id` (always 1) | `author_id` | The default author |
| `category_id` | `category_id` / `type` | See the category map |
| `title` | `title` | Strip suffixes like ` [Deprecated]` into `needs_review = true` and a deprecated notice |
| `slug` (all mixed case, e.g. `CakePHP-4-CRUD`) | `slug` = lowercase (`cakephp-4-crud`) | The old URL redirects |
| **`body`** | `body` | Run through the **legacy HTML converter** (§10.3), then the render pipeline |
| `poster` + `poster_dir` (`webroot\files\Articles\poster\{Slug}\{file}`) | `featured_media_id` | Downloaded from the **live site** `https://codethepixel.com/files/Articles/poster/{Slug}/{file}` (verified HTTP 200). **`poster_dir` comes in two formats**: `webroot\files\Articles\poster\{Slug}` and `webroot/files/Articles/poster/{Slug}`. Normalise both separators before building the URL |
| `featured` | `is_featured` | 11 articles |
| `hits` | `views` | |
| `kudos` | `kudos` | Kept as a starting count if reactions are built (PRD §11), otherwise stored only |
| `meta_key` | `seo_meta.keywords` | Strip the brand words `CodeThePixel` and `codethepixel` |
| `meta_description` | `seo_meta.description` and the `excerpt` fallback | |
| `publish_on` | `published_at` | Date only, so set 09:00 MYT |
| `modified` | `content_updated_at` | |
| `status`, `published` (all 1) | `status = published` | |
| `icon` (all NULL) | — | |

**Category map:**

| Legacy category | New |
|---|---|
| 1 CakePHP (42) | Category **CakePHP** |
| 2 Joomla! (3) | Category **Joomla** |
| 3 Tools & Software (3) | Category **Tools & Software** |
| 4 Misc (11) | Category **Misc**. Re-categorise by hand after the import |
| 5 Blog (4) | **Not imported** (D10: the blog starts fresh). Redirects: *Using Emmet in VS Code* → the tutorial *Boost Your Productivity with Emmet in VS Code*. The other 3 return **410 Gone** |
| 6 User Interface (1) | Category **User Interface** |

### 10.3 Legacy HTML Converter (`App\Legacy\HtmlCleaner`)

The legacy body was written in TinyMCE. **It has no `<h1>`–`<h6>` at all.** It contains about 1,100 `<br>`, about 400 inline `style` attributes, Prism code blocks (`<pre><code class="language-php">`, 379 of them), Bootstrap and Font Awesome classes, and one CodePen iframe.

Conversion rules, in order:

0. **Normalise line endings** (`\r\n` → `\n`).
1. **Split `<br><br>` runs into paragraphs.** A single `<br>` inside text stays.
2. **Headings:** a `<strong>…</strong>` that stands alone on its line (at the start of a `<p>`, or between `<br>`s), or inside `<span style="font-size: 14pt">`, becomes `<h2>`. A `<strong>` inside a coloured span (`#3598db`) becomes `<h3>`. A threshold of under 80 characters and no ending period avoids false positives.
3. **"File Location: …\path\file.php"** right before a `<pre>` becomes the code block's `data-filename` (backslashes become `/`), and the text line is removed.
4. **Code blocks** become the **Code custom block** (config: language, filename, code). If one is left as a native code block, the language class **moves from `<pre>` to `<code>`** (otherwise TipTap drops it, see §5.1):
   - `language-markup` becomes `language-html`
   - `line-numbers` sets the line-numbers flag
   - the code text is kept byte-for-byte (entity-decoded once)
5. **Strip:** every `style`, `class` (Bootstrap and Font Awesome), `&nbsp;` runs, empty `<p>`, and `<span>` wrappers with no meaning.
6. **Tables:** keep the structure and drop the widths and heights. Wrap the table for horizontal scroll. Promote the first row to `<thead>` when it is all `<strong>`.
7. **Images:** `src` values `/ctp/webroot/upload/images/tutorial/...`, `/ctp/webroot/tutorial/...` and `../img/tutorial/...` are looked up in `legacy/webroot/` (see below) and imported into the media library (folder `legacy/{slug}`). `src` becomes `data-media-id`. The old `alt` is kept, or the alt is generated from the filename and flagged for review.
8. **iframe:** a CodePen embed becomes the CodePen block (click to load). This must happen **before** the editor schema sees the HTML, because the schema drops `<iframe>`.
9. Unknown elements (a demo `<form>`, an `h-captcha` div) are escaped into a code block or removed, and listed in the report.

> **Images are missing (D11):** all 25 inline images (in about 12 articles, mostly CakePHP 4) **already return 404 on the live site, and the Wayback Machine has no copy either** (checked 2026-10-16). The importer tries each source in order:
> 1. `legacy/webroot/` (if the old files turn up later)
> 2. the live URL
> 3. the Wayback Machine `id_` raw URL
> 4. a **placeholder figure** ("Screenshot unavailable"), with the article flagged `needs_review`
>
> The report lists every affected article, so screenshots can be redone or the image removed. Many of these are deprecated CakePHP 4 tutorials anyway.

### 10.4 Redirects created (`source = legacy`)

| Old | New |
|---|---|
| `/codethepixel/articles/{Slug}` | `/{slug}` (lowercase) |
| `/codethepixel/articles?tag={tag}` | `/tag/{tag}` |
| `/codethepixel/articles` | `/tutorials` |
| `/codethepixel` (and `?page=N`, `?sort=…`) | `/` |
| `/files/Articles/poster/{Slug}/{file}` | the new media URL (images keep their search traffic) |

### 10.5 Run

1. `php artisan ctp:import-legacy --dry-run`: mapping report with counts, warnings, unconverted HTML and missing images.
2. `php artisan ctp:import-legacy`: upserts everything, then re-renders.
3. The report goes to `storage/logs/legacy-import-{date}.md`, including a checklist of articles that need a manual look.

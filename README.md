# CTP CMS

The tutorial CMS behind [codethepixel.com](https://codethepixel.com): a fast, text-first site for programming
tutorials, with an admin built for writing them. Laravel 13, Filament 5, Blade, Tailwind CSS v4 and Alpine.js.
It runs on ordinary cPanel shared hosting: no Node, no Redis, no queue worker on the server, cron only.

## Features

**Writing**
- Rich editor with code blocks, code tabs, terminal output, callouts, steps, YouTube and CodePen embeds
- Tutorials, blog posts, series, projects and static pages; drafts, previews, scheduling, revisions
- Media library with WebP variants, usage tracking and a picker
- Technologies (Simple Icons logos), categories, tags and difficulty levels

**Reading**
- Three-column tutorial layout with a category sidebar, "On this page" and focus mode
- Search palette (Ctrl/⌘ K) on MySQL FULLTEXT
- Comments with moderation and reply emails; contact form with an inbox
- Light and dark themes; Lighthouse 98–100 on every category

**SEO and AI**
- Meta, canonical, JSON-LD, sitemaps, RSS, generated Open Graph cards
- IndexNow, `llms.txt`, Markdown copies of every article, per-bot AI crawler control
- Slug history with automatic 301s, a 404 log and redirects

**Operations**
- Full-page response cache, CSP and security headers
- Nightly backups (local and S3-compatible) with a restore check
- Google sign-in for admins only

## Run it locally

Requires Docker. Everything runs through `./dev`; no PHP or Node on the host.

```bash
cp .env.example .env
./dev up
./dev composer install
./dev npm install
./dev artisan key:generate
./dev migrate
```

Open http://localhost:8100 (admin at `/admin`). phpMyAdmin is on port 8101 and Mailpit on 8125.
Set `ADMIN_EMAILS` and the Google OAuth keys in `.env` to sign in; outside production a quick-login button
is offered instead.

Checks before a commit:

```bash
./dev lint
./dev stan
./dev test
```

Tests use MySQL (`ctp_testing`), because search relies on FULLTEXT.

## Documentation

- [docs/PRD.md](docs/PRD.md): features and decisions
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md): schema, rendering, URLs, deployment
- [docs/DESIGN.md](docs/DESIGN.md): design tokens and components
- [docs/DEPLOY.md](docs/DEPLOY.md): deploying to cPanel
- [docs/LAUNCH-CHECKLIST.md](docs/LAUNCH-CHECKLIST.md): go-live checks

## Deployment

Pushing to `main` runs lint, static analysis and tests in GitHub Actions, then builds a release
(`vendor/` without dev packages and `public/build/`) onto the `production` branch. cPanel Git pulls that
branch and `.cpanel.yml` installs it. See [docs/DEPLOY.md](docs/DEPLOY.md).

## License

The code is open source under the [MIT License](LICENSE). The "Code The Pixel" name, logo and the
content of codethepixel.com are not covered by it. Security issues: see [SECURITY.md](SECURITY.md).

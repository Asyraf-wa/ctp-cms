# CTP — Code The Pixel

Tutorial CMS that replaces the legacy CakePHP site at codethepixel.com.
Laravel 13 + Filament 5 (admin at `/admin`), Blade + Tailwind v4 + Alpine on the public site.

## Read first
- `docs/PRD.md` — features (IDs like ART-01, SET-03) and the decisions log (D1…)
- `docs/ARCHITECTURE.md` — schema, render pipeline, URLs (§6, root slugs), deploy, legacy import (§10)
- `docs/DESIGN.md` — tokens and components (brand shared with the CTP SaaS in `../codethepixel`)
- `docs/TASKS.md` — task list; tick `[x]` when done and respect each phase's exit criteria

## Working rules
- Everything runs in Docker through `./dev` (no host PHP/Node): `./dev up`, `./dev artisan …`, `./dev composer …`, `./dev npm …`, `./dev test`, `./dev lint`, `./dev stan`.
- URLs: app http://localhost:8100 · phpMyAdmin http://localhost:8101 · Mailpit http://localhost:8125 · MySQL `localhost:3317`.
- Tests use MySQL (`ctp_testing`), not SQLite: production is MySQL 8.0.44 and we rely on FULLTEXT.
- Production is cPanel shared hosting: no Node, no queue worker, cron only. Don't add anything that needs Redis, Supervisor, Chrome or a Node runtime on the server.
- English only for UI strings and content.
- `legacy/` holds the old database dump (personal data). It is git-ignored; never commit it or copy rows from `contacts`/`users` anywhere.
- Before committing: `./dev lint`, `./dev stan`, `./dev test` all green.

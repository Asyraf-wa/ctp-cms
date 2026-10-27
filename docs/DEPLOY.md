# Deploying CTP to cPanel

Step-by-step guide for a clean cPanel account (T6.03). The design behind it is in ARCHITECTURE §9.
`USER` below is your cPanel username; `codethepixel.com` is the live domain.

**How releases work.** You push to `main`. GitHub Actions runs lint, Larastan and the tests, then builds
`vendor/` (no dev packages) and `public/build/` and adds a commit to the `production` branch (a normal,
linear history, so the host can simply pull). The cPanel clone of that branch **is** the live app: nothing is
copied. To update, you press **Update from Remote** and then **Deploy HEAD Commit**; `.cpanel.yml` runs the
migrations and cache steps for you, so **no Terminal is needed** (it takes seconds). The server needs no Node
and no Composer.

```
/home/USER/
├─ repositories/ctp/   cPanel Git clone of the production branch = the live app
│                      (.env, storage/ and public/storage live here; git never touches them)
└─ public_html/        shared with other sites; codethepixel → symlink to repositories/ctp/public
```

---

## 1. Before you start

On the host (cPanel → **Select PHP Version** / **MultiPHP Manager**):

- PHP **8.4** for the domain, with `gd` (WebP), `intl`, `zip`, `exif`, `bcmath`, `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`.
- `memory_limit` 256M or more, `upload_max_filesize` and `post_max_size` at least 12M (media uploads).
- **Terminal** or SSH access (cPanel → Terminal).
- Find the PHP 8.4 CLI path once; it is used by `.cpanel.yml` and cron:

```bash
ls /opt/cpanel/ea-php84/root/usr/bin/php
```

If that path does not exist, run `which php` and `php -v` and use whichever binary reports 8.4. Then edit
the `PHP=` line at the top of `.cpanel.yml` to match.

On GitHub:

- A **private** repository with this code on `main`.
- Settings → Actions → General → Workflow permissions: **Read and write** (the release job pushes `production`).
- Push `main` once and wait for the CI run to finish. The `production` branch appears when it is green.

---

## 2. Database

cPanel → **MySQL® Databases**:

1. Create the database, for example `USER_ctp`.
2. Create a user with a long random password and add it to the database with **ALL PRIVILEGES**.
3. Create a second, **empty** database, for example `USER_ctp_restore`, and give the same user all privileges.
   `php artisan ctp:backup-verify` restores into it to prove the backups work. Nothing else uses it.

---

## 3. Clone the repository

The repository is public, so cPanel needs no deploy key.

cPanel → **Git™ Version Control** → Create:

- Clone URL: `https://github.com/Asyraf-wa/ctp-cms.git`
- Repository path: `repositories/ctp`
- Name: `ctp`

Then open the repository → **Basic Information** → **Checked-out branch: `production`** (not `main`: `main` has
no `vendor/` or built assets).

If the clone is still on `main`, switch the branch there and press **Update from Remote**.

---

## 4. First deploy

Do this after steps 5 to 8 have created `.env` and the symlink. Git Version Control → `ctp` → **Update from
Remote**, then **Deploy HEAD Commit**. `.cpanel.yml` creates the `storage/` folders, runs `storage:link`,
`migrate --force`, `settings:clear-cache`, `optimize` and `responsecache:clear`. The deploy fails until `.env`
exists, which is expected.

If the deploy button is greyed out, the clone has local changes: leave the repository untouched on the server
(never edit files in it) and `.env`, `storage/` and `public/storage` are ignored by git, so they never count.

---

## 5. The `.env` file

Create `~/repositories/ctp/.env` (cPanel File Manager, or `nano ~/repositories/ctp/.env` in Terminal) and fill in the blanks.
It is never overwritten by a deploy.

```dotenv
APP_NAME="Code The Pixel"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://codethepixel.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USER_ctp
DB_USERNAME=USER_ctp
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true

CACHE_STORE=file
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

# SMTP: a cPanel email account (Email Accounts → Connect Devices shows host and port)
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=mail.codethepixel.com
MAIL_PORT=465
MAIL_USERNAME=noreply@codethepixel.com
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@codethepixel.com"
MAIL_FROM_NAME="${APP_NAME}"

# Admin sign-in (Google). Only these accounts can open /admin.
ADMIN_EMAILS=you@gmail.com
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Backups (OPS-03): local disk, plus "offsite" once an S3-compatible bucket exists
BACKUP_DISKS=backups
BACKUP_NOTIFY_EMAIL=you@example.com
BACKUP_ARCHIVE_PASSWORD=
BACKUP_VERIFY_DATABASE=USER_ctp_restore
# BACKUP_DISKS=backups,offsite
# BACKUP_S3_KEY=
# BACKUP_S3_SECRET=
# BACKUP_S3_REGION=auto
# BACKUP_S3_BUCKET=
# BACKUP_S3_ENDPOINT=

# Hosts of analytics scripts pasted in Site settings → Scripts (space-separated), for the CSP
CSP_EXTRA_HOSTS=
```

Then generate the key:

```bash
cd ~/repositories/ctp && /opt/cpanel/ea-php84/root/usr/bin/php artisan key:generate
```

No Terminal? Make the key yourself: `APP_KEY=base64:` followed by 32 random bytes in Base64. On Windows PowerShell:

```powershell
"base64:" + [Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }))
```

Paste the result after `APP_KEY=` once and never change it afterwards (it encrypts settings and sessions).

Notes:

- **`APP_KEY`.** If you move the local database (step 7, option A) and saved a Turnstile secret
  locally, either reuse the local `APP_KEY` or re-enter the secret in the admin afterwards. It is the only
  encrypted setting. Store a copy of the production `APP_KEY` outside the server: backups need it.
- **SMTP.** If port 465 fails, try `MAIL_SCHEME=smtp` with `MAIL_PORT=587`.
- **Response cache, robots and IndexNow** switch on by themselves when `APP_ENV=production`.
- `APP_DEBUG` must stay `false`: debug pages show the `.env` values.

---

## 6. Point the domain at `public/`

Only the `public/` folder may be reachable from the web: the rest holds `.env`, `vendor/` and the database
settings. Never put the app itself inside `public_html`.

**Domain with its own document root:** cPanel → **Domains** → the domain → **Document Root** →
`repositories/ctp/public`.

**Several sites share `public_html`** (for example a root `.htaccess` that maps `codethepixel.com` to a
`codethepixel` folder): make that folder a symlink to the app's `public/`, using File Manager → **Symlink**
(or `ln -s` if you do have a Terminal): link path `public_html/codethepixel`, pointing to
`/home/USER/repositories/ctp/public`. The root `.htaccess` stays as it is:

```apache
RewriteCond %{HTTP_HOST} ^(www\.)?codethepixel\.com$ [NC]
RewriteCond %{REQUEST_URI} !^/codethepixel/
RewriteRule ^(.*)$ /codethepixel/$1 [L]
```

Check that secret files are not reachable (browser, each should be 403 or 404, never file contents):
`https://codethepixel.com/.env`, `https://codethepixel.com/codethepixel/.env`, `https://codethepixel.com/composer.json`.

The release carries the PHP 8.4 handler in `public/.htaccess` and the http → https redirect, so there is
nothing to add by hand. If the host does not allow symlinks, set `MEDIA_ROOT` and `MEDIA_URL` in `.env`
(see ARCHITECTURE §9) so media lives under `public/`.

---

## 7. Content

Pick one.

### A. Move the local site (recommended)

Everything you edited locally (articles, settings, menus, pages, media) comes along.

On your computer, export the database without the throwaway tables:

```bash
docker compose exec -T mysql sh -c 'mysqldump -uroot -proot --single-transaction --no-tablespaces --set-gtid-purged=OFF ctp --ignore-table=ctp.sessions --ignore-table=ctp.cache --ignore-table=ctp.cache_locks --ignore-table=ctp.jobs --ignore-table=ctp.failed_jobs --ignore-table=ctp.job_batches' > ctp.sql
docker compose exec -T mysql sh -c 'mysqldump -uroot -proot --no-data --no-tablespaces --set-gtid-purged=OFF ctp sessions cache cache_locks jobs failed_jobs job_batches' >> ctp.sql
```

The second command adds the skipped tables back as empty tables.

Pack the media:

```bash
tar -czf media.tgz -C storage/app/public .
```

On the server:

1. phpMyAdmin → `USER_ctp` → **Import** → `ctp.sql`. If the file is too big for the upload limit, upload
   it with File Manager and run `mysql -u USER_ctp -p USER_ctp < ctp.sql` in Terminal.
2. Upload `media.tgz` to `~/repositories/ctp/storage/app/public/` with File Manager, select it and press **Extract**
   (or `tar -xzf media.tgz -C ~/repositories/ctp/storage/app/public` in a Terminal), then delete `media.tgz`.
3. Delete `ctp.sql` and `media.tgz` from the server once imported.

### B. Start fresh and import the legacy site

Use this only if the local database should not be moved. The legacy dump contains personal data; delete it
from the server when done.

1. Create a third database `USER_ctp_legacy`, import the legacy CakePHP dump into it, and set
   `LEGACY_DB_DATABASE=USER_ctp_legacy` in `.env`.
2. Run these while the **old site is still live** (posters and images are downloaded from it). Without a Terminal,
   run each one as a one-off cron job (see *Updating the live site*):

```bash
cd ~/repositories/ctp
/opt/cpanel/ea-php84/root/usr/bin/php artisan migrate --force
/opt/cpanel/ea-php84/root/usr/bin/php artisan ctp:import-legacy --dry-run
/opt/cpanel/ea-php84/root/usr/bin/php artisan ctp:import-legacy
/opt/cpanel/ea-php84/root/usr/bin/php artisan db:seed --class=LaunchPagesSeeder --force
```

Add `--with-posters` to the import if the legacy posters should become featured images.
3. Drop `USER_ctp_legacy` and remove `LEGACY_DB_DATABASE` from `.env`.

`LaunchPagesSeeder` overwrites About, Privacy Policy and Terms. Run it once only.

---

## 8. Cron and a test email

**The permanent cron entry.** cPanel → **Cron Jobs** → Add New Cron Job, "Once Per Minute" (`* * * * *`).
Use `$HOME/` plus the folder that really holds `artisan` (File Manager shows it: `repositories/ctp`, or
`public_html/codethepixel` until the app is moved out of `public_html`):

```bash
cd $HOME/repositories/ctp && /opt/cpanel/ea-php84/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

This one entry runs everything: scheduled publishing, **the mail queue** (every email is queued, so nothing is
sent without it), image variants, view rollups, backups, the topbar notice window and the 12-month contact purge.
It stays; unlike the one-off jobs it is never deleted. Set the cron email to blank, or every minute mails you.
If the app folder moves, edit this entry too.

**Check that it runs, without a Terminal:** open `/admin`. The **Background jobs** tile says *Running* with
"Last run … seconds ago" and "mail queue: 0 waiting, 0 failed". *Not running* after two minutes means the entry
is missing or its path or PHP binary is wrong.

**Where owner emails go.** New comments and contact messages are mailed to the **notification email** in
Settings → General (it falls back to the contact email). Both start as `noreply@…`, which nobody reads: put a
real inbox there. The **contact email** is also the reply-to address visitors see.

**Test the mail setup.** A one-off cron job (next minute, then delete it) sends a test email at once, without the
queue, and prints the mailer, the sender, the scheduler state and any SMTP error to `cron.log`:

```bash
cd $HOME/repositories/ctp && /opt/cpanel/ea-php84/root/usr/bin/php artisan ctp:mail-test you@gmail.com >> $HOME/cron.log 2>&1
```

Read `~/cron.log` in File Manager. `Sent.` plus an email in the inbox (check spam) means SMTP works. `NOT SENT:`
shows the reason: wrong `MAIL_HOST`, port or scheme, or a wrong username or password (the account in
`MAIL_USERNAME` must exist in cPanel → Email Accounts). Then do the real flow from the LAUNCH-CHECKLIST: send a
contact message and a comment, and wait a minute or two for the queued emails.

---

## 9. Check

Open `https://codethepixel.com`, then work through **docs/LAUNCH-CHECKLIST.md**. `http://codethepixel.com`
must redirect to https with a 301.

---

## 10. Google sign-in

Google Cloud Console → APIs & Services → **Credentials** → the OAuth client (Web application):

- Authorised JavaScript origins: `https://codethepixel.com`
- Authorised redirect URIs: `https://codethepixel.com/auth/google/callback`

Keep `http://localhost:8100/...` there as well if you still sign in locally. Changes can take a few minutes.

---

## Updating the live site (no Terminal)

1. Push to `main`.
2. Wait for the GitHub Actions run to go green: it adds the new release commit to `production`.
3. cPanel → Git Version Control → `ctp` → **Update from Remote**, then **Deploy HEAD Commit**.

That is all: the deploy runs the migrations and clears the caches. If CI fails, `production` is not touched
and the live site keeps the last good release.

Some releases need a one-off command; they are stated in the commit message or the release notes:

- **Re-render article bodies** (after a change to how content is rendered): `php artisan ctp:rerender`.
- **Seed the launch pages once** (About, Privacy, Terms): `php artisan db:seed --class=LaunchPagesSeeder --force`.

**Without a Terminal**, run such a command from **cPanel → Cron Jobs**: add a job for the next minute, for example
`/opt/cpanel/ea-php84/root/usr/bin/php /home/USER/repositories/ctp/artisan ctp:rerender >> /home/USER/cron.log 2>&1`,
wait for it to run, read `cron.log`, then delete the job.

---

## Troubleshooting

| Symptom | Check |
|---|---|
| 500 error, blank page | `~/repositories/ctp/storage/logs/laravel-*.log`. Usually `.env`, permissions on `storage/` and `bootstrap/cache/`, or a missing PHP extension. |
| Styles missing, page unstyled | `public/build/manifest.json` must exist. The domain must point at `repositories/ctp/public`. No file `public/hot` may exist on the server. |
| Images 404 | `public/storage` link (`storage:link`) or the `MEDIA_ROOT` fallback. |
| Old page still shown after a change | `php artisan responsecache:clear`. Saving content in the admin does this automatically. |
| New setting not saved or "must not be accessed before initialization" | `php artisan settings:clear-cache` (the deploy runs it). |
| Scheduled post not published, mails not sent | The cron entry, and its PHP path. |
| Google sign-in "redirect_uri_mismatch" | Step 10, and `APP_URL` must be `https://`. |
| Admin shows a quick-login button | `APP_ENV` is not `production`. |
| Site runs on the wrong PHP version after a deploy | MultiPHP Manager → set PHP 8.4 for the domain again. `.cpanel.yml` keeps its handler block in `public/.htaccess` from then on. |

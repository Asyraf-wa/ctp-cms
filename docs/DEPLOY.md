# Deploying CTP to cPanel

Step-by-step guide for a clean cPanel account (T6.03). The design behind it is in ARCHITECTURE §9.
`USER` below is your cPanel username; `codethepixel.com` is the live domain.

**How releases work.** You push to `main`. GitHub Actions runs lint, Larastan and the tests, then builds
`vendor/` (no dev packages) and `public/build/` and force-pushes the result to the `production` branch.
cPanel pulls `production`, and `.cpanel.yml` copies it into the live app folder and runs the artisan steps.
The server needs no Node and no Composer.

```
/home/USER/
├─ repositories/ctp/   cPanel Git clone of the production branch (never edit here)
├─ ctp-app/            live app: .env and storage/ live here permanently
└─ public_html/        not used by CTP (the domain points at ctp-app/public)
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

## 3. Clone the repository with a deploy key

The repository is private, so cPanel reads it with a read-only key.

1. cPanel → **SSH Access** → Manage SSH Keys → **Generate a New Key**. Name it `ctp_deploy`, no passphrase, RSA 4096.
   Then **Manage** → **Authorize** it.
2. View the **public** key and copy it.
3. GitHub → repository → Settings → **Deploy keys** → Add deploy key. Paste it, title `cPanel`, leave
   "Allow write access" **off**.
4. Tell SSH to use that key for GitHub. In cPanel Terminal:

```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
printf 'Host github.com\n  IdentityFile ~/.ssh/ctp_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
chmod 600 ~/.ssh/config
ssh -T git@github.com
```

Answer `yes` to the host key prompt. The last line should greet you by repository name.

5. cPanel → **Git™ Version Control** → Create:
   - Clone URL: `git@github.com:OWNER/REPO.git` (the SSH URL, not HTTPS)
   - Repository path: `repositories/ctp`
   - Name: `ctp`

6. Open the repository → **Basic Information** → Checked-out branch: **production**.

---

## 4. First deploy

In Git Version Control → `ctp` → **Pull or Deploy**: **Update from Remote**, then **Deploy HEAD Commit**.

This creates `~/ctp-app` with the code, `vendor/` and `public/build/`, and an empty `storage/` skeleton.
The artisan steps in the first run fail until `.env` exists, which is expected. Finish steps 5 to 8, then
deploy once more.

---

## 5. The `.env` file

Create `~/ctp-app/.env` (cPanel File Manager, or `nano ~/ctp-app/.env` in Terminal) and fill in the blanks.
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
ADMIN_EMAILS=asyraf.wahianuar@gmail.com
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Backups (OPS-03): local disk, plus "offsite" once an S3-compatible bucket exists
BACKUP_DISKS=backups
BACKUP_NOTIFY_EMAIL=asyraf.wahianuar@gmail.com
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
cd ~/ctp-app && /opt/cpanel/ea-php84/root/usr/bin/php artisan key:generate
```

Notes:

- **`APP_KEY`.** If you move the local database (step 7, option A) and saved a Turnstile secret
  locally, either reuse the local `APP_KEY` or re-enter the secret in the admin afterwards. It is the only
  encrypted setting. Store a copy of the production `APP_KEY` outside the server: backups need it.
- **SMTP.** If port 465 fails, try `MAIL_SCHEME=smtp` with `MAIL_PORT=587`.
- **Response cache, robots and IndexNow** switch on by themselves when `APP_ENV=production`.
- `APP_DEBUG` must stay `false`: debug pages show the `.env` values.

---

## 6. Point the domain at `ctp-app/public`

cPanel → **Domains** → `codethepixel.com` → Manage → **Document Root**: `ctp-app/public`.

If the host does not let you change the main domain's root, replace `public_html` with a symlink instead
(only when `public_html` holds nothing you need):

```bash
mv ~/public_html ~/public_html.old && ln -s ~/ctp-app/public ~/public_html
```

Then create the storage link and check the permissions:

```bash
cd ~/ctp-app
/opt/cpanel/ea-php84/root/usr/bin/php artisan storage:link
chmod -R u+rwX,go+rX storage bootstrap/cache
```

If `storage:link` is not allowed (symlinks disabled), keep media in the public folder instead. Add to
`.env`, then move any existing files there:

```dotenv
MEDIA_ROOT=/home/USER/ctp-app/public/media
MEDIA_URL=https://codethepixel.com/media
```

`.cpanel.yml` keeps `public/storage` and `public/media` out of its `--delete`, so neither is wiped by a deploy.

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
2. Upload `media.tgz` to `~/ctp-app/storage/app/public/`, then run
   `tar -xzf media.tgz -C ~/ctp-app/storage/app/public && rm media.tgz`.
3. Delete `ctp.sql` and `media.tgz` from the server once imported.

### B. Start fresh and import the legacy site

Use this only if the local database should not be moved. The legacy dump contains personal data; delete it
from the server when done.

1. Create a third database `USER_ctp_legacy`, import the legacy CakePHP dump into it, and set
   `LEGACY_DB_DATABASE=USER_ctp_legacy` in `.env`.
2. Run, while the **old site is still live** (posters and images are downloaded from it):

```bash
cd ~/ctp-app
/opt/cpanel/ea-php84/root/usr/bin/php artisan migrate --force
/opt/cpanel/ea-php84/root/usr/bin/php artisan ctp:import-legacy --dry-run
/opt/cpanel/ea-php84/root/usr/bin/php artisan ctp:import-legacy
/opt/cpanel/ea-php84/root/usr/bin/php artisan db:seed --class=LaunchPagesSeeder --force
```

Add `--with-posters` to the import if the legacy posters should become featured images.
3. Drop `USER_ctp_legacy` and remove `LEGACY_DB_DATABASE` from `.env`.

`LaunchPagesSeeder` overwrites About, Privacy Policy and Terms. Run it once only.

---

## 8. Cron

cPanel → **Cron Jobs** → Add New Cron Job, "Once Per Minute" (`* * * * *`):

```bash
cd /home/USER/ctp-app && /opt/cpanel/ea-php84/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

This one entry runs everything: scheduled publishing, the mail and image queue, view rollups, backups,
the topbar notice window and the 12-month contact purge. Set the cron email to blank, or every minute mails you.

---

## 9. Deploy again and check

Git Version Control → `ctp` → **Update from Remote** → **Deploy HEAD Commit**. This time the artisan
steps succeed: migrations, settings cache clear, `optimize`, response cache clear.

Then open `https://codethepixel.com` and work through **docs/LAUNCH-CHECKLIST.md**.

---

## 10. Google sign-in

Google Cloud Console → APIs & Services → **Credentials** → the OAuth client (Web application):

- Authorised JavaScript origins: `https://codethepixel.com`
- Authorised redirect URIs: `https://codethepixel.com/auth/google/callback`

Keep `http://localhost:8100/...` there as well if you still sign in locally. Changes can take a few minutes.

---

## Updating the live site

1. Push to `main` (merge `develop` first if you work there).
2. Wait for the CI run to go green. It updates `production`.
3. cPanel → Git Version Control → `ctp` → **Update from Remote** → **Deploy HEAD Commit**.

If CI fails, `production` is not touched and the live site keeps the last good release.

**Without Git** (fallback): on GitHub, switch to the `production` branch → Code → Download ZIP. Upload and
extract it over `~/ctp-app` without touching `.env` and `storage/`, then run the artisan steps from
`.cpanel.yml` by hand in Terminal.

---

## Troubleshooting

| Symptom | Check |
|---|---|
| 500 error, blank page | `~/ctp-app/storage/logs/laravel-*.log`. Usually `.env`, permissions on `storage/` and `bootstrap/cache/`, or a missing PHP extension. |
| Styles missing, page unstyled | `public/build/manifest.json` must exist. The domain must point at `ctp-app/public`. No file `public/hot` may exist on the server. |
| Images 404 | `public/storage` link (`storage:link`) or the `MEDIA_ROOT` fallback. |
| Old page still shown after a change | `php artisan responsecache:clear`. Saving content in the admin does this automatically. |
| New setting not saved or "must not be accessed before initialization" | `php artisan settings:clear-cache` (the deploy runs it). |
| Scheduled post not published, mails not sent | The cron entry, and its PHP path. |
| Google sign-in "redirect_uri_mismatch" | Step 10, and `APP_URL` must be `https://`. |
| Admin shows a quick-login button | `APP_ENV` is not `production`. |
| Site runs on the wrong PHP version after a deploy | MultiPHP Manager → set PHP 8.4 for the domain again. `.cpanel.yml` keeps its handler block in `public/.htaccess` from then on. |

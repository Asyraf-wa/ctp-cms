# Launch checklist

Work through this after the first deploy (docs/DEPLOY.md), before and after the DNS switch (T6.04).
Tick each box as you go. Use a private window, so a signed-in session or an extension does not hide a problem.

## 1. Before the DNS switch

Test on the new host with a temporary URL, or by pointing your own computer at it in the hosts file
(`<server IP>  codethepixel.com`).

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://codethepixel.com`, `APP_KEY` set and copied somewhere safe
- [ ] Cron entry saved, and `storage/logs` shows no scheduler errors after a few minutes
- [ ] Media present: open three older tutorials and check their images load
- [ ] The legacy site is still reachable if posters or images still need downloading (option B only)
- [ ] An empty `BACKUP_VERIFY_DATABASE` exists

## 2. DNS and HTTPS

- [ ] Lower the DNS TTL to 300 seconds a day before the switch
- [ ] Point `codethepixel.com` and `www` at the new host
- [ ] cPanel → **SSL/TLS Status** → Run AutoSSL. Both names get a valid certificate
- [ ] `http://` redirects to `https://`, and `www` redirects to the bare domain (or the other way round, but only one)
- [ ] The response has `Strict-Transport-Security` (browser dev tools → Network → the page → Headers)

## 3. Smoke tests

**Public site**

- [ ] Home, All Tutorials, a category, a tag, a series, Blog, Projects, About, Privacy Policy, Terms, Contact
- [ ] A tutorial page: code blocks, copy button, "On this page", images open in the zoom view
- [ ] Search: the Ctrl/⌘ K palette and `/search?q=laravel`
- [ ] Light and dark mode; the mobile menu (scroll down, then open it)
- [ ] An unknown URL shows the 404 page; a legacy URL redirects (`php artisan ctp:check-legacy-urls`)

**Admin**

- [ ] `/admin` signs in with Google; another Google account is refused; no quick-login button
- [ ] Create a draft, preview it, publish it: it appears on the home page straight away
- [ ] Schedule a post two minutes ahead: it goes live on its own (cron)
- [ ] Upload an image: the WebP variants appear within a minute (queue via cron)

**Comments and contact**

- [ ] Post a comment as a visitor: it waits for approval, and the notification email arrives
- [ ] Approve it, reply as admin with "notify replies" on: the reply email arrives
- [ ] Send the contact form: the inbox shows it, and the auto-reply arrives
- [ ] If Turnstile is on: the widget loads and the form still submits

**SEO**

- [ ] `https://codethepixel.com/robots.txt` allows crawling and lists the sitemap
- [ ] `https://codethepixel.com/sitemap.xml` and the article sitemap open
- [ ] `https://codethepixel.com/feed` opens in a feed reader
- [ ] OG image: paste a tutorial URL into the Facebook Sharing Debugger and the LinkedIn Post Inspector; the card shows
- [ ] View source on a tutorial: one canonical link, JSON-LD present

**Operations**

- [ ] `php artisan backup:run` succeeds; `php artisan ctp:backup-verify` passes
- [ ] Lighthouse (mobile, private window) on home and a tutorial: 90+ on every category

## 4. Search engines

- [ ] Google Search Console: add the **Domain** property (DNS TXT record), then submit `https://codethepixel.com/sitemap.xml`
- [ ] Bing Webmaster Tools: import from Search Console, or add the site and submit the same sitemap
- [ ] Site settings → Integrations: set an IndexNow key (Bing, Yandex and others then hear about new posts)
- [ ] URL Inspection on the home page and one tutorial → Request indexing

## 5. The first two weeks

- [ ] Admin → **404 log** every few days. Add a redirect for any real old URL that shows up repeatedly
- [ ] Search Console → Pages: watch "Not found (404)" and "Redirect error"
- [ ] Check the backup email each morning (sent only when something is wrong)
- [ ] After a week, raise the DNS TTL back to its normal value
- [ ] Remove the old hosting only when traffic and Search Console look settled

# Doha AL WAFA: launch checklist

Tick each item before switching the domain to the live store. Items marked **(owner)** need the client's content or accounts.

## 1. Hosting decision (plan Q34)

Pick one before anything else:

- **Laravel Cloud** (simplest): managed PHP, MySQL, queue worker, scheduler, HTTPS and database backups. Deploys from Git.
- **Managed VPS** (Forge, Ploi or similar): cheaper at scale, but you run the worker, cron, backups and updates yourself.

Either way, put **Cloudflare** in front for DNS and caching, and keep product photos on the server's `public` disk or move them to R2/S3 (`MEDIA_DISK`).

**Behind Cloudflare or a load balancer**, the app must trust the proxy, so that rate limits see the real visitor IP and HTTPS is detected. Add this in `bootstrap/app.php` inside `withMiddleware`, and only when the app can be reached **only** through the proxy:

```php
$middleware->trustProxies(at: '*');
```

## 2. Production settings (`.env`)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<domain>`
- [ ] `APP_KEY` generated once (`php artisan key:generate`) and kept safe. Losing it makes the saved ad tokens unreadable.
- [ ] `DB_CONNECTION=mysql` and its credentials
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `QUEUE_CONNECTION=database` (or `redis`), `CACHE_STORE=database` (or `redis`)
- [ ] `LOG_LEVEL=warning`
- [ ] Mail: a real mailer (SMTP, Postmark, SES…), `MAIL_FROM_ADDRESS=orders@<domain>` with SPF/DKIM set up for the domain
- [ ] `MEDIA_DISK` (and the `AWS_*` keys if photos go to R2/S3)

## 3. Every deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link        # first deploy only, for the public photo disk
php artisan optimize            # caches config, routes, views and events
php artisan queue:restart       # workers pick up the new code
```

## 4. Background processes

- [ ] **Queue worker** always running, e.g. `php artisan queue:work --tries=3 --max-time=3600`. It sends order emails, team alerts, contact messages and the server-side purchase events to Meta and TikTok.
- [ ] **Scheduler** every minute: `* * * * * php /path/to/artisan schedule:run`. It clears bags untouched for 30 days (daily) and emails the low-stock digest (08:00 Qatar time).
- [ ] Failed jobs checked weekly: `php artisan queue:failed`

## 5. HTTPS and domain

- [ ] Domain bought and on Cloudflare **(owner)**; SSL mode "Full (strict)"
- [ ] `www` redirects to the bare domain (or the reverse), never both live
- [ ] Every page loads over `https://`. The app forces https links in production and sends HSTS once served over HTTPS.

## 6. Backups

- [ ] Daily database backups, kept 14+ days, stored off the server (Laravel Cloud snapshots, or a nightly `mysqldump` to R2/S3)
- [ ] Product photos (`storage/app/public` or the R2/S3 bucket) backed up too
- [ ] **One test restore** done before launch

## 7. Store content (owner)

- [ ] Products with photos for every color, stock per size, prices
- [ ] Delivery areas and fees, free-delivery amount, days without delivery
- [ ] Settings: WhatsApp number, phone, email, social links, exchange promise, order alert email
- [ ] Pages: About, Delivery, Exchange & returns, Privacy, Terms (English and Arabic, consistent with each other)
- [ ] FAQ answers
- [ ] Owner password is strong and private; one staff account per person
- [ ] Demo or test data removed

## 8. Ads and search

- [ ] Consent bar on (Settings → Privacy) before adding pixels, unless legal advice says otherwise **(owner)**
- [ ] Meta and TikTok pixel IDs and server tokens added; one test order seen in Meta Test Events and TikTok's event tester; **test codes cleared afterwards**
- [ ] Catalog feeds added in Meta Commerce Manager and TikTok Catalog (Settings → Catalog feeds), with a feed password
- [ ] Google Search Console and Bing Webmaster Tools: domain verified, `https://<domain>/sitemap.xml` submitted
- [ ] `https://<domain>/robots.txt` lists the sitemap (outside production it blocks everything, by design)
- [ ] One product URL passes Google's Rich Results Test (product, price, stock)

## 9. Final checks on real devices

Walk the main journeys (social link → product → Order now → thank-you; bag with several items; track order; account) in **English and Arabic** on:

- [ ] iPhone: Safari, and the Instagram, TikTok, Facebook and Snapchat in-app browsers
- [ ] Android: Chrome, and the same in-app browsers
- [ ] Desktop: Chrome and Safari or Edge

And:

- [ ] Lighthouse (mobile) on home, a category, a product and checkout: performance ≥ 90, SEO and accessibility ≥ 95
- [ ] `composer audit` and `npm audit` show no vulnerabilities
- [ ] `php artisan test` passes
- [ ] A real order placed on the live site, checked through admin (emails, WhatsApp buttons, packing slip), then cancelled and its stock checked

## 10. Go live

- [ ] DNS switched; the site and admin open on the domain
- [ ] Short links and the Instagram/TikTok/Snapchat bio links point to the live domain
- [ ] Watch the first day's orders, failed jobs and the log (`storage/logs`)

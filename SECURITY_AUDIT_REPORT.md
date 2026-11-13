# Doha AL WAFA pre-deployment security audit

**Assessment date:** 2026-11-12  
**Application revision:** `cc986dc`  
**Decision:** **INSUFFICIENT EVIDENCE** to approve production deployment

## 1. Executive summary

The review identified **7 confirmed medium** and **4 confirmed low** findings in application source or configuration. It identified **one suspected high** finding involving password-reset link host handling. Confirmation of that risk depends on the production ingress and canonical-host configuration. There were **zero confirmed critical or high** findings.

The priorities are to verify canonical-host enforcement before enabling password recovery, harden privileged authentication, revoke existing sessions after credential changes, and prevent spreadsheet formula interpretation in admin exports. Production HTTPS and cookie behavior, data isolation, and deployment controls require staging evidence before sign-off.

## 2. Application technology overview

| Area | Evidence and architecture |
|---|---|
| Server | PHP 8.4.18, Laravel 13.40.0, Eloquent and Laravel query builder. `composer.json`, `composer.lock`, `bootstrap/app.php`. |
| Browser | Blade, JavaScript, Alpine 3.17.5, Tailwind 4.3.4, Vite 8.3.5. Separate storefront and admin entry points share CSS. English and Arabic routes render server-side. `package-lock.json`, `vite.config.js`, `resources/js/app.js`, `resources/js/admin.js`. |
| Database | Laravel supports configured SQL connections. The sample uses SQLite, tests use in-memory SQLite, and the launch guide specifies MySQL. Migrations cover catalog, orders, inventory, carts, accounts, delivery, coupons, attribution, settings, sessions, and jobs. `config/database.php`, `database/migrations/`, `phpunit.xml`. |
| Identity | A shared `web` session guard and Eloquent user provider serve customers, staff, and owners. Users have a Laravel hashed password cast. Admin and storefront have separate entry points. `config/auth.php:39–68`, `app/Models/User.php:16–49`. |
| Authorization | Gates group the admin routes. Owner access is required for settings, staff management, delivery, coupons, content, and reports. Staff and owners handle catalog, inventory, fulfillment, and customer records. User and cart ownership checks apply in the storefront. `routes/admin.php:59–143`, `app/Providers/AppServiceProvider.php:53–70`. |
| HTTP and APIs | Session-based web routes include JSON responses for autocomplete, cart mutations, wishlist sync, admin variant search, and image upload. Product cards and the cart drawer return HTML fragments. The repository has no separate public token API, inbound payment webhook, JWT, or OAuth login. `routes/web.php`, `routes/admin.php`. |
| Storage | Staff uploads are decoded with Intervention Image 4.4.1 and GD and written as WebP using generated names. Catalog media defaults to the public disk. The filesystem also defines a private local root. Invoices and packing slips are gated controller responses. `app/Services/ImageStore.php`, `config/filesystems.php`, `app/Http/Controllers/Admin/OrderDocumentController.php`. |
| Integrations | Cash on delivery is the only enabled payment method. The app supports WhatsApp links, SMTP-compatible Laravel mail, Meta and TikTok outbound purchase events, browser tracking for Meta/TikTok/Snap/GA4, and Meta/TikTok catalog CSV feeds. `config/shop.php`, `app/Services/Payments/`, `app/Services/ConversionEvents.php`, `resources/js/store/tracking.js`. |
| Async and deployment | Queued mail and conversion events, daily bag pruning, and a daily low-stock digest. Apache front-controller rules are tracked. The launch guide proposes Laravel Cloud or a managed VPS. Production ingress, worker, CI/CD, and container definitions were unavailable in the tracked repository. `routes/console.php`, `app/Mail/`, `app/Jobs/`, `public/.htaccess`, `docs/LAUNCH_CHECKLIST.md`. |

## 3. Scope and testing limitations

Source review covered the tracked application, route and middleware registration, frontend sinks, models and migrations, dependencies, test coverage, deployment guidance, and reachable Git history. Tests used the existing isolated harness. The local environment check classified a few configuration flags without printing values. No production app code, dependencies, or real application data were changed. There was no production, staging, third-party service, or real-user testing. The advisory commands queried package registries.

The assessment uses the OWASP Top 10:2025 categories, ASVS 5.0.0 Level 1 and relevant Level 2 requirements for privileged access, sessions, personal data, and operational controls, and WSTG v4.2 testing areas. Coverage below is by control area and does not establish ASVS conformance. [OWASP Top 10:2025](https://top10.owasp.org/2025/0x00_2025-Introduction/) · [ASVS 5.0.0](https://asvs.owasp.org/downloads/) · [WSTG v4.2](https://wstg.owasp.org/v4.2/)

**Status definitions.** CONFIRMED means the stated behavior or control gap is established by source or a test. SUSPECTED means an essential condition remains unverified. PASSED refers to the specific checked behavior. NOT TESTED marks an unexecuted verification group. N/A marks a control with no corresponding feature in this release. Finding evidence is labeled when it is source-only.

## 4. Attack surface inventory

Artisan reported **177 application routes** at the audited revision. There were 124 admin routes, 48 localized storefront routes, and five shared routes. The appendix maps each route to its handler and registered route middleware. All use the Laravel web stack. Global middleware is configured in `bootstrap/app.php` and is outside the per-route listing.

| Boundary | Entry points and sensitive operations |
|---|---|
| Anonymous browsing | Home, shop, product, category, collection, content, FAQ, localized search and suggestions, product-card fragments, sitemap, and robots. Product preview is gated. `ProductCatalog`, `SearchText`, `Sitemap`, and `StructuredData` handle catalog visibility, query construction, and metadata. |
| Anonymous writes | Cart mutations, delivery and coupon selection, checkout, contact, subscriptions, account registration, login, and reset. The bag cookie selects a database cart. The checkout request validates Qatar address and phone fields and delegates pricing, stock, coupon redemption, and payment recording to `CartService` and `PlaceOrder`. |
| Bearer links and tracking | Order tracking verifies order number and phone, then issues a temporary signed order link. Short links redirect to validated storefront destinations and capture attribution. Catalog feeds can require a shared token. |
| Signed-in customer | Profile, password change, saved addresses, order history, order detail, wishlist sync, and checkout prefill. Ownership follows the user ID. Guest orders do not become account orders by matching a phone number. |
| Staff | Catalog mutations, nested color and image changes, stock adjustments, manual orders, line and status changes, cash collection, invoices, packing slips, customer blocklist, inbox, and subscriber/order export. Sensitive actions include `AdjustStock`, `SyncProductStock`, `UpdateOrderItems`, `UpdateOrderStatus`, and `CollectCashPayment`. |
| Owner | Staff credentials and roles, coupons and delivery zones, content, reporting/export, integration and privacy settings, and catalog feed access. `Settings` caches values and encrypts ad conversion secrets. |
| External and infrastructure | Browser tracking scripts, mail transport, conversion APIs, storage/CDN, database and backups, queue worker, scheduler, reverse proxy, TLS, and build/package inputs. App-origin scripts and authenticated responses are separate trust boundaries from public media. |

### Authorization matrix

Allow means the route has the listed server-side access conditions. Object-level rules still apply. HTTP outcomes for the named representative cases are covered by the existing test suite. The complete route-by-role cross-product is NT-06.

| Operation | Guest | Customer | Staff | Owner | Server evidence |
|---|---|---|---|---|---|
| Admin dashboard | Login required | Deny | Allow | Allow | `routes/admin.php:53–62`, `AdminGatesTest.php`, `Admin/AuthenticatedSessionControllerTest.php` |
| Orders, inventory, catalog, customer operations, order/subscriber exports | Login required | Deny | Allow | Allow | `routes/admin.php:64–126`, `AppServiceProvider.php:53–56` |
| Settings, reports, staff, delivery zones, coupons, pages, FAQs | Login required | Deny | Deny | Allow | `routes/admin.php:128–143`, `Admin/StaffControllerTest.php`, `Admin/SettingsControllerTest.php` |
| Account order detail and address mutation | Login required | Own only | Own only under web guard | Own only under web guard | `AccountOrderController.php:21–27`, `AddressController.php:34–57`, `AccountPagesTest.php` |
| Cart line update/delete | Own bag | Own bag | Own bag | Own bag | `CartItemController.php:59–98`, `CartItemControllerTest.php` |
| Order confirmation / tracking | Signed link or matching lookup details | Same | Same | Same | `routes/web.php:75–77,114–117`, `OrderConfirmationControllerTest.php`, `TrackOrderControllerTest.php` |
| Product image/color child operations | Login required | Deny | Matching parent | Matching parent | `routes/admin.php:93–113`, `ProductColorControllerTest.php`, `ProductImageControllerTest.php` |

## 5. Tests performed and results

| Check | Result | Evidence |
|---|---|---|
| Existing application suite | **PASSED, 809 tests and 2,250 assertions** | `php artisan test --compact`. `phpunit.xml` selects in-memory SQLite, array mail/cache/session, and sync queues. Feature tests use `LazilyRefreshDatabase` and bypass Vite. |
| PHP syntax | PASSED | `php -l` on 383 tracked non-Blade PHP files, exit 0. |
| JavaScript syntax | PASSED | `node --check` on 17 tracked JavaScript files, exit 0. |
| Route inventory | PASSED | `php artisan route:list --json --except-vendor` returned 177 routes. Reviewed route groups and sensitive handler guards. |
| Manifest and advisory checks | PASSED | `composer validate --no-check-publish`, `composer audit --locked --format=json`, and `npm.cmd audit --json` exited 0. See §12. |
| Git secret signature and filename checks | PASSED within signature scope | Redacted, filenames-only search across 14 reachable commits returned zero matches for the selected key/token formats. Sensitive-file history check returned the tracked environment example only. |
| Input and sink tracing | Reviewed | Parameter-bound listing, search, order, and report queries. Reviewed Blade interpolation, escaped JSON-LD, fetched Blade fragments, redirect and link construction, upload decoding, and export writers. Findings below include a confirmed CSV boundary issue. Browser/hostile-input coverage is NT-04 and NT-09. |
| Deployment/config review | Reviewed | Examined environment example, app/session/auth config, Apache rules, headers, and launch guide. Local-only settings classified with no secret output. |
| Feature applicability | N/A where absent | No public JWT/API tokens, OAuth/SSO, NoSQL, online card payment, refund gateway, inbound payment webhooks, or XML ingestion. Missing privileged MFA is recorded in M-02. |

## 6. Critical vulnerabilities

Zero confirmed critical findings within scope.

## 7. High-severity vulnerabilities

### H-01 — Password-reset link origin depends on request host

- **Severity / confidence / status:** High if ingress accepts an unrecognized host / Medium / **SUSPECTED**.
- **Affected component:** Password recovery and deployment host validation. `app/Providers/AppServiceProvider.php:47–65`, `bootstrap/app.php:19–44`, installed `Illuminate/Routing/UrlGenerator.php:644–651`.
- **Evidence and impact:** The custom mail callback uses an absolute named route. The generator falls back to the request root unless an origin is forced. The application forces HTTPS in production, while the reviewed bootstrap has no host allowlist. An ingress that forwards arbitrary host values can cause recovery links to carry an untrusted origin. Exposure of a valid reset token could compromise the account, including a privileged account given M-02. Production reachability and mail behavior are unverified.
- **Verification and regression:** In isolated staging with fake mail and synthetic users, require ingress rejection of unknown host values and assert the captured recovery URL always uses the canonical HTTPS origin.
- **Fix:** Generate recovery links from a trusted configured origin and allowlist the canonical hosts at ingress and application level. Scope trusted proxies to the deployment topology. Apply the same origin discipline to signed order links.
- **Mapping:** OWASP A07 and A02. ASVS Authentication, Configuration. WSTG configuration and authentication testing.

## 8. Medium-severity vulnerabilities

### M-01 — Password changes leave existing sessions active

- **Severity / confidence / status:** Medium / High / **CONFIRMED by source**.
- **Affected component:** Account password change/reset and owner-issued staff password changes. `app/Http/Controllers/Storefront/Account/PasswordController.php:15–28`, `app/Http/Controllers/Storefront/Account/NewPasswordController.php:37–44`, `app/Http/Controllers/Admin/StaffController.php:70–79`, `bootstrap/app.php:30–44`.
- **Evidence and impact:** Credential changes save a new password. Recovery also rotates the remember token. The reviewed routes do not enforce password-hash-based session invalidation, and these actions do not revoke existing server-side sessions. The session guard reloads authenticated identities by user ID. A previously authenticated session can retain access after the user or owner changes the password, subject to session expiry and current role.
- **Verification and regression:** Use two synthetic sessions and verify the other session loses access after password change, reset, and an owner-issued staff password update. Include remembered sessions and check the intended behavior of the current device.
- **Fix:** Implement explicit per-user session and remember-token revocation consistently in credential recovery and password changes. Verify against the chosen session store.
- **Mapping:** OWASP A07. ASVS Session Management. WSTG session management testing.

### M-02 — Privileged accounts inherit storefront recovery policy

- **Severity / confidence / status:** Medium / High / **CONFIRMED by source**.
- **Affected component:** Shared user provider, storefront auth, and admin gates. `config/auth.php:39–100`, `app/Http/Requests/Storefront/Account/LoginRequest.php:57–82`, `app/Http/Controllers/Storefront/Account/PasswordResetLinkController.php:26–35`, `app/Http/Controllers/Storefront/Account/NewPasswordController.php:25–44`, `app/Providers/AppServiceProvider.php:51–56`.
- **Evidence and impact:** Storefront authentication and reset use the shared users provider without a customer-role restriction. Reset accepts the storefront password minimum, which is lower than the staff creation policy. Admin access checks the stored role on the same guard, and the app has no privileged MFA or recent-auth gate. Privileged access relies on the strength of a single password and the shared recovery channel. Existing tests verify that customers cannot use the admin login to become staff.
- **Verification and regression:** Assert privileged identities follow the chosen staff-specific login and recovery policy, retain staff password requirements, and must complete MFA before entering admin or making sensitive account changes. Test both login entry points.
- **Fix:** Centralize password and recovery policy by role, isolate privileged authentication or add explicit role-aware controls at every entry point, and require MFA or a verified identity-aware gateway for staff and owners.
- **Mapping:** OWASP A07. ASVS Authentication, Authorization. WSTG identity and authentication testing.

### M-03 — Admin CSV exports pass user-controlled text to spreadsheets

- **Severity / confidence / status:** Medium / High / **CONFIRMED by source**.
- **Affected component:** Order CSV and campaign/report CSV. `app/Http/Requests/Storefront/PlaceOrderRequest.php:57–75`, `app/Http/Controllers/Admin/OrderExportController.php:28–54`, `app/Http/Controllers/Admin/ReportExportController.php:23–34`, `app/Services/SalesReport.php:154–191`.
- **Evidence and impact:** Checkout stores shopper-supplied text and attribution. The order export passes those fields directly to `fputcsv`, and report export writes grouped text fields the same way. CSV quoting protects structure. Spreadsheet software can still interpret cell content as a formula. Impact depends on an authorized user opening the file and on client behavior. Subscriber export also deserves review for preserving text such as phone numbers.
- **Verification and regression:** Assert that synthetic text in spreadsheet-facing export columns is emitted as inert text. Verify parsed columns, leading whitespace, control characters, Arabic content, phone numbers, and numeric report fields. Spreadsheet behavior was not executed.
- **Fix:** Centralize spreadsheet-safe text serialization for all admin exports or emit typed text cells in a spreadsheet format. Keep advertising catalog feeds on their own machine-readable contract.
- **Mapping:** OWASP A05. ASVS Encoding and Sanitization. WSTG input validation testing.

### M-04 — Restricted catalog feeds are cacheable by shared intermediaries

- **Severity / confidence / status:** Medium / High for response policy / **CONFIRMED by source**. CDN exposure remains unverified.
- **Affected component:** Catalog feed access and cache policy. `app/Http/Controllers/Storefront/ProductFeedController.php:20–41`, `app/Http/Requests/Admin/UpdateSettingsRequest.php:68,150–162`, `resources/views/admin/settings/edit.blade.php:199–210`.
- **Evidence and impact:** Token validation precedes application caching, but even a token-protected response advertises `public, max-age=900`. A cache with a query-insensitive key could serve protected catalog data across requests. A cache that keys on the complete URL can still retain earlier authorized responses after rotation. The optional token travels in the URL and is stored as a readable setting. The feed contains product data for listed products rather than order or customer data.
- **Verification and regression:** Assert protected responses carry `private, no-store`. At the configured staging CDN, verify unauthenticated requests are denied after a successful fetch and rotation takes effect immediately. Check log redaction for query credentials.
- **Fix:** Keep internal content caching behind the token check. Bypass the CDN cache for restricted feeds, use a high-entropy rotatable token, protect it at rest, and prefer a header where platform support permits.
- **Mapping:** OWASP A01 and A02. ASVS Data Protection, Authorization. WSTG configuration testing.

### M-05 — Upload byte limits do not bound decoded image memory

- **Severity / confidence / status:** Medium / High for the missing bound / **CONFIRMED by source**. Runtime exhaustion remains unverified.
- **Affected component:** Staff media uploads. `app/Http/Requests/Admin/ProductImageRequest.php:28–35`, `app/Http/Requests/Admin/BannerRequest.php:35–38`, `app/Services/ImageStore.php:31–37,57–64`, `app/Http/Controllers/Admin/ProductImageController.php:22–39`.
- **Evidence and impact:** Upload rules cap compressed file bytes and set minimum dimensions for product photos. They do not cap maximum pixels. The server decodes with GD before downscaling, and the upload controller does this synchronously. A highly compressed image can make a staff request expensive, with availability impact depending on PHP and worker resource limits. Executable upload was not demonstrated.
- **Verification and regression:** In the isolated harness, test small representative files against a deliberately small pixel cap and verify rejection happens before image decoding. Verify full-request bounds separately in staging.
- **Fix:** Enforce width, height, pixel, and aggregate request limits before full decode across all image entry points. Bound decoder CPU/memory and ensure the public media origin serves files as static content.
- **Mapping:** OWASP A06 and A10. ASVS File Handling. WSTG upload testing.

### M-06 — Changing the recovery email requires only a live session

- **Severity / confidence / status:** Medium / High / **CONFIRMED by source**.
- **Affected component:** Profile email change on the shared guard. `routes/web.php:99–105`, `app/Http/Requests/Storefront/Account/ProfileRequest.php:39–49`, `app/Http/Controllers/Storefront/Account/ProfileController.php:25–33`.
- **Evidence and impact:** The profile handler writes the new email directly for the current identity. It requires neither recent authentication nor confirmation of the new address. The shared route is also available to privileged identities. Someone with access to an unattended or compromised session can change the recovery destination and retain control beyond the original session.
- **Verification and regression:** Assert recovery email remains unchanged until recent authentication and confirmation of the replacement address. Verify the original address is notified, privileged identities use the intended controls, and expired confirmations fail.
- **Fix:** Gate sensitive identity changes on recent authentication, verify the new email before making it the recovery destination, and notify the old address.
- **Mapping:** OWASP A07. ASVS Authentication. WSTG identity and authentication testing.

### M-07 — Checkout drafts leave personal information in persistent browser storage

- **Severity / confidence / status:** Medium / High / **CONFIRMED by source**.
- **Affected component:** Checkout draft and third-party script boundary. `resources/js/store/checkout-form.js:6–29,69–79,129–141`, `resources/js/app.js:30–35`, `resources/views/components/store/layout.blade.php:70–76`.
- **Evidence and impact:** The draft persists name, phone, email, address components, and notes in `localStorage`. The seven-day age check ignores stale drafts without deleting them. Successful confirmation clears the key, so abandoned drafts can remain indefinitely. Shared browser profiles and same-origin scripts, including enabled third-party scripts executing in the storefront, can access the storage.
- **Verification and regression:** Use synthetic drafts to verify expiry deletes the key, completion clears data, and the chosen scope prevents persistence across users on shared devices. Verify real browser behavior separately.
- **Fix:** Minimize the fields kept in the browser. Prefer short-lived session or server-side state with deletion on expiry and completion. Keep third-party scripts off sensitive views where possible.
- **Mapping:** OWASP A02 and A04. ASVS Data Protection, Web Frontend Security. WSTG client-side testing.

## 9. Low-severity vulnerabilities

### L-01 — Registration discloses whether a shared user email exists

- **Severity / confidence / status:** Low / High / **CONFIRMED by source**.
- **Affected component:** Public registration. `app/Http/Requests/Storefront/Account/RegisterRequest.php:43–49,58–67`, `routes/web.php:89–90`.
- **Evidence and impact:** The public form checks uniqueness against all users and shows a dedicated duplicate-account message. The route has an IP-based throttle. Account existence disclosure can support targeting and phishing.
- **Verification and regression:** Compare outward behavior for existing and new synthetic email addresses. Assert the intended generic response and rate limit.
- **Fix:** Use an email-driven enrollment flow with a generic public response and comparable observable behavior.
- **Mapping:** OWASP A07. ASVS Authentication. WSTG account enumeration testing.

### L-02 — Content Security Policy only constrains framing

- **Severity / confidence / status:** Low / High / **CONFIRMED by source and existing header expectations**.
- **Affected component:** Browser headers and script loading. `app/Http/Middleware/SecurityHeaders.php:20–27`, `resources/js/store/tracking.js:24–83`, `tests/Feature/SecurityHeadersTest.php:16–23`.
- **Evidence and impact:** The application sets CSP `frame-ancestors` and other browser safety headers. The policy leaves script execution and resource origins unrestricted. This weakens defense in depth if browser injection or a compromised script dependency arises. The review did not establish a browser XSS finding.
- **Verification and regression:** Check representative storefront and admin pages with a report-only policy before enforcement. Assert expected first-party scripts run, framing stays restricted, and unapproved resource loads are rejected under the enforced policy.
- **Fix:** Start with the admin policy, where ad scripts are unnecessary. Adopt CSP-compatible Alpine where feasible, limit origins and inline scripts, and verify the store policy with configured integrations.
- **Mapping:** OWASP A02. ASVS Web Frontend Security. WSTG client-side testing.

### L-03 — Conversion failures log raw third-party response bodies

- **Severity / confidence / status:** Low / High for logging behavior / **CONFIRMED by source**.
- **Affected component:** Conversion queue logging. `app/Services/ConversionEvents.php:61–90,105–157`, `app/Jobs/SendPurchaseToAdPlatforms.php:19–36`.
- **Evidence and impact:** Failed conversion responses are logged verbatim before an exception is thrown. Error content is controlled by the integration and can contain identifiers or echoed data. No real credential or personal data was found or captured in logs during the audit.
- **Verification and regression:** In an HTTP fake, assert warnings and failed-job telemetry retain a provider code or request ID while discarding arbitrary response text and synthetic sensitive markers.
- **Fix:** Log allowlisted error codes, sanitized request IDs, provider, and status. Apply redaction and restricted retention to queue and exception telemetry.
- **Mapping:** OWASP A09 and A10. ASVS Security Logging and Error Handling. WSTG error handling testing.

### L-04 — Checkout idempotency key can mint an order link without owner binding

- **Severity / confidence / status:** Low / High for source behavior / **CONFIRMED by source**.
- **Affected component:** Checkout duplicate handling. `app/Http/Controllers/Storefront/CheckoutController.php:48–69,78–105`, `app/Http/Requests/Storefront/PlaceOrderRequest.php:73–76`, `app/Models/Order.php:50,89–98`, `resources/views/storefront/orders/thank-you.blade.php:82–106`.
- **Evidence and impact:** Checkout creates a random UUID and persists it as a unique order key. Both duplicate paths look up that key globally and issue a fresh signed confirmation URL. Neither lookup binds to the original bag, session, or account. Disclosure of this high-entropy key would let its holder renew access to delivery and contact details. No key disclosure or practical guessing was observed.
- **Verification and regression:** Assert retries in the initiating synthetic session remain idempotent and separate sessions cannot receive the existing order link. Include the unique-constraint recovery path.
- **Fix:** Bind idempotency records to the authenticated identity or original guest session, expire replay state, and keep confirmation links short-lived and out of caches and logs.
- **Mapping:** OWASP A01 and A06. ASVS Authorization, Validation and Business Logic. WSTG authorization and business logic testing.

## 10. Security controls that passed

Scope for the test-backed rows is the existing suite and its representative cases.

| Control | Evidence |
|---|---|
| Role and account boundaries | Admin authentication rejects customers. Owner-only settings and staff access are tested. Account orders and address changes check ownership. Cart lines check the caller’s bag. `AdminGatesTest.php`, `Admin/AuthenticatedSessionControllerTest.php`, `Admin/StaffControllerTest.php`, `Storefront/Account/AccountPagesTest.php`, `Storefront/CartItemControllerTest.php`. |
| Authentication basics | Password hashing uses Laravel’s cast. Sign-in regenerates the session and sign-out invalidates it. Existing tests cover password checks, local lockout, sign-out, and basic password reset. Recovery requests return generic messaging. `app/Models/User.php:40–46`, `Storefront/Account/AuthenticationTest.php`, `Admin/AuthenticatedSessionControllerTest.php`. |
| Guest order disclosure controls | Representative tests reject invalid signatures, expired links and mismatched tracking details. Tracking is throttled. `Storefront/OrderConfirmationControllerTest.php`, `Storefront/TrackOrderControllerTest.php`. |
| Order integrity | Server-side repricing, quantity validation, transactional stock changes, order snapshots, coupon validation, duplicate order keys, allowed state transitions, cash collection rules, and restock behavior. The last-piece test is sequential. `Actions/PlaceOrderTest.php`, `Actions/UpdateOrderStatusTest.php`, `Actions/UpdateOrderItemsTest.php`, `Storefront/CheckoutControllerTest.php`, `Models/CouponTest.php`. |
| File handling | Source enforces staff authorization, allowlisted raster content, byte limits, generated paths, and WebP re-encoding. Existing tests verify representative validation and generated files. `Admin/ProductImageControllerTest.php`, `Services/ImageStoreTest.php`. |
| Headers and conditional tracking | Tests cover representative security headers, production HTTPS HSTS behavior, feed-token origin checks, and configured consent behavior and fake outbound integrations. `SecurityHeadersTest.php`, `Storefront/ProductFeedControllerTest.php`, `Storefront/TrackingTest.php`, `Services/ConversionEventsTest.php`. |
| Injection and field allowlists, source inspection | Reviewed report expressions use fixed SQL or bound values. Search uses bound values. Registration and profile updates select explicit fields and role updates are separately gated. Blade escapes ordinary HTML and JSON-LD hex-encodes HTML delimiters. This is source evidence for the reviewed sinks. |

Test filenames above are relative to `tests/Feature/`.

## 11. Unverified security controls

The following **12 groups** are NOT TESTED. The count denotes groups, distinct from automated test cases and static findings.

1. **NT-01 — Production ingress.** Host and forwarded-header rejection, client IP handling, canonical redirects and origin reachability.
2. **NT-02 — TLS and deployment headers.** Certificates, TLS policy, HSTS and error-page headers at ingress/CDN.
3. **NT-03 — Effective cookies and CSRF.** Real browser cookie flags, subdomain scope, token and origin enforcement with actual middleware behavior, and rejection of cross-origin writes. Laravel web registration is source evidence.
4. **NT-04 — Real-browser client security.** DOM sinks, script policy, consent before and after navigation, tracking interactions with sensitive pages, and browser draft storage.
5. **NT-05 — Session and recovery edge cases.** Independent device revocation, remember-me lifecycle, expired/reused reset links in end-to-end tests, enumeration timing, and distributed credential-stuffing resistance. Laravel’s broker implements expiry and deletes tokens after successful reset. `config/auth.php:97–100`, installed `PasswordBroker.php:122–145`.
6. **NT-06 — Complete HTTP authorization matrix.** Exhaustive route-by-role and cross-resource HTTP checks. The suite covers representative routes and actions.
7. **NT-07 — Production SQL and storage.** MySQL privileges, transport encryption, native locking, concurrent checkout/coupon/inventory state, database at-rest encryption and retention.
8. **NT-08 — Filesystem and hosting boundaries.** Document root, environment and backup file isolation, media no-execution policy, request limits, image resource exhaustion, bucket policies, and private file access.
9. **NT-09 — Targeted dynamic input and client tests.** Broad malformed-input cases, live CORS/preflights, spreadsheet interpretation, path/file handling, and browser injection probes. Static source review and existing tests provide partial evidence.
10. **NT-10 — CDN and third-party behavior.** Restricted feed caching and revocation at the edge, real mail and conversion delivery, provider error redaction, and platform access controls.
11. **NT-11 — Operational assurance.** Secret manager controls and rotation, privileged account provisioning, no demo users, backup protection/restore, worker permissions, monitoring, security event alerting, and incident procedures.
12. **NT-12 — Build and CI/CD.** Clean reproducible production asset build, runner and artifact access, provenance, release approval, and deployment rollback.

## 12. Dependency and supply chain findings

- **Composer.** `composer audit --locked --format=json` returned zero advisories and zero abandoned packages. Manifest validation passed.
- **npm.** `npm.cmd audit --json` returned zero vulnerabilities in the registry report. Both lockfiles are tracked. Advisory databases cover published disclosures known to the queried registries.
- **Build and release.** Composer scripts include setup, package discovery, asset publication, and Boost updates. Vite fetches configured font assets through the Laravel Vite plugin. Review these build-time trust relationships and use locked production installs with restricted CI permissions. CI and runtime artifact provenance are NT-12.
- **Advisory remediation.** No package, advisory ID, or upgrade was identified by either audit on this run. No updates were applied.

## 13. Configuration and deployment findings

- **Secrets.** The redacted signature scan checked 14 reachable commits for private-key markers and selected AWS, GitHub, Slack, Google, Laravel app-key, and payment-key patterns. It returned no matching paths. Git ignores the local environment file, local app log, and public storage link. The history filename sweep found only `.env.example` among the checked sensitive-file names/extensions. This bounded search leaves provider-specific formats, untracked workspace artifacts, historical objects outside reachable refs, and external secret stores unverified.
- **Local configuration.** The redacted local flag check found development settings for app environment/debug, session encryption and mail, with the secure-cookie setting unset. Treat the local environment as development. `config/app.php:29–42` defaults debug off when unset, and `docs/LAUNCH_CHECKLIST.md:20–45` calls for production overrides and real mail. The log mailer is suitable for local development and would put recovery and order email content in logs if enabled on a live store.
- **Demo accounts.** The development seeder creates owner and staff fixtures using a predictable factory password. The seeder is documented for local development and the production owner CLI uses a prompted password. Establish that no demo principals exist in staging or production. `database/seeders/DatabaseSeeder.php:13–27`, `database/factories/UserFactory.php:25–34`, `app/Console/Commands/CreateOwnerCommand.php:40–55`.
- **Database and personal data.** Order/customer records hold contact and address data, and order attribution can include network and campaign metadata. Verify least-privilege runtime grants, private database access, encryption and retention, protected backups, and access-controlled exports. Ad conversion tokens use application encryption. Protect the application key and define a rotation procedure. `database/migrations/2026_11_10_100001_create_orders_table.php`, `database/migrations/2026_11_11_110000_add_social_commerce.php`, `app/Services/Settings.php:43–64`.
- **Monitoring.** Laravel login requests emit lockout events and order status/stock changes have audit records. The reviewed app has limited dedicated audit coverage for privileged credential/role changes, settings changes, and personal data exports. Log structured actions with actor, target, and outcome, and verify centralized alerts and retention without credential or personal-data payloads.
- **Web server.** Deploy with `public/` as document root. Apache rules set `-Indexes` when the applicable modules and overrides are active. Laravel forces HTTPS URL generation in production and emits HSTS only for requests it recognizes as secure. Verify the actual edge redirects, trusted proxies, static media permissions and error behavior. `public/.htaccess:1–25`, `app/Providers/AppServiceProvider.php:47–49`, `app/Http/Middleware/SecurityHeaders.php:40–54`.

## 14. Recommended fixes

1. **Enforce canonical hosts and verify the HTTPS edge** before enabling production recovery. Pin the recovery-link origin and test H-01.
2. **Require strong privileged authentication.** Set a consistent staff/owner password and recovery policy and require MFA. Add layered rate limits and reduce public registration enumeration. M-02 and L-01.
3. **Revoke existing sessions on credential changes.** Include remember tokens and owner-issued staff password resets. M-01.
4. **Protect recovery email changes.** Add recent authentication, verification, and notification. M-06.
5. **Neutralize spreadsheet interpretation in admin exports.** Apply the same text handling across orders, reports, and subscribers. M-03.
6. **Secure restricted feed delivery.** Prevent shared caching, protect and rotate the token, and redact query credentials. M-04.
7. **Bound image decoding and verify static-only serving.** Set pixel and request budgets and confirm media execution is disabled. M-05.
8. **Minimize PII and bearer state.** Replace persistent checkout draft storage, bind idempotency to its owner, and check sensitive response cache and referrer policies. M-07 and L-04.
9. **Improve browser and logging controls.** Introduce CSP in stages, redact integration errors, and audit privileged actions. L-02, L-03 and §13.
10. **Close staging and operational gaps.** Verify MySQL concurrency and privileges, rotate/provision credentials correctly, remove demo users, test restore and alerting, and validate the locked release pipeline. NT-01 through NT-12.

## 15. Regression tests to add

| Test | Expected result |
|---|---|
| Recovery canonical origin and host policy | Unexpected hosts are rejected. Fake reset mail always contains the approved HTTPS origin. H-01. |
| Multi-device session and remember token lifecycle | Reset/change invalidates earlier sessions and remembered login state under the selected policy. M-01. |
| Privileged auth across both entry points | Shared storefront entry points cannot bypass required MFA, privileged password rules or staff recovery policy. M-02. |
| CSV typed or escaped text | Every externally influenced text column is inert in the intended spreadsheet workflow, with Arabic and numeric values preserved. M-03. |
| Restricted feed and rotation | Protected responses are never shared-cacheable. An old token is rejected after rotation. M-04. |
| Image limits | Validation rejects over-budget fixtures before GD decode. M-05. |
| Recovery email changes | Pending addresses cannot receive reset links. Old address receives notification and stale confirmation fails. M-06. |
| Checkout draft lifecycle | Sensitive data is minimized, stale state is actually removed, and completion clears the draft. M-07. |
| Registration and authentication abuse controls | Generic public responses and layered throttling behave consistently for synthetic users. L-01 and NT-05. |
| Browser and integration logging | Staging CSP behavior is validated, and fake provider failures log only approved fields. L-02 and L-03. |
| Checkout retry ownership and real SQL concurrency | Same-session retries reuse an order safely. Different sessions receive no order details. Parallel staging transactions preserve stock, coupon and payment state. L-04 and NT-07. |

## 16. Pre-deployment security checklist

- [ ] Close H-01 with canonical-host evidence. Verify HTTPS redirect, TLS, HSTS, secure cookies and CSRF through the deployed ingress.
- [ ] Resolve M-01, M-02, M-03 and M-06 before handling real orders or provisioning production staff. Document mitigation or acceptance of other findings.
- [ ] Confirm debug off, restricted app and DB credentials, protected application key, real mail transport, and zero demo accounts.
- [ ] Set web root to `public/`. Verify private files are inaccessible, media is static-only, and uploads and requests have bounded resources.
- [ ] Verify admin and customer boundaries, order and address ownership, confirmation links, and sensitive response caching on staging.
- [ ] Verify MySQL concurrency, DB privilege separation, backup restoration, and queue and scheduler operation.
- [ ] Verify ad consent, restricted feed caching, telemetry redaction and logging/alerting before enabling integrations.
- [ ] Re-run automated tests and registry advisories for the exact release. Capture CI/build and staging evidence.

## 17. Deployment readiness assessment

**INSUFFICIENT EVIDENCE.** Hold public launch. The source review and existing suite establish a local baseline. Canonical-host enforcement, deployed session and CSRF behavior, media isolation, and production data controls remain essential verification gates. Address the privileged authentication, session, recovery email, and spreadsheet findings before sign-off.

| Metric | Total |
|---|---:|
| Automated tests executed | 809 |
| Automated tests passed | 809 |
| Automated tests failed | 0 |
| Not-tested verification groups | 12 |
| Confirmed Critical | 0 |
| Confirmed High | 0 |
| Confirmed Medium | 7 |
| Confirmed Low | 4 |
| Suspected High | 1 |

The ten remediation priorities are in §14.

## Appendix — Registered application routes

This inventory comes from Artisan's application route listing. The table omits the universal `web` marker for readability. It shows registered route middleware, not global middleware or controller-level checks.

| Method | Path | Handler | Additional route middleware |
|---|---|---|---|
| `GET / HEAD` | `/` | `Storefront\LocaleRedirectController` | — |
| `GET / HEAD` | `/admin` | `Admin\DashboardController` | `auth`, `can:access-admin` |
| `GET / HEAD` | `/admin/banners` | `Admin\BannerController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/banners` | `Admin\BannerController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/banners/create` | `Admin\BannerController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT / PATCH` | `/admin/banners/{banner}` | `Admin\BannerController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/banners/{banner}` | `Admin\BannerController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/banners/{banner}/edit` | `Admin\BannerController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/categories` | `Admin\CategoryController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/categories` | `Admin\CategoryController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/categories/create` | `Admin\CategoryController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT / PATCH` | `/admin/categories/{category}` | `Admin\CategoryController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/categories/{category}` | `Admin\CategoryController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/categories/{category}/edit` | `Admin\CategoryController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/collections` | `Admin\CollectionController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/collections` | `Admin\CollectionController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/collections/create` | `Admin\CollectionController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT / PATCH` | `/admin/collections/{collection}` | `Admin\CollectionController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/collections/{collection}` | `Admin\CollectionController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/collections/{collection}/edit` | `Admin\CollectionController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/colors` | `Admin\ColorController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/colors` | `Admin\ColorController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/colors/create` | `Admin\ColorController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT / PATCH` | `/admin/colors/{color}` | `Admin\ColorController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/colors/{color}` | `Admin\ColorController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/colors/{color}/edit` | `Admin\ColorController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/coupons` | `Admin\CouponController@index` | `auth`, `can:access-admin`, `can:manage-store` |
| `POST` | `/admin/coupons` | `Admin\CouponController@store` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/coupons/create` | `Admin\CouponController@create` | `auth`, `can:access-admin`, `can:manage-store` |
| `PUT / PATCH` | `/admin/coupons/{coupon}` | `Admin\CouponController@update` | `auth`, `can:access-admin`, `can:manage-store` |
| `DELETE` | `/admin/coupons/{coupon}` | `Admin\CouponController@destroy` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/coupons/{coupon}/edit` | `Admin\CouponController@edit` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/customers` | `Admin\CustomerController@index` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/customers/{customer}` | `Admin\CustomerController@show` | `auth`, `can:access-admin`, `can:manage-orders` |
| `PUT` | `/admin/customers/{customer}` | `Admin\CustomerController@update` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/delivery-zones` | `Admin\DeliveryZoneController@index` | `auth`, `can:access-admin`, `can:manage-store` |
| `POST` | `/admin/delivery-zones` | `Admin\DeliveryZoneController@store` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/delivery-zones/create` | `Admin\DeliveryZoneController@create` | `auth`, `can:access-admin`, `can:manage-store` |
| `PUT / PATCH` | `/admin/delivery-zones/{delivery_zone}` | `Admin\DeliveryZoneController@update` | `auth`, `can:access-admin`, `can:manage-store` |
| `DELETE` | `/admin/delivery-zones/{delivery_zone}` | `Admin\DeliveryZoneController@destroy` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/delivery-zones/{delivery_zone}/edit` | `Admin\DeliveryZoneController@edit` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/faqs` | `Admin\FaqController@index` | `auth`, `can:access-admin`, `can:manage-store` |
| `POST` | `/admin/faqs` | `Admin\FaqController@store` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/faqs/create` | `Admin\FaqController@create` | `auth`, `can:access-admin`, `can:manage-store` |
| `PUT / PATCH` | `/admin/faqs/{faq}` | `Admin\FaqController@update` | `auth`, `can:access-admin`, `can:manage-store` |
| `DELETE` | `/admin/faqs/{faq}` | `Admin\FaqController@destroy` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/faqs/{faq}/edit` | `Admin\FaqController@edit` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/inventory` | `Admin\InventoryController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/inventory/{variant}` | `Admin\StockAdjustmentController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/inventory/{variant}/adjustments` | `Admin\StockAdjustmentController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/login` | `Admin\AuthenticatedSessionController@create` | `guest` |
| `POST` | `/admin/login` | `Admin\AuthenticatedSessionController@store` | `guest` |
| `POST` | `/admin/logout` | `Admin\AuthenticatedSessionController@destroy` | `auth`, `can:access-admin` |
| `GET / HEAD` | `/admin/messages` | `Admin\ContactMessageController@index` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/messages/{message}` | `Admin\ContactMessageController@show` | `auth`, `can:access-admin`, `can:manage-orders` |
| `DELETE` | `/admin/messages/{message}` | `Admin\ContactMessageController@destroy` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/orders` | `Admin\OrderController@index` | `auth`, `can:access-admin`, `can:manage-orders` |
| `POST` | `/admin/orders` | `Admin\OrderController@store` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/orders/create` | `Admin\OrderController@create` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/orders/export` | `Admin\OrderExportController` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/orders/{order:number}` | `Admin\OrderController@show` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/orders/{order:number}/invoice` | `Admin\OrderDocumentController@invoice` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/orders/{order:number}/items` | `Admin\OrderItemController@edit` | `auth`, `can:access-admin`, `can:manage-orders` |
| `PUT` | `/admin/orders/{order:number}/items` | `Admin\OrderItemController@update` | `auth`, `can:access-admin`, `can:manage-orders` |
| `PUT` | `/admin/orders/{order:number}/note` | `Admin\OrderNoteController` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/orders/{order:number}/packing-slip` | `Admin\OrderDocumentController@packingSlip` | `auth`, `can:access-admin`, `can:manage-orders` |
| `PUT` | `/admin/orders/{order:number}/payment` | `Admin\OrderPaymentController` | `auth`, `can:access-admin`, `can:manage-orders` |
| `PUT` | `/admin/orders/{order:number}/status` | `Admin\OrderStatusController` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/pages` | `Admin\PageController@index` | `auth`, `can:access-admin`, `can:manage-store` |
| `POST` | `/admin/pages` | `Admin\PageController@store` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/pages/create` | `Admin\PageController@create` | `auth`, `can:access-admin`, `can:manage-store` |
| `PUT / PATCH` | `/admin/pages/{page}` | `Admin\PageController@update` | `auth`, `can:access-admin`, `can:manage-store` |
| `DELETE` | `/admin/pages/{page}` | `Admin\PageController@destroy` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/pages/{page}/edit` | `Admin\PageController@edit` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/products` | `Admin\ProductController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/products` | `Admin\ProductController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/products/create` | `Admin\ProductController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT / PATCH` | `/admin/products/{product}` | `Admin\ProductController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/products/{product}` | `Admin\ProductController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/products/{product}/colors` | `Admin\ProductColorController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/products/{product}/colors` | `Admin\ProductColorController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT` | `/admin/products/{product}/colors/{color}` | `Admin\ProductColorController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/products/{product}/colors/{color}` | `Admin\ProductColorController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT` | `/admin/products/{product}/colors/{color}/default` | `Admin\ProductDefaultColorController` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/products/{product}/colors/{color}/images` | `Admin\ProductImageController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/products/{product}/colors/{color}/images/{image}` | `Admin\ProductImageController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT` | `/admin/products/{product}/colors/{color}/images/{image}/position` | `Admin\ProductImagePositionController` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT` | `/admin/products/{product}/colors/{color}/position` | `Admin\ProductColorPositionController` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/products/{product}/edit` | `Admin\ProductController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/products/{product}/stock` | `Admin\ProductStockController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT` | `/admin/products/{product}/stock` | `Admin\ProductStockController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT` | `/admin/products/{product}/variants` | `Admin\ProductVariantController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/reports` | `Admin\ReportController` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/reports/{report}.csv` | `Admin\ReportExportController` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/settings` | `Admin\SettingsController@edit` | `auth`, `can:access-admin`, `can:manage-store` |
| `PUT` | `/admin/settings` | `Admin\SettingsController@update` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/short-links` | `Admin\ShortLinkController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/short-links` | `Admin\ShortLinkController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/short-links/create` | `Admin\ShortLinkController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT / PATCH` | `/admin/short-links/{short_link}` | `Admin\ShortLinkController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/short-links/{short_link}` | `Admin\ShortLinkController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/short-links/{short_link}/edit` | `Admin\ShortLinkController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/size-guides` | `Admin\SizeGuideController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/size-guides` | `Admin\SizeGuideController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/size-guides/create` | `Admin\SizeGuideController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT / PATCH` | `/admin/size-guides/{size_guide}` | `Admin\SizeGuideController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/size-guides/{size_guide}` | `Admin\SizeGuideController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/size-guides/{size_guide}/edit` | `Admin\SizeGuideController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/sizes` | `Admin\SizeController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `POST` | `/admin/sizes` | `Admin\SizeController@store` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/sizes/create` | `Admin\SizeController@create` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `PUT / PATCH` | `/admin/sizes/{size}` | `Admin\SizeController@update` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `DELETE` | `/admin/sizes/{size}` | `Admin\SizeController@destroy` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/sizes/{size}/edit` | `Admin\SizeController@edit` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/staff` | `Admin\StaffController@index` | `auth`, `can:access-admin`, `can:manage-store` |
| `POST` | `/admin/staff` | `Admin\StaffController@store` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/staff/create` | `Admin\StaffController@create` | `auth`, `can:access-admin`, `can:manage-store` |
| `PUT / PATCH` | `/admin/staff/{staff}` | `Admin\StaffController@update` | `auth`, `can:access-admin`, `can:manage-store` |
| `DELETE` | `/admin/staff/{staff}` | `Admin\StaffController@destroy` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/staff/{staff}/edit` | `Admin\StaffController@edit` | `auth`, `can:access-admin`, `can:manage-store` |
| `GET / HEAD` | `/admin/stock-history` | `Admin\StockMovementController@index` | `auth`, `can:access-admin`, `can:manage-catalog` |
| `GET / HEAD` | `/admin/subscribers` | `Admin\SubscriberController@index` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/subscribers/export` | `Admin\SubscriberController@export` | `auth`, `can:access-admin`, `can:manage-orders` |
| `DELETE` | `/admin/subscribers/{subscriber}` | `Admin\SubscriberController@destroy` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/admin/variants/search` | `Admin\VariantSearchController` | `auth`, `can:access-admin`, `can:manage-orders` |
| `GET / HEAD` | `/feeds/{platform}.csv` | `Storefront\ProductFeedController` | `throttle:30,1` |
| `GET / HEAD` | `/p/{code}` | `Storefront\ShortLinkController` | `throttle:120,1` |
| `GET / HEAD` | `/robots.txt` | `Storefront\SeoController@robots` | — |
| `GET / HEAD` | `/sitemap.xml` | `Storefront\SeoController@sitemap` | `throttle:30,1` |
| `GET / HEAD` | `/{locale}` | `Storefront\HomeController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `GET / HEAD` | `/{locale}/account` | `Storefront\Account\AccountOrderController@index` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `GET / HEAD` | `/{locale}/account/addresses` | `Storefront\Account\AddressController@index` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `POST` | `/{locale}/account/addresses` | `Storefront\Account\AddressController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `PUT` | `/{locale}/account/addresses/{address}` | `Storefront\Account\AddressController@update` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `DELETE` | `/{locale}/account/addresses/{address}` | `Storefront\Account\AddressController@destroy` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `GET / HEAD` | `/{locale}/account/forgot-password` | `Storefront\Account\PasswordResetLinkController@create` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `guest` |
| `POST` | `/{locale}/account/forgot-password` | `Storefront\Account\PasswordResetLinkController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `guest`, `throttle:5,10` |
| `GET / HEAD` | `/{locale}/account/login` | `Storefront\Account\AuthenticatedSessionController@create` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `guest` |
| `POST` | `/{locale}/account/login` | `Storefront\Account\AuthenticatedSessionController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `guest` |
| `POST` | `/{locale}/account/logout` | `Storefront\Account\AuthenticatedSessionController@destroy` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `GET / HEAD` | `/{locale}/account/orders/{order:number}` | `Storefront\Account\AccountOrderController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `PUT` | `/{locale}/account/password` | `Storefront\Account\PasswordController@update` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `GET / HEAD` | `/{locale}/account/profile` | `Storefront\Account\ProfileController@edit` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `PUT` | `/{locale}/account/profile` | `Storefront\Account\ProfileController@update` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth` |
| `GET / HEAD` | `/{locale}/account/register` | `Storefront\Account\RegisteredUserController@create` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `guest` |
| `POST` | `/{locale}/account/register` | `Storefront\Account\RegisteredUserController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `guest`, `throttle:5,10` |
| `POST` | `/{locale}/account/reset-password` | `Storefront\Account\NewPasswordController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `guest`, `throttle:10,10` |
| `GET / HEAD` | `/{locale}/account/reset-password/{token}` | `Storefront\Account\NewPasswordController@create` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `guest` |
| `PUT` | `/{locale}/account/wishlist` | `Storefront\Account\WishlistSyncController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `auth`, `throttle:60,1` |
| `GET / HEAD` | `/{locale}/bag` | `Storefront\CartController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `POST` | `/{locale}/bag/coupon` | `Storefront\CartCouponController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:60,1` |
| `DELETE` | `/{locale}/bag/coupon` | `Storefront\CartCouponController@destroy` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:60,1` |
| `PUT` | `/{locale}/bag/delivery-area` | `Storefront\CartDeliveryZoneController@update` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:60,1` |
| `GET / HEAD` | `/{locale}/bag/drawer` | `Storefront\CartController@drawer` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `POST` | `/{locale}/bag/items` | `Storefront\CartItemController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:60,1` |
| `PATCH` | `/{locale}/bag/items/{item}` | `Storefront\CartItemController@update` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:60,1` |
| `DELETE` | `/{locale}/bag/items/{item}` | `Storefront\CartItemController@destroy` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:60,1` |
| `GET / HEAD` | `/{locale}/category/{category:slug}` | `Storefront\CategoryController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `GET / HEAD` | `/{locale}/checkout` | `Storefront\CheckoutController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `POST` | `/{locale}/checkout` | `Storefront\CheckoutController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:place-order` |
| `POST` | `/{locale}/checkout/buy-now` | `Storefront\BuyNowController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:60,1` |
| `GET / HEAD` | `/{locale}/collection/{collection:slug}` | `Storefront\CollectionController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `GET / HEAD` | `/{locale}/contact` | `Storefront\ContactController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `POST` | `/{locale}/contact` | `Storefront\ContactController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:5,10` |
| `GET / HEAD` | `/{locale}/faq` | `Storefront\PageController@faq` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `GET / HEAD` | `/{locale}/orders/{order:number}/thank-you` | `Storefront\OrderConfirmationController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `signed` |
| `GET / HEAD` | `/{locale}/pages/{page:slug}` | `Storefront\PageController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `GET / HEAD` | `/{locale}/product-cards` | `Storefront\ProductCardController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:120,1` |
| `GET / HEAD` | `/{locale}/products/{product:slug}` | `Storefront\ProductController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `GET / HEAD` | `/{locale}/search` | `Storefront\SearchController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `GET / HEAD` | `/{locale}/search/suggestions` | `Storefront\SearchSuggestionController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:120,1` |
| `GET / HEAD` | `/{locale}/shop` | `Storefront\ShopController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `GET / HEAD` | `/{locale}/styleguide` | `Illuminate\Routing\ViewController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `POST` | `/{locale}/subscribe` | `Storefront\SubscriberController@store` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:10,1` |
| `GET / HEAD` | `/{locale}/track` | `Storefront\TrackOrderController@show` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |
| `POST` | `/{locale}/track` | `Storefront\TrackOrderController@lookup` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution`, `throttle:10,1` |
| `GET / HEAD` | `/{locale}/wishlist` | `Storefront\WishlistController` | `SetLocale`, `ApplyCouponFromLink`, `CaptureAttribution` |

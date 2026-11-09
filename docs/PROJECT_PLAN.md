# Doha AL WAFA Online Store: Project Plan

## Context

Doha AL WAFA is a clothing shop in Qatar that sells Jelabiyas. The client wants a mobile-first online store that turns TikTok, Instagram, Facebook and Snapchat traffic into Cash on Delivery orders. Their products come in multiple colors, so color drives images, stock, cart, orders and admin. The reference store (lrflt.com) has single-color products. The project starts from a fresh Laravel app. This document covers the audit, the decisions and the step-by-step build plan.

**Status:** plan approved on 2026-11-09. Work proceeds one phase at a time (§12), with a review stop after each phase. Open questions in §13 use their recommended defaults until answered.

### How the audit was done (limits)

- **Chrome extension unavailable.** It was disconnected in this session, so the desktop and mobile visual review did not happen. The audit used read-only page fetches instead: page content plus Shopify's public product JSON. Visual design, hover and scroll behaviour, the cart drawer and mobile layout are marked **Not verified**. A visual pass can happen once the extension is reconnected (see Q40).
- **Checkout not reachable.** Every product was "Sold Out", so nothing could be added to the cart, and adding to cart is a write action anyway. The Shopify checkout steps are therefore **Not verified**.
- **Accounts not reachable.** The reference uses Shopify customer accounts on `account.lrflt.com`, and reaching them requires login or registration. They are **Not verified**.
- **Automated access clause.** The reference's Terms (Section 14) ask automated agents to identify themselves in the user-agent string. The fetch tool cannot do that, so fetching stopped after about 20 GET requests. No forms were submitted, no account was created and no order was placed.
- **Laravel Boost MCP unavailable.** It failed to connect, so the codebase was inspected with shell commands. MySQL (Laragon) was not running at the time, so start it before Phase 1.

### Existing app (what we build on)

- Laravel 13.40, PHP 8.4.18, Pest 5.3 (SQLite in-memory for tests, see `phpunit.xml`), Tailwind 4.3 through `@tailwindcss/vite`, Vite 8, laravel-vite-plugin 3.2. Fonts load through `bunny()` in `vite.config.js`.
- MySQL database `doha_online_shop`. Session, cache and queue all use the database driver, and their tables already exist.
- Only the skeleton exists: `routes/web.php` (welcome), `app/Models/User.php` (Laravel 13 attribute style `#[Fillable]` / `#[Hidden]`), and the users, cache and jobs migrations.
- There is no `.ai/rules`. Project skills are in `.claude/skills/`: laravel-best-practices, testing-best-practices and tailwindcss-development.
- Reuse plan:
  - Extend `User` with a role.
  - Keep the database drivers.
  - Put Tailwind v4 `@theme` tokens in `resources/css/app.css`.
  - Swap the Bunny fonts in `vite.config.js`.
  - Turn on `RefreshDatabase` in `tests/Pest.php`.

---

## 1. Reference website audit

Verification key: **[P]** = seen in the fetched page, **[J]** = public product JSON, **NV** = not verified.

### 1.1 Global and home page

| # | Feature | Where | How it works | Ver. |
|---|---|---|---|---|
| A1 | Platform | Site-wide | Shopify theme with standard routes: `/collections`, `/products`, `/pages`, `/policies`, `/cart`, `/search` | P |
| A2 | Header | All pages | Logo, search icon (opens a panel), account (Log in / Register), wishlist link, cart with count | P |
| A3 | Social strip | Top of header | Instagram, TikTok and WhatsApp icons | P |
| A4 | Main nav | Header | Flat list of seasonal collections (Best Selling, FALL26, EVERWEAR, FW2024, EID2024, …) with no dropdowns | P |
| A5 | Mobile menu | Header | Separate mobile menu with the same links plus an Account section | P (behaviour NV) |
| A6 | Language | Header | English / العربية switch. Arabic lives under an `/ar/...` prefix and English has no prefix | P (RTL `dir` NV) |
| A7 | Currency | Site-wide | QAR only (`QAR 850,00`), no switcher | P |
| A8 | Hero | Home | Headline plus a "Discover More" button that links to `#` (broken) | P |
| A9 | Shop by collection | Home | Collection tiles with item counts | P |
| A10 | Best-selling grid | Home | Product grid, 9 items | P |
| A11 | Promo block | Home | "Discover more" banner with 2 featured products | P |
| A12 | Newsletter | Home | "Join our community" with a terms checkbox | P (not submitted) |
| A13 | Age gate | Popup | "Are you 18 or older?" Yes/No modal | P |
| A14 | Cookie banner | Popup | Ignore / Decline / Optional / Learn more | P |
| A15 | Footer | All pages | Search, About, Contact, Shop, My orders / profile / settings, Terms, WhatsApp, socials, "Payment options" icons, © line | P (payment icons NV) |
| A16 | Tracking scripts | Site-wide | Meta, TikTok, Snap, GA: not visible in the fetched content | NV |

### 1.2 Listing and search

| # | Feature | Where | How it works | Ver. |
|---|---|---|---|---|
| B1 | Collection pages | `/collections/{handle}` | Products in a collection, with SEO-style long handles | P |
| B2 | Filters | Collection and search | Collection, price from/to (QAR), size (with counts), color (Black, Navy, Olive, probably from tags or metafields) | P |
| B3 | Sort | Collection | 9 options: featured, relevance, best selling, A–Z, Z–A, price ↑ and ↓, date old→new and new→old | P |
| B4 | Grid switcher | Collection | List, 2, 3, 4 or 5 columns | P |
| B5 | Load more | Collection | "Load More" button | P |
| B6 | Product card | Grids | Second image in markup (hover NV), empty "-%" sale badge (a bug), "Sold Out" badge, "Select options" link. No quick add | P |
| B7 | Search page | `/search?q=` | Result count, price/size/color filters, 3 sorts, numbered pages | P |
| B8 | Search panel | Header | "Popular searches" links. Live predictive results NV | P / NV |

### 1.3 Product page

| # | Feature | How it works | Ver. |
|---|---|---|---|
| C1 | Gallery | 3 images with "Zoom in". Images are not linked to variants (`variant_ids: []`) | P, J |
| C2 | Breadcrumbs | Home / Collection / Product | P |
| C3 | Price | Regular and sale price slots, compare-at empty | P, J |
| C4 | Size option | One Shopify option, Size S/M/L/XL. Each variant has a SKU (`AYLA-S`) and Shopify inventory tracking | J |
| C5 | Length selector | Values 49–62 in the UI but not a Shopify option, so it probably never reaches the cart or order (a data gap) | P, J |
| C6 | Color | None. All checked products are single-color | J |
| C7 | Stock state | "Variant sold out or unavailable" on size buttons. No quantities shown | P |
| C8 | Quantity, Add to cart | Present. "Buy it now" not seen | P |
| C9 | Terms checkbox | "I agree with Terms" next to Add to cart | P |
| C10 | Size guide | Link to a table in inches (Shoulder, Bust) | P |
| C11 | Delivery estimate | "Estimated delivery: Nov 13 – Nov 17" | P |
| C12 | Info sections | Description, Shipping & Returns, FAQ (4 questions). Tabs or accordion NV | P |
| C13 | Share | Copy link, Facebook, Pinterest, X, Telegram, WhatsApp, email | P |
| C14 | Wishlist | "Add to wishlist" plus a `/pages/wishlist` page. Storage method NV | P |
| C15 | Compare | "Compare" link | P |
| C16 | Ask a question | Inquiry form (not submitted) | P |
| C17 | Trust badges | Easy return, Safe & Secure (3D Secure), 14-day guarantee | P |
| C18 | Sticky add-to-cart bar | In markup (image, title, price, button). Fixed-on-scroll behaviour NV | P / NV |
| C19 | Recently viewed | Heading present | P |
| C20 | Related products, reviews | Not captured | NV |

### 1.4 Cart, checkout and account

| # | Feature | How it works | Ver. |
|---|---|---|---|
| D1 | Cart page | Empty state with "Back to shopping", subtotal, "Taxes and shipping calculated at checkout" | P |
| D2 | Cart drawer | Markup present (note, delivery date, discount). Interaction NV | P / NV |
| D3 | Order note | "Add note for seller" | P |
| D4 | Delivery date picker | "Pick a delivery date" | P |
| D5 | Shipping estimator | Name, company, address, city, country, province, ZIP, phone | P |
| D6 | Discount code | "Add a discount code" field | P |
| D7 | Terms checkbox | Before CHECK OUT | P |
| D8 | Checkout | Shopify checkout. Steps, fields, delivery methods and payment methods (COD?) | **NV** |
| D9 | Order confirmation and emails | **NV** | NV |
| D10 | Customer accounts | Shopify accounts subdomain: login/register, My orders, Profile, Settings | **NV** (login required) |
| D11 | Order tracking | No page (`/pages/track-order` and `/apps/track` return 404). The shipping policy says a tracking number is sent on dispatch | P |

### 1.5 Content and policies

| # | Feature | How it works | Ver. |
|---|---|---|---|
| E1 | Terms of service | Exists. Orders are accepted after confirmation, prices exclude shipping | P |
| E2 | Refund policy | Exists but contradicts itself (14-day and 30-day windows on one page). Unworn, tags on, original packaging. Exchange = return plus a new purchase | P |
| E3 | Shipping policy | Qatar only, fees by zone shown at checkout, 1–2 days processing plus 2–5 days delivery. The About page image alt text claims GCC delivery, which conflicts | P |
| E4 | Privacy and contact-information policies | Exist. Phone, email and address published | P |
| E5 | Contact page | Email, phone, socials, WhatsApp, form (full fields NV) | P |
| E6 | About page | Brand story and business hours (Sun–Thu) | P |
| E7 | FAQ page | None (404). FAQ only appears inside product pages | P |

**Takeaways:**
- The reference has data gaps: the length selector isn't stored, and color filters exist without color variants.
- Its policies contradict each other (14 vs 30 days, Qatar vs GCC).
- It has friction we can drop: age gate, terms checkboxes, a 4-button cookie banner.
- It is missing order tracking, quick ordering and color support.

We fix all of these.

---

## 2. Feature matrix

Copy = same function, our own design and content. Improve = same goal, better execution. Add = not on the reference. Skip = leave out.

| Ref | Feature | Decision | Reason |
|---|---|---|---|
| A2 | Header with search, account, wishlist, cart | Copy | Standard and expected |
| A3 | Social icons in header | Improve | Move them to the footer and a floating WhatsApp button, to keep header space on mobile |
| A4 | Seasonal collection nav | Improve | Lead with categories and put campaign collections (Eid, New) second, so the nav doesn't clutter |
| A5 | Mobile menu | Improve | Slide-in drawer that flips side for RTL, with category images |
| A6 | EN/AR with `/ar` prefix | Copy (decision Q35) | Most of Qatar's audience reads Arabic. Full RTL |
| A7 | QAR only | Copy | Qatar-only store |
| A8 | Hero | Improve | Admin-managed slides with separate mobile and desktop images and a working link |
| A9–A11 | Collection tiles, best sellers, promo block | Copy | Proven merchandising sections |
| A12 | Newsletter with terms checkbox | Improve | Collect a WhatsApp number or email, use consent text instead of a checkbox |
| A13 | Age gate | **Skip** | Irrelevant for clothing and blocks social visitors |
| A14 | Cookie banner (4 buttons) | Improve | One small non-blocking bar (Q30) |
| A15 | Footer | Improve | Add all policy links and a COD badge |
| B1 | Collection pages | Copy | Needed for campaigns |
| B2 | Filters | Improve | Color filter backed by real variant data, size, price, "In stock only". Mobile filters in a bottom sheet |
| B3 | 9 sort options | Improve | Cut to 5: Featured, Best selling, Newest, Price ↑, Price ↓ |
| B4 | Grid switcher | **Skip** | Adds clutter. Fixed 2 columns on mobile, 4 on desktop |
| B5 | Load more | Copy | Easy on mobile, and the URL keeps `?page` for SEO |
| B6 | Product card | Improve | Color swatches that change the card image, a real discount %, low-stock and sold-out badges |
| B7 | Search page | Copy | Same function |
| B8 | Search panel | Improve | Live suggestions with thumbnails. Arabic letter normalization so spelling variants match |
| C1 | Gallery | Improve | Images per color, swipe, pinch/fullscreen zoom, 4:5 ratio |
| C2–C3 | Breadcrumbs, price, compare-at | Copy | Standard |
| C4–C5 | Size and length | Improve | One size dimension stored on each variant (letters, lengths or free size). Nothing is lost on the way to the order |
| C6 | Color | **Add** | Core requirement (§4) |
| C7 | Stock state | Improve | Availability per color and size, plus "Only N left" when stock ≤ 3 |
| C8 | Add to cart | Copy, plus **Add** "Order now" | "Order now" goes straight to checkout, the fastest social path |
| C9 | Terms checkbox on product page | **Skip** | Friction. A terms notice at place-order is enough |
| C10 | Size guide | Improve | Per-product guide in cm and inches, opened in a bottom sheet |
| C11 | Delivery estimate | Copy | Calculated from delivery settings |
| C12 | Info accordions | Copy | Content managed in admin, EN/AR |
| C13 | Share | Improve | Native share sheet plus WhatsApp and copy link. The shared link keeps the chosen color |
| C14 | Wishlist | Copy | Saved in the browser for guests, synced to the account when logged in |
| C15 | Compare | **Skip** | Low value for one product type |
| C16 | Ask a question form | Improve | Prefilled WhatsApp message with product, color and size, with the contact form as fallback |
| C17 | Trust badges | Improve | Honest and configurable: COD, delivery across Qatar, the client's exchange policy |
| C18 | Sticky add-to-cart | Copy | Keeps the main button within thumb reach on mobile |
| C19 | Recently viewed | Copy | Stored in the browser, cheap |
| C20 | Related products | **Add** | Sells more: same category, plus "complete the look" picks |
| D1–D2 | Cart page and drawer | Copy | Standard |
| D3 | Order note | Copy | Moved into checkout |
| D4 | Delivery date | Copy | Optional "preferred date", toggled in admin (Q17) |
| D5 | Shipping estimator | Improve | Delivery-area dropdown with an instant fee. Qatar has no ZIP codes |
| D6 | Discount code | Copy, improved | Also auto-applies from a link (`?coupon=EID10`) for influencers |
| D7 | Terms checkbox | Improve | Inline agreement text instead |
| D8 | Checkout | Improve | Our own single-page, guest-first COD checkout |
| D10 | Accounts | Copy (optional, decision Q36) | Never required to order |
| D11 | Order tracking page | **Add** | Order number plus phone. Cuts "where is my order?" chats |
| E1–E6 | Policies, contact, about | Copy | The client's own content, edited in admin, one source of truth so pages can't contradict each other |
| E7 | FAQ page | **Add** | Answers COD, delivery and size questions in advance |

---

## 3. Customer journeys

**Target:** from a social link to a placed order in about 5 taps plus typing, with at most 6 required form fields.

**J1. Social video → Order now → done (main flow, guest)**
1. The visitor taps a short link from a TikTok, Instagram, Facebook or Snapchat bio or ad, for example `dohaalwafa.qa/p/K7Q2`.
2. The server records the click and saves campaign info (UTM, fbclid/ttclid/ScCid) in a cookie. It then redirects to `/ar/products/{slug}?color=navy&utm_…`.
3. The product page opens with **Navy selected**, so the gallery shows the navy photos. Size pills show availability for navy only, and sold-out sizes are struck through.
4. The visitor taps a size. If the product has only one size, it is preselected.
5. A sticky bar shows **Order now** (primary) and Add to bag.
6. The checkout page opens with the item summary and quantity stepper, then:
   - Full name, mobile (fixed +974 with a numeric keypad), delivery area (dropdown, fee shown instantly), Zone / Street / Building numbers ("from the blue plate").
   - Optional, behind "More details": unit/floor, landmark, map pin, email, note, preferred date, coupon.
   - Payment shows **Cash on Delivery** as the only method, plus the total.
7. The visitor taps **Place order**.
8. The server validates, re-checks stock with an atomic decrement, creates the order and payment record, and sends notifications.
9. The thank-you page shows the order number, summary, total to pay in cash, delivery estimate, a **"Confirm on WhatsApp"** button (prefilled), a tracking link and "Continue shopping".
10. The Meta and TikTok purchase events fire in the browser and from the server, with deduplication.
11. Staff confirm by phone or WhatsApp, then move the order through Confirmed → Packed → Out for delivery → Delivered. Cash collected sets the payment to Paid.

**J2. Browse and cart (several items):** Instagram profile link → Home → category chip → filter by color and size → product → Add to bag (drawer opens with "Checkout" and "Continue shopping") → more items → Checkout → as J1.

**J3. WhatsApp order:** The customer taps "Order on WhatsApp" on the product page (message includes product, color, size, qty and link). Staff create a **manual order** in admin, so stock and reports stay correct, and then send the tracking link.

**J4. Track order:** Use the link on the thank-you page, or go to `/track` and enter the order number and phone. The page shows a status timeline and a WhatsApp help button.

**J5. Returning customer (optional account):** Log in. Checkout is prefilled with the saved address. View My Orders.

**Edge cases:**
- The chosen color or size is sold out: suggest other sizes or colors, plus "Ask on WhatsApp".
- Stock changes between cart and checkout: the line is adjusted with a clear message, and the order is never oversold.
- Invalid phone: inline hint.
- The in-app browser reloads: checkout fields are restored from a local draft.

---

## 4. Multi-color product design

```
Product (shared: name, description, category, base price, size guide)
 └─ ProductColor (a palette color used by this product; its own images, sort order, default flag, active flag)
     ├─ ProductColorImage[] (ordered; image 1 is the cover for this color)
     └─ ProductVariant[] = ProductColor × Size  (SKU, stock, optional price override, active)
Color (global palette: name EN/AR, hex or swatch image for prints)
Size  (global, ordered: S…XXL, 52…62, Free Size)
```

**Rules:**
- **Images belong to a color.** The product thumbnail is the first image of the default color. Selecting a color swaps the gallery instantly in the browser: all colors' image URLs load as data with the page, and images lazy-load.
- **Stock lives on each variant**, one per color and size pair. The database enforces uniqueness on `(product_color_id, size_id)`.
- A variant is available if it is active and stock > 0. A color is available if any of its variants is. A product is available if any color is.
- **Price:** `variant.price ?? product.price`, and the same for compare-at. Listings show "From QAR X" when variant prices differ.
- **Default color:** the admin flag, otherwise the first available color. Sold-out colors stay visible (crossed swatch, sorted last) unless deactivated (Q11).
- **URLs:** `?color={slug}&size={slug}` preselect the options. The canonical URL drops these parameters. The OG image matches the chosen color.
- **Cards:** default-color image, up to 5 swatches plus "+N". Tapping a swatch changes the image, and the link keeps the color. With a color filter active, cards show that color's image.
- **Cart, order, admin:** each line references `product_variant_id`. Order items store a **snapshot** (product name EN/AR, color name and hex, size, SKU, image, unit price), so history survives later edits or deletion.
- **Stock changes** happen at order placement. Each change is a conditional update (`stock >= qty`) inside a transaction and is logged in `stock_movements` (order placed, cancelled, returned, manual adjustment, with user and note). Cancelling or returning restocks.
- **SKU** is generated automatically and can be edited, for example `JLB012-NVY-56`.
- **Edge cases:** a single-color product is simply one ProductColor. A "Free Size" product has one size. Prints and patterns use a swatch image.
- **Feeds and pixels:** one feed item per variant, with `item_group_id` = product, its own color/size attributes and the color's image. Pixel `content_ids` equal the variant SKU, so ads and the catalog match.

---

## 5. Original design direction

*This is a proposal. It will be adjusted to the client's logo, and Phase 1 delivers a style page for approval first.*

- **Mood:** warm, modern Gulf elegance, calm and premium but friendly. It deliberately avoids the stark black-and-white luxury-template look common to Shopify fashion themes. The reference's exact visuals are not verified, so we rely on our own system rather than contrast against it.
- **Color tokens** (in Tailwind `@theme` in `resources/css/app.css`):
  - sand background `#FBF8F3`
  - ink text `#1E1A17`
  - maroon primary `#7A1E35`, a nod to Qatar's maroon without using the flag
  - muted gold accent `#B8925A`, used sparingly
  - stone neutrals
  - success `#2F6B4F`, danger `#B42318`
  - All text meets WCAG AA contrast.
- **Typography** (through the existing `bunny()` font loader, with subsets by character range so English pages never download Arabic fonts):
  - Body: **IBM Plex Sans Arabic** for both Arabic and Latin, which gives one harmonious family.
  - Headings: **Fraunces** for Latin with **Noto Kufi Arabic** as fallback for Arabic characters.
  - At most 5 weights in total.
- **Signature details:**
  - A thin, original geometric line motif inspired by mashrabiya screens, used for dividers and empty states.
  - 4:5 product images, which also reuses social content.
  - Rounded-2xl cards and soft shadows.
  - Color names always shown next to the swatch (accessibility).
- **Mobile experience:**
  - Compact header: menu, logo, search, bag.
  - Swipeable gallery with dots.
  - Sticky bottom buttons within thumb reach.
  - Bottom sheets for filters, sizes and the size guide.
  - 44px tap targets, numeric keypads, autofill hints, no popups on landing, form state kept on errors.
- **Home layout:**
  1. Announcement bar
  2. Hero slides
  3. Horizontal category chips
  4. New arrivals (2-column)
  5. **Shop by color** swatch row (unique to this store)
  6. Promo banner
  7. Best sellers
  8. Trust strip (COD · delivery across Qatar · easy exchange)
  9. Follow-us CTA
  10. Footer
- **Product page layout:**
  1. Gallery
  2. Title and price
  3. **Color: Navy** with swatches
  4. Size pills
  5. Stock line
  6. Delivery promise
  7. Order now / Add to bag
  8. WhatsApp ask
  9. Accordions (details, size guide, delivery & exchange)
  10. Related products
- **RTL:**
  - Logical Tailwind utilities only (`ms-`/`me-`/`ps-`/`start-`) and mirrored arrow icons.
  - `<html lang dir>` set per locale.
  - Western digits for prices and phones in both languages (Q38).
- **How we differ from the reference:**
  - Color-first product page and cards.
  - Category-led navigation.
  - Our own single-page COD checkout instead of a multi-step hosted checkout.
  - No age gate or terms checkboxes.
  - Order tracking.
  - Original palette, type, motif, photography and copy.
  - Nothing reused: no logo, name, images or text.

---

## 6. Admin panel (custom, Blade)

**How it is built:**
- No admin package. The panel is a custom area at `/admin` built from Laravel controllers, Form Requests and Blade views.
- It shares the storefront's Tailwind design tokens in a simpler admin layout: a sidebar on desktop and a slide-in menu on mobile, so the client can manage orders from a phone.
- It uses a small set of reusable admin Blade components: data table (search, filters, sort and pagination through the URL query string), form fields, EN/AR field tabs, image uploader, confirm dialog, status badge, flash messages and stat card.
- CSV exports use Laravel's streamed responses.
- Charts are plain inline SVG or CSS bars, so no chart library is needed.

**Roles:** **Owner** (everything) and **Staff** (products and catalog, stock, orders, customers; no settings, reports or users — confirmed 2026-11-09). Admin login is rate-limited, sessions time out, and all admin routes sit behind auth and role middleware.

| Area | What the client can do |
|---|---|
| **Dashboard** | Today's and week's orders, orders pending confirmation, revenue (placed vs delivered), low stock, top products and colors, orders by traffic source |
| **Products** | Tabs: *General* (EN/AR name and description, category, collections, price, compare-at, status Draft/Active/Archived, badges). *Colors & images* (pick a palette color; upload several images at once; reorder with up/down buttons, which work on phones; the server crops to 4:5 using a top/center/bottom focus choice for tall phone photos; set default). *Sizes & stock* (a single **stock grid** form, rows = colors and columns = sizes, with each cell holding stock, an active toggle, and SKU and price override under "advanced"; "Generate variants" fills every color × size). *SEO* (slug, meta EN/AR). Also duplicate product and bulk status change |
| **Colors / Sizes / Size guides** | Palette (EN/AR name, hex or swatch image). Ordered sizes. Size guide tables (cm and inches, EN/AR notes) |
| **Categories / Collections** | EN/AR names, slugs, image, sort order, active. Collections: manual product picks, banner, optional date window |
| **Inventory** | All variants with filters (low, out of stock, color, category). Adjust stock with a required reason. Stock movement log. CSV export |
| **Orders** | Filters (status, payment status, date, area, source, coupon) with count badges. Order view: items with swatch, size, SKU and image; customer and address; map link; **Call / WhatsApp** buttons with message templates (confirm order, out for delivery); status actions with allowed transitions only; notes and status timeline; edit items before dispatch (stock adjusted); cancel or return with restock; mark COD collected; print invoice and packing slip (shows the COD amount to collect). **Create manual order** (channel: WhatsApp, Instagram DM, phone, walk-in) |
| **Order statuses** | Pending confirmation → Confirmed → Packed → Out for delivery → Delivered. Side exits: Cancelled, Delivery failed, Returned. Payment status: Pending → Paid / Refunded / Failed |
| **Customers** | Created automatically from orders, one per phone number. Order count, total spent, notes, **block** flag (fake-order phones), link to account |
| **Delivery** | Areas (EN/AR name, fee, free-over threshold, delivery estimate, active, sort). Settings: global free-delivery threshold, processing days, same-day cutoff, non-delivery days (Friday), preferred-date toggle |
| **Marketing** | Banners (mobile and desktop images, link, schedule, per language). Announcement bar. Coupons (percent, fixed or free delivery; min subtotal; dates; total and per-phone limits; first-order-only; usage stats). Short and campaign links (§8) with clicks, orders and revenue |
| **Content** | Pages (About, Delivery, Exchange & Returns, Privacy, Terms) EN/AR. FAQ items. Contact messages inbox. Subscribers with export |
| **Reports** | Sales by day, week or month. Orders by status, area, source/campaign, coupon. Top products, **colors and sizes** (restock insight). Cancellation and return rate. CSV exports |
| **Settings** | Store info, WhatsApp number and message templates, social links, pixel IDs and encrypted API tokens, notification recipients, trust badge text, low-stock threshold |

---

## 7. Qatar-specific needs

- **Currency:** QAR stored as integer dirhams (1 QAR = 100). Shown as `QAR 250` in English (no `.00` when whole) and `250 ر.ق` in Arabic. No currency switcher.
- **Phone:** a fixed `+974` prefix with 8 digits. Mobile numbers start with 3, 5, 6 or 7. The input accepts `+974 5555 1234`, `00974…` and spaces, and stores E.164 (`+97455551234`). The phone number identifies the customer.
- **Address (Qatar "blue plate" system):**
  - Delivery area (admin list, seeded with the 8 municipalities, which the client can split, for example Lusail or The Pearl)
  - Zone no., Street no., Building no.
  - Unit/floor (optional), landmark/directions (optional)
  - Optional location pin (browser location to a map link)
  - No ZIP or province fields. Strictness is decided in Q18.
- **Delivery:**
  - Fee per area, with an optional free-delivery threshold.
  - Delivery estimates skip non-delivery days (Friday by default).
  - Same-day cutoff setting.
  - Qatar only (Q19).
- **Time zone:** `Asia/Qatar`. Order dates and estimates use local time.
- **Tax:** no VAT line (confirm, Q27). A `tax_total` column stays at 0 for the future.
- **Privacy:** Qatar's Personal Data Privacy Protection Law (Law No. 13 of 2016) applies. Needs a clear privacy policy, consent for marketing messages and the consent approach for pixels (Q30).
- **Language (recommendation, your decision):**
  - **Launch with English and Arabic, full RTL.**
  - Arabic browsers get Arabic, everyone else gets English, and a switcher is always visible.
  - Social links carry the language explicitly (`/ar/…`, `/en/…`).
  - Cost: about 15–20% more front-end and content effort, since all text needs both languages.
  - Arabic-first social traffic converts better in its own language. Adding RTL later means reworking every template.
  - Either way, the code is built bilingual-ready from Phase 1: all UI text uses translation keys and data uses `_en`/`_ar` columns. English-only would just hide the switcher.

---

## 8. Social commerce features

| Feature | Evaluation and plan | When |
|---|---|---|
| **Fast mobile performance** | Server-rendered Blade with small Alpine.js (no slider or framework bundle). WebP images in 3 sizes with `srcset` and lazy loading. Preload the main image. Two font families with subsets. Cache catalog queries. Cloudflare CDN. Targets on mid-range Android over 4G: LCP < 2.5s, CLS < 0.1, INP < 200ms, product page JS < 60KB gzip. Test inside the **Instagram, TikTok, Facebook and Snapchat in-app browsers** | Launch |
| **Short shareable links** | `/p/{code}` per product and per product color, generated automatically. Admin campaign links (label, target, UTM preset) count clicks and later orders and revenue | Launch |
| **Social preview images** | Open Graph and Twitter tags per page. Product pages use the chosen color's image (1200px), title, price and availability, so WhatsApp, Instagram DM and Facebook previews look right | Launch. Auto-generated preview images with price overlay: Later |
| **WhatsApp** | Floating button (toggle). "Ask about this" and "Order on WhatsApp" on product pages with a prefilled message (product, color, size, link). "Confirm on WhatsApp" on the thank-you page. Admin click-to-chat templates. Manual orders keep stock correct | Launch. WhatsApp Business API automation: Later |
| **Meta Pixel and Conversions API** | Events: PageView, ViewContent, AddToCart, InitiateCheckout, Purchase (value in QAR, `content_ids` = SKU). Server-side events are queued, sent with hashed phone and email, and deduplicated with the browser events by `event_id` | Launch (needs IDs and tokens, Q29) |
| **TikTok Pixel and Events API** | Same events (ViewContent, AddToCart, InitiateCheckout, PlaceAnOrder), with deduplication | Launch |
| **Snapchat Pixel and GA4** | Snapchat is large in Qatar, so it is a toggle. GA4 handles general analytics | Launch (toggles) |
| **Campaign tracking** | First-party cookie stores UTM, click IDs, referrer and landing page (last non-direct visit, 30 days). Copied onto each order. Reports by source, campaign, short link and coupon | Launch |
| **Catalog feeds (Meta and TikTok)** | `/feeds/meta.csv` and `/feeds/tiktok.csv`, one row per variant with `item_group_id`, color, size, availability, price/sale price in QAR, the color's image and a deep link with color and size. Optional secret token. Cached and rebuilt on change. This enables dynamic or catalog ads and product tags where the platforms support Qatar | Launch |
| **Influencer coupon links** | `?coupon=CODE` auto-applies the code, and reports attribute orders to the influencer | Launch |

---

## 9. Recommended extras

**Launch (clear impact, low cost):**
- **"Order now"** one-tap path to checkout, the biggest conversion lever for social traffic.
- **Manual orders in admin** for WhatsApp, DM and phone sales, so stock and reports stay correct.
- **Printable invoice and packing slip** with the COD amount for the driver, which cuts delivery mistakes.
- **COD confirmation workflow** (Pending confirmation status plus WhatsApp templates). It cuts fake and no-show orders.
- **Fake-order protection:** rate limits, honeypot, phone blocklist, duplicate-order warning, max quantity per line.
- **Order tracking page.** Fewer support chats.
- **Honest low-stock message** ("Only 2 left in Navy / 56"). Urgency without lying.
- **Low-stock daily email** to the owner, so best sellers don't run out.
- **Checkout draft saved in the browser.** In-app browsers reload often, and the draft avoids losing the form.
- **FAQ page and related products.** Answers questions in advance and lifts average order value.

**Later (after launch, in priority order):**
1. **Abandoned checkout follow-up:** capture the phone number once it's valid, and give admin a WhatsApp follow-up list. High value, but needs a consent wording decision.
2. **Back-in-stock alerts** per color and size (WhatsApp or email).
3. **Customer reviews with photos.** Social proof.
4. **Online payment gateway**, for example SkipCash, Dibsy, QPay/NAPS, MyFatoorah or Tap, with Apple Pay. The structure is ready (§10).
5. **WhatsApp Business API** automatic messages (order received, out for delivery).
6. **OTP verification** for high-value COD orders.
7. Quick add from listings, bundles ("buy 2 save 10%"), and an Eid gift message or wrapping.
8. Courier integration, automatic discounts, loyalty and referrals.
9. Admin two-factor login (needs a TOTP package, which needs your approval). Until then: strong passwords, rate-limited login and session timeout.

---

## 10. Technical plan

### 10.1 Stack (needs approval: new dependencies)

Blade everywhere: storefront and admin. No Filament and no Livewire.

| Package | Why |
|---|---|
| `intervention/image` (composer) | Generates the WebP image sizes (400, 800, 1200px) and the 4:5 crop, on the queue |
| `alpinejs` (npm, about 15KB gzip) | Small interactive pieces in Blade: color and size picker, gallery, bottom sheets, cart drawer, admin uploader and stock grid. Without it, the same code would be hand-written plain JavaScript |

No other packages are needed:
- Authentication (admin and customers) is hand-written on Laravel's built-in `Auth`, password reset broker and `RateLimiter`.
- Translations use `_en`/`_ar` columns.
- Settings, sitemap, feeds, pixels, CSV export and charts are custom code.

**Storefront approach:**
- Controllers and Blade for every page (fast, cacheable, SEO-friendly).
- Alpine for instant interactions.
- Cart actions are normal form POSTs that also accept `fetch()` from Alpine and return the rendered drawer partial. They keep working even if JavaScript fails inside in-app browsers.
- Checkout is a standard POST validated by a Form Request. Alpine adds the live delivery fee and the draft saving.
- Search suggestions come from a small JSON endpoint.

**New folders (needs approval):**
- `app/Enums/`
- `app/Actions/` (PlaceOrder, CancelOrder, AdjustStock, UpdateOrderStatus)
- `app/Services/` (Cart, DeliveryFee, Attribution, Tracking/Pixels, Feeds)
- `app/Support/` (QatarPhone, Money)
- `app/Http/Controllers/Storefront/`, `app/Http/Controllers/Admin/`
- `app/Http/Requests/`
- `app/Http/Middleware/` (SetLocale, EnsureUserIsStaff, CaptureAttribution)
- `app/Policies/`
- `resources/views/{storefront,admin}/`, `resources/views/components/{store,admin}/`

### 10.2 Database (main tables)

- **users** (exists): adds `role` (owner, staff, customer).
- **customers:** user_id?, name, phone (E.164, unique), email?, locale, is_blocked, notes, orders_count, total_spent, last_order_at.
- **addresses:** customer_id, delivery_zone_id, zone_no, street_no, building_no, unit, landmark, lat, lng, is_default.
- **categories:** parent_id?, name_en/ar, slug, image, sort, is_active.
- **collections:** name_en/ar, slug, banner, starts/ends_at, is_active.
- **collection_product:** pivot linking collections and products.
- **products:** category_id, name_en/ar, slug (unique), description_en/ar, details_en/ar, price, compare_at_price, status, is_featured, badge, size_guide_id, sales_count, search_text (normalized EN and AR), meta_*_en/ar, published_at.
- **colors:** name_en/ar, slug, hex, swatch_image, sort.
- **sizes:** name, slug, group (letter, length, free), sort.
- **size_guides:** name, rows (JSON), notes_en/ar.
- **product_colors:** product_id, color_id, label_en/ar?, sort, is_default, is_active. Unique (product_id, color_id).
- **product_color_images:** product_color_id, path, alt_en/ar, sort, plus generated size paths.
- **product_variants:** product_id, product_color_id, size_id, sku (unique), price?, compare_at_price?, stock, low_stock_threshold?, is_active. Unique (product_color_id, size_id). Index on (product_id, is_active, stock).
- **stock_movements:** product_variant_id, change, stock_after, reason (enum), order_id?, user_id?, note.
- **carts:** token (cookie), user_id?, coupon_code?, delivery_zone_id?, expires_at.
- **cart_items:** cart_id, product_variant_id, quantity. Unique (cart_id, product_variant_id).
- **delivery_zones:** name_en/ar, fee, free_over?, eta_min_days, eta_max_days, is_active, sort.
- **coupons:** code (unique), type, value, min_subtotal, starts/ends_at, max_uses, max_uses_per_phone, first_order_only, is_active, plus **coupon_redemptions**.
- **orders:**
  - number (unique, `DW-10001`), customer_id, user_id?, channel (web, whatsapp, …), status, payment_method, payment_status, currency
  - subtotal, discount_total, delivery_fee, tax_total, grand_total (integer dirhams)
  - coupon snapshot
  - customer snapshot (name, phone, email)
  - address snapshot plus delivery_zone_id
  - preferred_delivery_date, customer_note, admin_note, locale
  - attribution: utm_source, medium, campaign, content, term; click_id_type, click_id; referrer, landing_url, short_link_id
  - ip, user_agent
  - placed_at, confirmed_at, shipped_at, delivered_at, cancelled_at, cancel_reason
- **order_items:** order_id, product_variant_id? (null on delete), product_id?, snapshot (name_en/ar, color_name_en/ar, color_hex, size_name, sku, image), unit_price, compare_at_price, quantity, line_total.
- **order_status_histories:** order_id, from, to, note, user_id?, created_at.
- **payments:** order_id, method, provider?, status, amount, currency, reference?, meta (JSON), paid_at. These are the gateway hooks.
- **Marketing, content and other tables:**
  - banners, announcements
  - pages (slug, title/body en/ar), faqs
  - contact_messages, subscribers
  - short_links (code, label, product_id?, product_color_id?, url?, utm_*, clicks)
  - wishlist_items (user_id, product_color_id)
  - settings (key, value JSON, cached)
- **Enums (TitleCase keys):** OrderStatus with an allowed-transitions map, PaymentStatus, PaymentMethod, OrderChannel, ProductStatus, StockMovementReason, UserRole.

**Payment flexibility:**
- A `PaymentGateway` contract with `CashOnDeliveryGateway` as the only implementation.
- Checkout lists the gateways enabled in config.
- Each order can have many `payments` rows (attempts, refunds), and the order's `payment_status` follows from them.
- A future gateway adds a class and a webhook route, with no schema change.

**Stock safety:**
- `PlaceOrder` runs in one transaction. For each line it runs `UPDATE product_variants SET stock = stock - :qty WHERE id = :id AND stock >= :qty AND is_active`. Zero affected rows aborts with a friendly "only N left" message.
- This works on MySQL in production and on SQLite in tests.

### 10.3 Routes

Pages are under a `/{locale}` prefix (`en` or `ar`). Named routes:

| Route | Name |
|---|---|
| `/` | `home` |
| `/shop` | `shop` |
| `/category/{category:slug}` | `category` |
| `/collection/{collection:slug}` | `collection` |
| `/products/{product:slug}` | `product.show` |
| `/search` | `search` |
| `/cart` | `cart` |
| `/checkout` | `checkout` |
| `/checkout/buy-now/{variant}` | `checkout.buy-now` |
| `/orders/{order:number}/thank-you` | `orders.thank-you` (signed URL) |
| `/track` (GET, POST) | `track` |
| `/wishlist` | `wishlist` |
| `/faq` | `faq` |
| `/contact` (GET, POST) | `contact` |
| `/pages/{page:slug}` | `page` |
| `/subscribe` (POST) | `subscribe` |
| `/login`, `/register`, `/forgot-password` | auth routes |
| `/account` (orders, order detail, profile, addresses) | `account.*` |

**Without a language prefix:**
- `/` redirects by cookie, then browser language.
- `/p/{code}` (short links)
- `/feeds/meta.csv`, `/feeds/tiktok.csv`
- `/sitemap.xml`, `robots.txt`
- `/up`

**Admin** (`/admin`, English, middleware `auth` + `staff`, resource controllers named `admin.*`):

| Area | Routes |
|---|---|
| Login | `/admin/login`, `/admin/logout` |
| Dashboard | `admin.dashboard` |
| Catalog | `admin.products.*`, `admin.products.stock` (grid update), `admin.products.images.*`, `admin.colors.*`, `admin.sizes.*`, `admin.size-guides.*`, `admin.categories.*`, `admin.collections.*` |
| Inventory | `admin.inventory.index`, `admin.inventory.adjust`, `admin.stock-movements.index` |
| Orders | `admin.orders.*` (including manual `create`/`store`), `admin.orders.status` (POST transition), `admin.orders.invoice`, `admin.orders.packing-slip`, `admin.orders.export` |
| Customers | `admin.customers.*` |
| Delivery | `admin.delivery-zones.*` |
| Marketing | `admin.coupons.*`, `admin.banners.*`, `admin.announcements.*`, `admin.short-links.*` |
| Content | `admin.pages.*`, `admin.faqs.*`, `admin.messages.*`, `admin.subscribers.*` |
| Owner only | `admin.reports.*`, `admin.settings.*`, `admin.users.*` |

### 10.4 Components

**Blade components:**
- Layout: layout (lang/dir, meta/OG, pixels), header, mobile-menu, announcement-bar, footer, language-switcher, breadcrumb
- Catalog: product-card (swatches), color-swatches, size-picker, gallery (scroll-snap plus zoom), price, stock-badge, related-products
- Inputs and overlays: qty-stepper, bottom-sheet/modal, accordion, phone-input, delivery-area-select
- Listing: filter-sheet, sort-select, load-more
- Messaging and sharing: empty-state, toast, whatsapp-button, share-button (Web Share API)
- Orders and trust: trust-strip, order-timeline
- Home: banner-slider

**Store interactive parts (Blade plus Alpine):** cart-drawer, cart-page, checkout-form, search-suggestions.

**Admin Blade components:**
- Page structure: layout (sidebar, mobile menu), page-header with actions
- Data display: data-table, filters, pagination, status-badge, stat-card, svg-bar-chart
- Forms and editing: form fields (text, number, money, select, toggle, textarea, date), locale-tabs (EN/AR), image-uploader (multi-file, up/down reorder), stock-grid (colors × sizes)
- Feedback and timeline: confirm-dialog, flash-toast, timeline

**JS helpers:**
- `track()`, which fans events out to the enabled pixels with an `event_id`
- recently-viewed and wishlist storage in the browser
- checkout draft persistence

---

## 11. Quality plan

| Area | How |
|---|---|
| **Automated tests (Pest feature tests, factories with states)** | Catalog visibility (draft or archived hidden). Availability per color and size. Price resolution. Cart limits (stock, inactive variant). Checkout validation (Qatar phone datasets: valid and invalid). Delivery fee and free threshold. Coupon rules. **Order placement decrements the exact variant**. **Last-unit race: a second order fails cleanly**. Snapshot survives product edits. Cancel or return restocks. Status transitions allowed or blocked. Tracking requires the matching phone. Thank-you URL is signed. Customers can't open `/admin` or others' orders. Staff can't open settings or reports. Admin controller tests cover create, update and validation for products and the stock grid, status transitions and manual orders. Feeds output. Short-link redirect with UTM saved on the order. Locale routes and `dir="rtl"`. SEO tags present. Unit tests for QatarPhone, Money and the order-number generator |
| **Stock accuracy** | A movement-log check (sum of movements = current stock) as a test and as an admin check |
| **Mobile layouts** | Widths 360, 390, 414, 768, 1280 and 1440 in LTR and RTL. Real-device pass in the Instagram, TikTok, Facebook and Snapchat in-app browsers on iOS and Android. Chrome-extension screenshots when it's connected |
| **Order flow** | End-to-end script per phase: social link → product → order → admin lifecycle → tracking, as guest and as account |
| **Security** | Form Requests on every input. CSRF. Rate limits (checkout, track, coupon, contact, login). Honeypot. Policies and roles. Signed URLs. Image-only uploads with size limits. Sanitized rich text. Encrypted settings secrets. Security headers. Rate-limited admin login and session timeout. `composer audit` and `npm audit` |
| **Performance** | Lighthouse mobile ≥ 90 on home, listing, product and checkout. Lazy-loading guard in development (`Model::preventLazyLoading`). Index review. Queued image, email and server-side event jobs. Image size budget |
| **SEO** | Unique EN/AR titles and descriptions. Canonical. `hreflang` (en, ar, x-default). Product JSON-LD (offers per variant, QAR, availability). BreadcrumbList and Organization. `sitemap.xml` with language alternates. `robots.txt`. Clean slugs |
| **Process** | `vendor/bin/pint --dirty --format agent` and the narrowest `php artisan test --compact` after each change. Full suite at the end of each phase |

---

## 12. Implementation phases

Each phase ends with: a summary of changes, how to test it, and a stop for your approval.

| Phase | Deliverables | Acceptance criteria |
|---|---|---|
| **0. Save plan** | Save `docs/PROJECT_PLAN.md`, start MySQL | File saved. Decisions recorded |
| **1. Foundation and design system** | Install approved packages. Language routing (`/en`, `/ar`) with RTL. Store layout shell (header, menu, footer). Design tokens and fonts. Base components. Settings store. Roles. **Admin auth** (login, logout, rate limit, `staff` middleware). **Admin layout and UI kit** (table, form fields, flash, confirm). `Asia/Qatar` time zone. A local-only style page | `/en` and `/ar` render with the correct `lang`/`dir`. Owner can log in to `/admin`, customer is blocked (test). Lighthouse ≥ 95 on the shell. **You approve the store and admin look** |
| **2a. Catalog basics and admin** | Migrations, models, factories and seeders for categories, collections, colors, sizes and size guides, with their admin CRUD screens (EN/AR fields) | The client can create, edit and delete each item with validation. Tests pass |
| **2b. Products, colors, stock and admin** | Products, product colors, images (queued WebP sizes and 4:5 crop), variants, stock movements. Admin product form, per-color image uploader, **stock grid**, inventory list, adjustments and log. Demo product seeder | A product with 3 colors × 4 sizes takes under 5 minutes to create. Images reorder per color. Stock grid saves exactly. Stock edits are logged. Model rule tests pass |
| **3. Storefront browsing** | Home (banners admin and sections). Shop, category and collection pages with filters, sort and load more. Search with suggestions and Arabic normalization. Product page (color-driven gallery, sizes per color, stock line, size guide, delivery estimate, accordions, share, related, recently viewed). Guest wishlist. 404 page | Switching color updates images and sizes instantly and updates the URL. Sold-out combinations are disabled. `?color` deep links work. Filters combine correctly. Tests pass |
| **4. Cart and delivery areas** | Cookie-token database cart, drawer and page, quantity limits from stock, delivery areas admin, fee and free-delivery progress, "Order now" path | Can't exceed stock. Price or stock changes show up. Fee is correct per area. Tests pass |
| **5. COD checkout and order placement** | Single-page checkout (Qatar phone and address). Notes and preferred date. `PlaceOrder` (transaction, atomic stock, snapshots, payment row, customer upsert, order number). Signed thank-you page. Admin and customer emails. Fake-order protection. Checkout draft | Guest order completes at 360px. Last-unit race test passes. Stock exact. Snapshot kept after product edit |
| **6. Order management and tracking** | Admin orders (list with filters, view, transitions, timeline, notes, WhatsApp/Call templates, invoice and packing slip, edit before dispatch, cancel or return with restock, mark COD collected). Manual orders. Customers and blocklist. New-order notifications. Low-stock digest. `/track` | Full lifecycle test. Blocked transitions rejected. Restock correct. Staff permissions enforced |
| **7. Promotions and content** | Coupons (rules, URL auto-apply, stats). Announcement bar. EN/AR pages and policies, FAQ, contact form and inbox. Subscribers and export. Trust badges from settings. Consent bar | Coupon rule tests pass. All content editable in both languages |
| **8. Customer accounts** | Email and password register/login/reset. My orders and detail. Profile. Saved addresses. Wishlist sync. Checkout prefill | Guest checkout unchanged. Customers see only their own data (tests) |
| **9. Social commerce** | Short and campaign links. Attribution saved on orders. Meta, TikTok and Snap pixels plus GA4 (consent-aware). Server-side events with deduplication. Meta and TikTok feeds. OG tags per color. WhatsApp buttons | Events show in Meta Test Events and TikTok Pixel Helper. Feeds validate. A short link opens the right color and its UTM is stored on the order |
| **10. Dashboard and reports** | KPI cards, SVG/CSS charts, breakdowns by status, area, source, campaign, coupon, product, color and size. CSV exports | Report figures match seeded test data |
| **11. SEO, performance, security, launch** | JSON-LD, sitemap, hreflang. Caching, CDN, image tuning. Headers, audits. Device and in-app browser QA. Deployment (queue worker, scheduler, backups, HTTPS) and launch checklist | Lighthouse mobile ≥ 90 performance and ≥ 95 SEO and accessibility on key pages. Full suite green. Launch checklist signed off |

---

## 13. Open questions (recommended defaults in **bold**)

| # | Question | Default |
|---|---|---|
| **Brand and content** | | |
| Q1 | Logo (SVG), brand colors, any brand fonts? | **Use the maroon and sand proposal and adapt it once the logo arrives** |
| Q2 | Product photos per color (at least 3, plain background plus on-model)? | **4:5 crop at upload. Placeholders until photos are supplied** |
| Q3 | Domain and a sender email (orders@…)? | **Client buys the domain. Cloudflare DNS** |
| Q4 | WhatsApp number, phone, email, showroom address, hours, social handles (TikTok, IG, FB, Snap)? | — (required) |
| Q5 | Delivery, exchange and returns, privacy and terms text (EN/AR)? | **I supply a section structure. Client or their adviser writes the final text** |
| **Products** | | |
| Q6 | How many products at launch? Who enters them? | **Client enters them in admin. I seed demo data for testing** |
| Q7 | Size system: letters, lengths (52–62), free size, kids? | **Support all. Seed letters, lengths and Free Size** |
| Q8 | Does price ever differ by color or size? | **Product price, with an optional override per variant** |
| Q9 | Category and collection list? | **Women / Men / Kids, plus New Arrivals, Best Sellers, Eid** |
| Q10 | Show exact stock to shoppers? | **Only "Only N left" when ≤ 3** |
| Q11 | Show or hide sold-out colors and sizes? | **Show them disabled and labelled. Admin can deactivate** |
| Q12 | Size guide measurements per style? | **One guide per product type, in cm and inches** |
| **Delivery** | | |
| Q13 | Delivery areas and fee per area? | **8 municipalities with placeholder fees for the client to edit** |
| Q14 | Free-delivery threshold? | **One global threshold (amount from the client), optional per-area override** |
| Q15 | Delivery times, same-day cutoff, non-delivery days? | **1–3 business days, no Friday delivery, cutoff off** |
| Q16 | Own driver or courier company? | **Own driver or local courier, using the printable packing slip** |
| Q17 | Offer a preferred delivery date? | **Yes, optional, next 7 delivery days** |
| Q18 | Are Zone, Street and Building numbers required? | **Required, with a "find it on the blue plate" hint. Map pin optional** |
| Q19 | Deliver outside Qatar? | **No, Qatar only** |
| **Orders and payments** | | |
| Q20 | Confirm every COD order by phone or WhatsApp before dispatch? | **Yes, orders start as "Pending confirmation"** |
| Q21 | Are the order statuses in §6 right? | **Yes, as listed** |
| Q22 | Exchange and return window and conditions? | **Client's policy. One setting feeds both badges and pages** |
| Q23 | Who gets new-order alerts? | **Owner email plus admin panel notification** |
| Q24 | Number of staff users and their access? | **Owner and Staff roles** |
| Q25 | Maximum quantity per line? | **10** |
| Q26 | Preferred future payment gateway? | **Decide later. Structure is ready** |
| Q27 | VAT or tax on invoices? CR number on invoice? | **No tax line. Show CR number if the client provides it** |
| **Marketing** | | |
| Q28 | Launch offer or first-order coupon? | **None at launch. The coupon engine is ready** |
| Q29 | Pixel IDs and API tokens (Meta, TikTok, Snap, GA4)? | **Build with on/off toggles. Add the IDs when ready** |
| Q30 | Cookie and consent approach for ad pixels? | **Small bar. Ad pixels load after "Accept"**. The client may switch to notice-only after legal advice (better tracking, more legal risk) |
| Q31 | Newsletter channel? | **WhatsApp number (email optional) with explicit consent text** |
| **Technical** | | |
| Q32 | Approve the new dependencies (`intervention/image`, `alpinejs`) and the new folders (§10.1)? | **Approve**. Without Alpine I write plain JavaScript, which is slower to build |
| Q33 | Admin panel language? | **English UI, with EN/AR fields for all content**. An Arabic admin UI can come later |
| Q34 | Hosting? | **Decide by Phase 11: Laravel Cloud or a managed VPS, with Cloudflare in front and images on S3 or R2** |
| Q35 | English and Arabic at launch? Default language? | **Yes, both. Arabic for Arabic browsers, English otherwise** |
| Q36 | Customer accounts at launch? | **Yes, optional, in Phase 8 (can be dropped without affecting other phases)** |
| Q37 | Abandoned-checkout follow-up (Later list)? | **Later, after you confirm the consent wording** |
| Q38 | Digits in the Arabic UI? | **Western digits (0–9) for prices and phones** |
| Q39 | Client admin guide document? | **Only if you ask for it (Phase 11)** |
| Q40 | Visual review of the reference with the Chrome extension? Note: the reference's Terms ask automated agents to identify themselves | **Optional. Reconnect the extension before Phase 3 if you want it. The plan doesn't depend on it** |

---

## Verification (end to end, per phase and at launch)

1. Run `php artisan test --compact` (full suite) and `vendor/bin/pint --dirty --format agent`.
2. Run `composer run dev` and walk J1–J5 at 360px (Chrome device mode, or the extension when connected) in `/en` and `/ar`.
3. In `/admin` (custom Blade panel), create a 3-color product. Place a guest COD order using a short link. Check the stock drop and the attribution. Take the order through every status. Cancel a second order and check the restock. Print the packing slip.
4. In Phase 9, confirm the events in Meta Test Events and TikTok Pixel Helper, and validate the feeds in Commerce Manager.
5. Run Lighthouse mobile on home, listing, product and checkout against the targets in §11.

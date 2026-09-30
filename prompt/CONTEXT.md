# Iceland Beach Resort — Engineering Handoff

**Written:** 2026-09-30 · **Branch:** `main` · **Repo:** https://github.com/Manlikenonso1k/iceland-beach.git
**Production:** icelandbeach.com · **Staging:** staging.icelandbeach.com
**Test suite:** 37 passing, 145 assertions — `php artisan test`

Everything a new engineer or agent needs to work on this codebase without
rediscovering it. Facts here were read out of the repository, not assumed.
Where something could not be verified, it says so.

**Related docs:** [../context.md](../context.md) (working notes, overlaps this
file) · [../README.md](../README.md) (changelog) ·
[../FILAMENT_UI.md](../FILAMENT_UI.md) (admin UI mechanics) ·
[../PAYMENTS_INTEGRATION.md](../PAYMENTS_INTEGRATION.md) (portable payments
guide) · [../RESULT.md](../RESULT.md) (ticketing) ·
[../HOSPI_SALES_BRIEF.md](../HOSPI_SALES_BRIEF.md) (planned mobile app)

---

## 1. What this is

A resort management system: a public marketing/booking site, a staff admin
panel, an events ticketing system with QR gate entry, a referral-partner
portal, and two payment gateways.

| Layer | Technology |
|---|---|
| Framework | Laravel 12 (`laravel/framework ^12.0`), PHP ^8.2 (local runs 8.5.1) |
| Admin | Filament 3 (`filament/filament ^3.2`) + `saade/filament-fullcalendar ^3.2` |
| Public site | React 19 SPA (`resources/js/`), Vite, React Router, Framer Motion, Three.js |
| Auth | Laravel Sanctum ^4.0 · `spatie/laravel-permission 7.1.*` |
| PDF / QR | `barryvdh/laravel-dompdf ^3.1` · `simplesoftwareio/simple-qrcode ^4.2` |
| DB | MySQL in production · **SQLite `:memory:` in tests** (this matters — §6) |
| Node | v24.19.0 |

### Repo layout that matters

```
app/
  Filament/
    Resources/        14 admin resources
    Pages/            Dashboard, ManageSettings, ScanTicket
    Widgets/          9 dashboard widgets
    Referral/         the SEPARATE partner panel's components
    Concerns/         RestrictsFrontDeskAndScanner
  Http/Controllers/
    Api/              BookingController, CatalogController, ContactController,
                      ServiceBookingController
    (web)             PaymentController, BookingPaymentController,
                      BookingReceiptController, PaystackWebhookController,
                      VendorPaymentController
  Services/           ActivityLogService, BookingWorkflowService, PaystackService,
                      PosService, ReservationService, TicketPaymentRefreshService
  Models/             23 models
  Observers/          TicketObserver
  Policies/           ActivityLog, Invoice, Role, User
  Providers/Filament/ AdminPanelProvider, ReferralPanelProvider
resources/js/         React SPA — app.jsx + pages/ + components/
routes/               web.php (public + payments), api.php (JSON)
deploy.sh             run by CI on push to main
```

---

## 2. Architecture: the systems

### 2.1 Two booking systems — read this first

The single most confusing thing here.

| | `room` table | `bookings` table |
|---|---|---|
| Columns | `is_booked` enum, `start_date`, `end_date`, `customer_name`, `email` | `room_id`, `check_in`, `check_out`, `status`, `guest_*` |
| `is_booked` | `no` · `booked` · `rejected` · `expired` | — |
| `status` | — | `pending` · `confirmed` · `rejected` · `pending_payment` · `payment_failed` |

The rooms table is **`room`**, singular and legacy. Validation reads
`exists:room,id`. Don't write `rooms`.

**`bookings` is canonical.** It previously held only a `room_type` *string* with
no `room_id` and no `check_out`, so it could not be joined to a room or tested
for overlap — which is why availability was historically read off
`room.is_booked`.

The availability rule lives in exactly one place, `Booking::scopeBlocking()`:

> **Only a `confirmed` booking holds a room.** `pending` and `rejected` never do.

`room.is_booked` is a **denormalised mirror** kept in step for the Room-based
calendar and widgets. If the two disagree, `bookings` wins.

⚠️ `rejected` and `expired` are historical states, not live holds. The old
front-end treated anything other than `'no'` as unavailable, so rejected
bookings blocked rooms. Never reintroduce `is_booked !== 'no'`.

### 2.2 All booking state changes funnel through one service

`app/Services/BookingWorkflowService.php` — `confirmBooking()`,
`rejectBooking()`, `markBookingPaid()`.

`BookingResource`'s actions, `PendingBookingsWidget`, `ConfirmedBookingsWidget`,
`BookingPaymentController` and `PaystackWebhookController` all call it.

**Why it exists:** these paths used to carry separate copies of the logic and had
already diverged — one emailed the guest, the other didn't. Status, room mirror,
activity log and guest email now cannot drift apart. Add new callers here, not
alongside.

### 2.3 Two panels, two guards

| | `/admin` | `/referral` |
|---|---|---|
| Provider | `AdminPanelProvider` | `ReferralPanelProvider` |
| Guard | `web` (`App\Models\User`) | `referral` (`App\Models\Referral`) |
| Components | `App\Filament\Resources\*` | `App\Filament\Referral\*` only |

`App\Models\Referral` extends `Authenticatable` and implements `FilamentUser`.
`config/auth.php` carries the `referral` guard and `referrals` provider.

⚠️ **Partners used to be `User`s with a `Referral` role, scoped in
`ReferralResource::getEloquentQuery()`. That design is gone.** `ReferralResource`
is now staff-only and manages partner records; `'Referral'` was removed from
`User::canAccessPanel()`. Every `/admin` resource carries
`canAccess(): auth()->guard('web')->check()` so a partner token cannot reach
staff data even by URL.

### 2.4 Payments — two gateways, three flows

| Flow | Entry point | Gateway |
|---|---|---|
| Event tickets | `PaymentController` (inline popup → `/verify-payment`) | Paystack + TGI Pay |
| Room bookings | `BookingPaymentController` (redirect → callback) | Paystack |
| Vendor spots | `VendorPaymentController` | TGI Pay |

`app/Services/PaystackService.php` — `isConfigured()`, `initialize()`,
`verify()`, `verifyWebhookSignature()` (HMAC-SHA512 via `hash_equals`),
`generateReference('BOOK')`. **Amounts are integer kobo.**

Full portable writeup: [../PAYMENTS_INTEGRATION.md](../PAYMENTS_INTEGRATION.md).

### 2.5 Ticketing and gate entry

`tickets` (uuid, amount in kobo, `paid`, `checked_in`, `scan_count`,
`qr_code_path`, `referral_code`) → QR encodes `GET /ticket/verify/{uuid}` →
scanned at `/admin/scan-ticket` → logged in `ticket_scan_logs`.

### 2.6 Referral programme

Ticket buyers enter a partner code at checkout; the partner is credited only on
successful payment.

**Why attribution lives in a model observer, not the payment callbacks:** a
ticket reaches `paid = true` from **four** places —
`PaymentController::paystackVerify()`, `::tgiPayCallback()`,
`::tgiPayWebhook()`, and `TicketPaymentRefreshService`. `App\Observers\TicketObserver`
(registered in `AppServiceProvider::boot()`) catches all four, so no path can be
forgotten and the payment code stays untouched.

- The **double-credit guard is the `unique` index on
  `referral_ticket_sales.ticket_id`**, not caller discipline. The `exists()`
  pre-check is an optimisation; the `QueryException` catch is the real handler
  for racing callbacks.
- `commission_rate` is **snapshotted per sale** — a partner's rate may change and
  historical payouts must not move with it.
- Unknown or deactivated codes are ignored silently and must **never** block a purchase.
- The checkout input is still `name="promo_code"` so the existing `ICECOCONUT`
  discount and the pricing AJAX endpoint keep working. Only the label says
  "Referral Code".

### 2.7 Existing POS

`products`, `sales`, `sale_items`, `dining_tables`, `waiters`, `void_logs`, with
`PosService::processSale()` doing a table-assignment guard, a 60-second
duplicate-sale window, stock checks, a mandatory `void_reason`, and one atomic
transaction.

⚠️ These tables have **no order status, no accept/decline, no timing**. A sale is
a completed cash event, not a tracked order. Relevant to the planned mobile app
(§8).

---

## 3. Access control

`spatie/laravel-permission`. `RoleSeeder` creates exactly: `Super Admin`,
`Admin`, `Manager`, `Front Desk`, `Waiter`, `Scanner`, `Referral`.

⚠️ **No permissions are defined — only role names.** Every gate tests role names
directly. Don't reach for `can()` or permission checks; they aren't wired up.

- `User::canAccessPanel()` gates on role, plus an email bypass for
  `victorynonso9@gmail.com`. A user can authenticate successfully and still be
  refused the panel — **any new admin account needs a role assigned.**
- `app/Filament/Concerns/RestrictsFrontDeskAndScanner.php` removes
  Administration and Events from Front Desk and Scanner.
- ⚠️ **`/admin/scan-ticket` is a deliberate exception.** It sits in the Events
  group, so restricting Events originally locked the **Scanner** role out of the
  only page it exists for. `ScanTicket::canAccess()` now excludes Front Desk
  only, and is a **denylist not an allowlist** so the roleless owner account
  admitted by email isn't locked out. See
  `tests/Feature/RestrictedAdminSectionsTest.php`.
- ⚠️ **`app/Policies/UserPolicy.php` tests `hasRole('super_admin')`** but the
  seeder creates `Super Admin`. Snake_case never matches, so the policy is dead
  for `UserResource` (whose `canAccess()` is overridden anyway). Use the seeded
  names; don't copy the typo.

---

## 4. Decisions and their rationale

| Decision | Why |
|---|---|
| `bookings` canonical, `room.is_booked` a mirror | the room table can't express overlap or be joined reliably |
| Only `confirmed` blocks a room | a request awaiting review must not make a room look taken |
| One `BookingWorkflowService` | the duplicated copies had already diverged on emailing |
| `markBookingPaid()` idempotent | Paystack's callback and webhook routinely both fire; the guest must not be emailed twice |
| One webhook handler, dispatch by reference prefix | **Paystack allows only ONE webhook URL per mode**; `BOOK-` → booking, else ticket. Three route aliases point at it so whichever URL is already in the dashboard keeps working |
| Paying auto-confirms | a paid booking skips the staff queue; gated by `booking_payment_required` in `settings` |
| Receipt routes use `signed` middleware | a booking id is a small sequential integer — unsigned, anyone could walk the range and harvest guest name/email/phone |
| Referral credited in an observer | four separate code paths set `paid = true` |
| Referral partners on their own guard + panel | role-based scoping inside one panel left staff data one URL edit away |
| Admin booking form: phone required, email optional | staff take bookings by phone where no email exists. **Public form still requires email — Paystack needs a receipt address.** Don't "fix" the asymmetry |
| Snapshot rates/prices per row | a later rate change must not rewrite history |
| Money as integer kobo | matches `tickets.amount`; avoids float drift |
| Three.js code-split behind `React.lazy` | keeps the shader out of the main bundle |

---

## 5. Current state (recent work, newest first)

| Commit | What |
|---|---|
| `1c633c8` | refreshed `context.md` — the referral section had gone actively misleading |
| `7bbfb80` | **Scanner can scan again** — role had been locked out of `/admin/scan-ticket` |
| `d6c335f` | **scan-ticket page repaired** (Livewire root elements) + Hospi Sales brief and agent prompts |
| `7eae7cd` | **email optional for manual bookings**, phone primary + nullable migration |
| `2be7aca` | tests covering the booking API rate limits |
| `1ca2bb5` | Front Desk / Scanner restricted from Administration and Events |

Earlier: referral panel isolation, Paystack booking payments, revenue widgets and
pie charts, brand colour to `#002F61` navy, booking calendar fixes, activity
logging, email templates, `deploy.sh` created.

### What works and is covered by tests

Booking workflow (confirm/reject/pay, idempotency), booking calendar widget,
booking receipts and signed links, invoice totals and numbering, TGI Pay ticket
confirmation + QR + receipt, API rate limits, manual phone-only bookings, role
restrictions, the scan page rendering.

---

## 6. Gotchas that cost real time

**SQLite vs MySQL.** Tests run SQLite `:memory:`; production is MySQL. **SQLite
ignores ENUM constraints**, so a new `bookings.status` value passes locally in
silence and fails in production. Widening an enum needs a migration — see
`add_payment_fields_to_bookings_table` and `widen_bookings_status_on_sqlite`.

**A NOT NULL column makes "optional" a 500.** `bookings.guest_email` was created
as a plain `string()`. Making the form field optional without
`make_guest_email_nullable_on_bookings_table` fails at the database with a 1048
integrity violation, not a form error. Check the column before relaxing a form.

**Livewire requires exactly one root element per component.** A long inline
`<script>` in `scan-ticket.blade.php` made libxml — which Livewire uses to count
roots — close the `.fi-page` div early; Filament's three action-modal `<form>`s
became sibling roots and the page threw
`MultipleRootElementsDetectedException` **on every load with `APP_DEBUG=true`**.
Production with debug off never runs the check, so it looked fine live. Fix:
`@push('scripts')`. See `tests/Feature/ScanTicketPageTest.php`.

**`<x-filament-panels::page>` already renders `getHeaderWidgets()`.** Calling
`<x-filament-widgets::widgets>` inside it renders everything twice. That was the
duplicate-widget bug — **not** auto-discovery; `$isDiscovered = false` did
nothing for it.

**A bulk action's `visible()` closure resolves `false` even for a Super Admin.**
Authorise *inside* the action — see `PendingBookingsWidget::runBulk()`. Row
actions can use `visible()` normally.

**`ListRecords::authorizeAccess()` is empty in Filament 3.** A resource's list
page isn't gated by `canViewAny()` alone. In practice restricted roles *do* get
403 (verified by test), but don't assume one `canAccess()` override protects a
direct URL — test it.

**`resources/css/app.css` never loads in the Filament panel.** It serves the
public React site. Panel CSS goes through a render hook
(`PanelsRenderHook::HEAD_END`).

**A Mailable property cannot be named `$subject`** — `Illuminate\Mail\Mailable`
already declares it and a typed promoted property of that name is a fatal
redeclaration. Ours is `$record`.

**`ActivityLogService::log()` types its user as `?App\Models\User`.** The `Login`
listener fired for every guard, so a partner logging in passed a `Referral`,
threw a `TypeError`, and **aborted the login with no visible error on the form**.
The listener now early-returns unless the user is a `User`.

**`/admin/bookings` is `BookingCalendar`, not `ListBookings`.** The list is at
`/admin/bookings/list`.

**`CreateBooking::afterCreate()` is dead code.** It gates on `is_booked`,
`customer_name`, `start_date` — all **Room** columns absent from this form — so
it always returns early. Manual bookings never email on create; mail goes out
when staff hit Confirm.

**`vendor/composer/*` is tracked.** Staging commits `--no-dev` versions that
break local dev (`Laravel\Pail\PailServiceProvider not found`). Workflow:
`composer install` to work, `git checkout -- vendor/` before committing. Now
that `deploy.sh` runs `composer install` server-side this can be untracked:
`git rm -r --cached vendor/`.

**Windows shell:** Git-Bash `/tmp` and Python's `/tmp` resolve differently — a
Bash `cp` into `/tmp` is invisible to a Python script from the same shell. Use
project-relative paths. Also: PHP namespaces in Python heredocs break on `\U`,
`\A`, `\M`, `\F` — use raw strings.

**`min-width: 0` on carousel rails is load-bearing.** They're flex rows of
fixed-width cards inside grid items; with default `min-width: auto` they refuse
to shrink, forcing the document to ~1027px at a 320px viewport. The header is
`position: fixed` so it inherits that width and pushes the hamburger off-screen.

---

## 7. Local development, testing, deployment

```bash
php artisan serve --port=8001     # 8000 is often taken by another project
npm run dev                        # Vite watch
npm run build                      # production assets -> public/build/
php artisan test                   # 37 passing, 145 assertions
```

**Admin login:** `admin@local.test` / `password` at `/admin/login`, created by
`AdminUserSeeder` (idempotent; `ADMIN_EMAIL`/`ADMIN_PASSWORD` override).
⚠️ Without that seeder a fresh `migrate:fresh --seed` produces **zero users** and
the panel is unreachable — `RoleSeeder` only assigns roles to accounts that
already exist.

```bash
php artisan optimize:clear                 # after provider/config edits
php artisan view:clear                     # after Blade/render-hook edits
php artisan db:seed --class=RoleSeeder     # after adding a role
```

⚠️ Don't run `route:cache` locally — it breaks the dev loop.

`phpunit.xml` sets `CACHE_STORE=array`, so the rate limiter resets per test.

**Browser testing:** `playwright` is a devDependency. Put throwaway scripts in
the project root so `import { chromium } from 'playwright'` resolves, and delete
them after. Use `waitUntil: 'domcontentloaded'` — `networkidle` never settles
because the hero video streams continuously. Filament login fields are
`input[id="data.email"]` / `input[id="data.password"]`, and **assert on page
content, not the URL** — Livewire swaps in the dashboard before updating the
path, which reads as a false "login rejected".

**Deployment:** `deploy.sh` is run by GitHub Actions on push to `main` —
`composer install --no-dev`, `migrate --force`, `RoleSeeder`, `npm run build`,
storage symlink, cache rebuild. ⚠️ **It was referenced by CI but absent for a
long time**, so migrations never ran on the server — which explained three
separate "500 errors" as a single root cause. After a deploy check
`php artisan migrate:status` and `tail -n 100 storage/logs/laravel.log`.

---

## 8. Planned: Hospi Sales mobile app

White-label, multi-tenant hospitality ops app (Android + iOS) built around
**meal-order delay accountability** — every order gets a server-stamped
countdown and overdue orders escalate to the manager automatically.

Specs, all complete:
- [../HOSPI_SALES_BRIEF.md](../HOSPI_SALES_BRIEF.md) — codebase map, order/delay
  data model, Expo + React Native + Sanctum architecture, build phases
- `agent-3-stitch.md` — UI design prompt
- `agent-2-build.md` — build prompt

Two facts that shape the work: **Sanctum is already installed**
(`personal_access_tokens` migration + an `auth:sanctum` route), and **no existing
table has a `tenant_id`**.

### Verified state of the app, as of this writing

`agent-2-build.md` was appended to by hand and states that an Expo project is
already scaffolded in a **sibling folder** with `app.config.ts`, `eas.json`,
`src/config.ts`, and a working `/api/health` screen on a real device.

Two things I could not confirm from this repository, flagged so nobody builds on
a wrong assumption:

1. **There is no `/api/health` route in this codebase.** `routes/api.php`
   contains only `/rooms`, `/services`, `/contact`, `/bookings`,
   `/service-bookings` and the Sanctum `/user`. A `grep` for "health" across
   `routes/` returns nothing. If the health screen is green, it is hitting
   something other than a `/api/health` endpoint here — or the endpoint exists
   only on the server. **Resolve this before Phase 0.**
2. **No Expo project is present alongside this repo** on this machine
   (`Documents/` has no `hospi`-like sibling). It may live elsewhere or on
   another machine.

**No backend work for the app has landed in this repository yet** — no `tenants`
table, no `meals`/`orders` migrations, no new API controllers.

---

## 9. Open items and risks

**Security**

- ⚠️ **The production DB password and mail credentials were previously committed
  in `.env.example`.** They were scrubbed and replaced with empty placeholders,
  and the repo is private — but **rotating those credentials was advised and is
  not confirmed done.** Highest-value item on this list.
- `default.php` and `public/default.php` (195 lines each, arrived from staging)
  are unreviewed and may be stray web entry points.

**Correctness**

- `UserPolicy` role-name mismatch (§3).
- Scanner camera flow needs a real-device check now that scan-ticket's scripts
  moved to the layout stack. Tests prove the page renders and assets are present,
  not that the camera starts.

**Performance / housekeeping**

- `.git` carries ~1.27 GB of large binaries from earlier commits (two over
  GitHub's 50 MB warning). Shrinking needs a history rewrite.
- Hero video is 36 MB. Lazy-loaded (`preload="none"` + IntersectionObserver) so
  it no longer costs an initial page load, but re-encoding to H.265/VP8 (target
  8–10 MB) is still worth doing.
- `vendor/` tracking (§6) — can now be untracked.
- `/gallery` has a `grid-cols-3` element overflowing both edges (`left=-105`,
  `right=425` at 320px). Clipped, so no page-level bleed, but it looks wrong.
  Pre-existing, in the `animated-gallery` component.

---

## 10. Design system (geothermal-coast)

| Token | Value | Use |
|---|---|---|
| `--ib-basalt` | `#0b0f0e` | Dark ground |
| `--ib-glacier` | `#f4f7f6` | Light text |
| `--ib-teal` | `#6fb8bc` | Focus rings, active |
| `--ib-ember` | `#e2924a` | Primary CTAs |

Brand navy for admin and email is **`#002F61`**.

Type: **Fraunces** (serif headings) + **Hanken Grotesk** (UI). Motion:
`cubic-bezier(0.16, 1, 0.3, 1)`, 0.8–1.6s, Framer Motion `whileInView`,
`staggerChildren: 0.08`. Everything respects `prefers-reduced-motion`.

**Aurora** (`components/AuroraCanvas.jsx`) is a real Three.js fragment shader —
5-octave fbm noise, additive blending — wrapped by `AuroraLayer.jsx` so Three.js
is code-split out of the main bundle. Pauses off-screen, caps DPR at 1.5, renders
one static frame under reduced motion, falls back to a CSS gradient without
WebGL. The homepage `ScrollExpandMedia` hero is deliberately untouched.

---

## 11. Conventions

- Comments explain **why**, not what. Match the surrounding density.
- Business rules live in `app/Services/*`, not in controllers or Filament actions.
- New behaviour gets a test that **fails without the change** — verify that, don't
  assume it.
- Money is integer kobo. Format only at the UI edge.
- Money, rates and names are **snapshotted** onto transactional rows.
- Commits end with: `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`

**Developer:** Manlikenonso1k · victorynonso9@gmail.com

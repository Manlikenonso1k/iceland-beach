# Hospi Bookings — Progress

> Resume file. Read this first and continue from "Next up". Do not start over.

## Repos

| Role | Full path | Identified by |
|---|---|---|
| **WEB** (`iceland-beach`) — Laravel backend + website + API | `C:\Users\Tenstrings Music Ins\Documents\Iceland` | `artisan`, `composer.json` |
| **APP** (`Hospit-app`) — Expo / React Native | `C:\Users\Tenstrings Music Ins\Documents\iceland app` | `app.config.ts`, `eas.json` |

Backend and API changes go in WEB. App screens go in APP. **Commit and push each repo separately.** This file lives in the WEB root only.

---

## Current phase

**Phase 2 — Backend endpoints DONE and pushed. App screens next.**

---

## Plan (from reading both repos)

### How the web calendar gets bookings today

Two parallel, deliberately separate systems:

| | Rooms | Spaces |
|---|---|---|
| Ledger table | `bookings` | `space_bookings` |
| Model | `App\Models\Booking` | `App\Models\SpaceBooking` |
| Catalogue table | `room` (singular, legacy) | `services` (via `ServiceBooking`) |
| Blocking rule | `Booking::BLOCKING_STATUSES = ['confirmed']` + `scopeBlocking()` | identical |
| Workflow service | `BookingWorkflowService` | `SpaceBookingWorkflowService` |
| Calendar widget | `BookingCalendarWidget` | `SpaceBookingCalendarWidget` |
| Report service | `BookingReportService` | `SpaceBookingReportService` |
| Admin resource | `BookingResource` (index = `BookingCalendar`) | `SpaceBookingResource` (index = `SpaceBookingCalendar`) |

Both calendar widgets query `whereIn('status', ['pending','confirmed'])` with a date window, colour by catalogue id, and render all-day events. **The app's calendar must use the same query shape and the same status set, or app and web will disagree for the same dates.**

Neither ledger has a price column. Amounts are derived:
- rooms: `room.room_price × nights` (`BookingReportService::amount()`)
- spaces: `services.service_price × nights` (`SpaceBookingReportService::amount()`)

### Existing API (all additive, Sanctum)

Public: `/rooms`, `/services`, `/contact`, `/bookings`, `/service-bookings`.
Authenticated: `/health`, `/auth/login`, `/auth/logout`, `/me`, `/tenant`, `/meals*`, `/orders*`, `/dashboard/revenue`, `/staff`, `/tables`, `/transfers*`, `/shifts*`, `/notifications*`, `/device-tokens`.

**Nothing exposes room or space bookings to the app.** That is the gap.

Role gate: `app/Http/Middleware/EnsureRole.php`, aliased `hospi.role` in `bootstrap/app.php`. Tests role NAMES (this codebase defines no permissions).

### Endpoints to add (read-only)

| Endpoint | Roles | Notes |
|---|---|---|
| `GET /api/bookings/calendar` | CEO, Manager, Space Host, Host | `type=rooms\|spaces\|all`, `from`/`to` required, paginated |
| `GET /api/bookings/rooms/{booking}` | CEO, Manager, Space Host, Host | single room booking detail |
| `GET /api/bookings/spaces/{spaceBooking}` | CEO, Manager, Space Host, Host | single space booking detail |
| `GET /api/inventory/rooms` | CEO, Manager, Space Host, Host | images (full URLs), price, features |
| `GET /api/inventory/spaces` | CEO, Manager, Space Host, Host | images, price/`price_label`, capacity |

### Role rules (mirroring the website exactly)

- **CEO, Manager** — VIEW all room and space bookings + catalogue. View-only: the website gives them no app-reachable write action here.
- **Space Host / Host** — VIEW both. Space *writes* exist on the website (`RestrictsSpaceBookingWrites`) but are **not** exposed in this phase; the brief says view-only unless the web already allows it, and no API write path exists yet. Room writes are forbidden on the web (`RestrictsRoomBookingWrites`) and stay forbidden here.
- **Chef, Waiter, Scanner, Front Desk, and any other role** — 403.
- **No token** — 401.

### Decisions made (noted, not asked)

1. **One calendar endpoint, not two.** `type` selects rooms/spaces/all so the app's Rooms|Spaces tabs are one hook with a parameter. Halves the client code.
2. **`from`/`to` are required**, max window 92 days, hard `per_page` cap of 200. The brief says never return everything at once.
3. **Amounts computed server-side** by reusing the two report services, so app, web calendar and PDF cannot disagree.
4. **Receipts are signed URLs only** (`space.receipt`, already permanent-signed). Room bookings reuse `booking.receipt`. No guessable paths.
5. **No new migration.** Everything needed already exists.
6. **Guest contact is role-gated**, not returned to anyone who can see a date.

---

## Done

- [x] Located and identified both repos; paths recorded above.
- [x] PLANNER: read both repos, wrote the plan above.
- [x] CODER (backend): 5 read-only endpoints, role gate, pagination, capped window.
- [x] QA (backend): 18 feature tests covering the full role matrix. **WEB suite: 200 passed, 890 assertions.**

### Endpoints live

| Endpoint | Roles | Enforcement |
|---|---|---|
| `GET /api/bookings/calendar` | CEO, Manager, Super Admin, Admin, Space Host, Host | `hospi.role` + `auth:sanctum` + `throttle:60,1` |
| `GET /api/bookings/rooms/{booking}` | same | same |
| `GET /api/bookings/spaces/{spaceBooking}` | same | same |
| `GET /api/inventory/rooms` | same | same |
| `GET /api/inventory/spaces` | same | same |

Verified by test: Chef, Waiter, Scanner, Front Desk, Referral and a roleless
account all get **403**; no token gets **401**; POST/PATCH/DELETE on these paths
are **not routable** (404/405), so app-side view-only holds by construction.

## Next up

- [ ] CODER (app): Bookings section (calendar, Rooms|Spaces tabs, detail sheet), Rooms & Spaces screen.
- [ ] CYBERSECURITY: audit all `/api` routes, fix + test each finding.

## Files changed

### WEB
- `PROGRESS.md` (new)
- `app/Http/Controllers/Api/BookingViewController.php` (new)
- `app/Http/Controllers/Api/InventoryController.php` (new)
- `routes/api.php` (5 routes added inside the existing sanctum group)
- `tests/Feature/BookingVisibilityApiTest.php` (new, 18 tests)

## Open issues

- `google-services.json` is **tracked** in the APP repo. Firebase Android config is designed to ship inside the APK and its `api_key` is package-restricted, so this is informational rather than a leak — but it is listed for the security pass to rule on. The real secret, the `hospi-sales-firebase-adminsdk-*.json` service-account private key, is correctly untracked.
- APP repo has an untracked `graphify-out/` (build artifact) that should be gitignored there as it already is in WEB.

## Last commit

WEB: pending (this file) · APP: `eb47387`

## Security audit — 2026-10-21

Scope: every route in `php artisan route:list --path=api` (47), plus the four receipt/invoice
links, `config/cors.php`, `config/sanctum.php`, `bootstrap/app.php`, `$fillable` on the six named
models, and `git ls-files` + full history in both repos. Every "Fixed" row, and every "Accepted"
row that asserts a control, is backed by a test in `tests/Feature/ApiSecurityAuditTest.php`
(17 tests). Suite: **200 → 217 passing**, no regressions.

| Sev | Finding | File:line | Abuse path (concrete) | Status | Test |
|---|---|---|---|---|---|
| **High** | Cross-tenant read of the resort's booking ledger. `bookings`/`space_bookings` predate Hospi multi-tenancy and have no `tenant_id`, so `BelongsToTenant` cannot scope them. The role gate asks "which role?", never "whose business?" | `routes/api.php:142-157`, `app/Http/Controllers/Api/BookingViewController.php:125-166` | Sign in as the Manager (or CEO / Space Host) of ANY other business on Hospi Sales, then `GET /api/bookings/rooms/1`, `/2`, `/3`… Verified 200 returning `guest_name`, `guest_email`, `guest_phone` and a **valid signed** `/booking/receipt/{id}` URL for a resort guest. `/api/bookings/calendar?from=…&to=…` hands back up to 200 of them per page. | **Fixed** — new `hospi.resort` middleware (`app/Http/Middleware/EnsureResortTenant.php`) on the three `/api/bookings/*` routes. Allows a tenantless legacy admin and the tenant whose slug is `config('resort.tenant_slug')`; **fails open if no tenant matches that slug**, so a config mismatch cannot lock the resort out of its own calendar. `RESORT_TENANT_SLUG` added to `.env.example`. **Action for the lead: confirm the production `tenants.slug` row for Iceland is `iceland-beach`, or set `RESORT_TENANT_SLUG` to the real value.** | `test_another_tenants_manager_cannot_read_resort_booking_pii`, `test_another_tenants_space_host_cannot_read_resort_bookings`, `test_the_resort_tenants_manager_still_sees_bookings`, `test_a_tenantless_legacy_admin_still_sees_bookings`, `test_the_gate_fails_open_when_the_resort_tenant_does_not_exist` |
| **Medium** | Any tokenless request to a protected `/api` route **without** `Accept: application/json` returned **500**, not 401. Laravel's default `redirectGuestsTo(fn () => route('login'))` is evaluated inside `Authenticate::unauthenticated()`, and this app has no route named `login` (Filament registers `filament.admin.auth.login`), so the `AuthenticationException` became a `RouteNotFoundException`. | `bootstrap/app.php` (previously no `redirectGuestsTo`, inheriting `vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:278`) | `curl https://icelandbeach.com/api/me` with no headers. With `APP_DEBUG=true` that 500 is a full HTML stack trace — framework paths, code frames, and for a DB-touching route the SQL — served to an anonymous caller. Reproduced locally: 500 with `"exception": "RouteNotFoundException"` and 14 trace frames. | **Fixed** — `redirectGuestsTo()` now returns `null` for `api/*` / JSON callers and the Filament admin login for the website; `shouldRenderJsonWhen()` renders every `api/*` failure as JSON. `/api/me` with no headers is now a JSON 401. | `test_an_unauthenticated_api_request_is_401_json_even_without_an_accept_header`, `test_every_api_route_is_either_public_by_design_or_401`, `test_a_server_error_on_an_api_route_leaks_nothing_when_debug_is_off` |
| **Medium** | `/invoices/{id}/view` is unauthenticated and unsigned; sequential integer id; renders customer name, address and phone. | `routes/web.php:11-14` | `GET /invoices/1/view`, `/2/view`, … walks every invoice ever raised. | **Needs decision** — already recorded in `ROLE_ACCESS_AUDIT.md` and awaiting the owner. **Re-stated, deliberately NOT changed**: signing it breaks saved and printed links. Recommended path: keep the unsigned URL working for existing ids and mint signed links for new invoices, or put it behind the panel session with a signed fallback. | none added (no change made) |
| **Medium** | `config/cors.php` does not exist, so `HandleCors` uses the framework default: `allowed_origins => ['*']`, `allowed_headers => ['*']`, `paths => ['api/*','sanctum/csrf-cookie']`. Verified by dumping `config('cors')`. | absent; default lives at `vendor/laravel/framework/config/cors.php` | Any website can read `/api/rooms`, `/api/services`, `/api/health` from a visitor's browser. Impact is limited: `supports_credentials` is `false` and auth is a Bearer header, so no cookie is attached cross-origin and a third-party page cannot borrow a staff session. A hardening gap, not an account-takeover path. | **Needs decision** — changing allowed origins is outward-facing (Expo web dev client, any embedded widget), so not changed. Recommended `config/cors.php`: `'paths' => ['api/*']`, `'allowed_methods' => ['GET','POST','PATCH','DELETE','OPTIONS']`, `'allowed_origins' => ['https://icelandbeach.com','https://www.icelandbeach.com','https://staging.icelandbeach.com','http://localhost:8081','http://localhost:19006']`, `'allowed_origins_patterns' => ['#^http://192\.168\.\d+\.\d+:(8081|19006)$#']`, `'allowed_headers' => ['Accept','Authorization','Content-Type','X-Requested-With']`, `'supports_credentials' => false`. Native iOS/Android enforce no CORS, so only Expo **web** needs the localhost entries. | none added (no change made) |
| **Medium** | Sanctum tokens never expire — `'expiration' => null`. A staff token minted once is valid forever; `POST /api/auth/logout` is the only thing that ever kills it. | `config/sanctum.php:53` | A lost or stolen phone keeps full API access for its role indefinitely, and there is no in-app "sign out other devices". Confirmed `config('sanctum.expiration') === null`. | **Needs decision** — setting an expiry logs every device out at once, which is outward-facing. Recommended: `'expiration' => 60 * 24 * 30` plus `sanctum:prune-expired --hours=24` on the scheduler, shipped together with app-side silent re-login, and a Filament action to revoke one user's tokens. | `test_logout_actually_revokes_the_token` covers revocation (the half that does work) |
| **Low** | `POST /api/auth/logout` was the only write route in the API with no rate limiter. The `api` middleware group carries no default `throttle`, so an unlisted route gets none. | `routes/api.php:46-47` | Unbounded POSTs to an authenticated endpoint that performs a DB delete each time — a cheap amplification lever, not a data leak. | **Fixed** — `throttle:30,1`, matching the other write routes. | `test_every_api_write_route_carries_a_rate_limit` (asserts the property over the whole route table, so a new unthrottled write fails the suite) |
| **Low** | 12 authenticated **read** routes have no rate limiter: `/api/me`, `/api/user`, `/api/meals`, `/api/orders`, `/api/orders/board`, `/api/orders/{order}/transfer-targets`, `/api/dashboard/revenue`, `/api/staff`, `/api/tables`, `/api/transfers`, `/api/shifts/current`, `/api/notifications/unread-count`. | `routes/api.php` | A valid token can poll `/api/dashboard/revenue` — which loads every order in the period and aggregates in PHP — in a tight loop. Requires a valid token, so the blast radius is one compromised staff account. | **Accepted-no-action** — the app polls the order board and the unread count on a timer; adding a limiter risks 429-ing normal use. Recommended instead: `throttle:120,1` on the group once the app's real poll intervals are measured. | none added (no change made) |
| **Low** | `POST /api/orders` accepts `table_id` as a bare integer with no existence or tenant check; `OrderService::place()` writes it straight through. | `app/Http/Controllers/Api/OrderController.php:25`, `app/Services/OrderService.php:149` | A waitress posts `table_id: 99999`, or another tenant's table id, and the order is stored against it. `OrderResource` only echoes the integer back and never resolves a name, so nothing belonging to another tenant is disclosed — a data-integrity gap, not a leak. | **Accepted-no-action** — fixing it means `Rule::exists` against the tenant-scoped `venue_tables`, which could start rejecting orders the app currently sends (it may post a stale id after a table is retired). Recommended as a small, separately tested change. | none added (no change made) |
| **Info** | Tenant isolation on every id-taking Hospi route is **correct**. `BelongsToTenant`'s global scope reaches route-model binding only because `Authenticate` runs before `SubstituteBindings` in the framework's middleware priority — invisible in this repo and silently breakable. | `app/Models/Concerns/BelongsToTenant.php:26-32` | Attempted as another tenant's Manager / Chef / Waiter: `PATCH /api/meals/{id}`, `POST /api/meals/{id}/image`, `PATCH /api/tables/{id}`, `POST /api/orders/{id}/accept|nudge|expedite|reassign`, `GET` and `POST /api/orders/{id}/transfer*`, `POST /api/transfers/{id}/accept|decline`. All **404**, with the target records verified unchanged. Ordering another tenant's meal is a 422; reassigning to another tenant's chef is a 422. | **Accepted-no-action** (verified correct; now pinned) | `test_no_hospi_route_resolves_another_tenants_record_by_id`, `test_an_order_cannot_be_placed_against_another_tenants_meal` |
| **Info** | The two public catalogue endpoints are **clean** — and only because they name their columns. `room` carries `customer_name`, `email`, `start_date`, `end_date`; `services` carries `customers_name`, `customers_email`, `signin`, `signout`; both sets are in the models' `$fillable`. | `app/Http/Controllers/Api/CatalogController.php:26-37`, `app/Http/Controllers/Api/BookingController.php:35` | Verified with a room and a space holding a real name and email: neither value appears in `GET /api/rooms` or `GET /api/services`, and no PII key is present. The latent abuse path is a future edit — widen either `select()` to `*`, or drop it, and a named guest's email is published unauthenticated to the open internet. `CatalogController`'s docblock already warns against *narrowing* that select; nothing warned against widening it. | **Accepted-no-action** (verified clean; now pinned) | `test_public_rooms_endpoint_never_returns_guest_pii`, `test_public_services_endpoint_never_returns_guest_pii` |
| **Info** | Mass assignment is not reachable. `$fillable` is wide — `Order` lists `tenant_id`/`status`/`total_amount`/`completed_at`, `SpaceBooking` lists `payment_status`/`source`/`receipt_path`, `Meal` lists `tenant_id`/`image_path`, `User` lists `tenant_id` — but `grep` finds **no** `$request->all()`, `->fill($request…)` or `::create($request…)` anywhere in `app/`; every write builds an explicit array out of `$request->validate()`. | `app/Models/Order.php:32`, `app/Models/SpaceBooking.php:43`, `app/Models/Meal.php:16`, `app/Models/User.php:26`, `app/Models/Booking.php:21`, `app/Models/Referral.php:17` | Attempted smuggling `tenant_id`, `sku`, `image_path` into `POST /api/meals`; `status`, `total_amount`, `tenant_id`, `placed_by_user_id`, `completed_at` into `POST /api/orders`; `tenant_id` into `POST /api/tables`; `status`, `payment_status`, `payment_reference` into public `POST /api/bookings`; `status`, `payment_status`, `source`, `receipt_path` into public `POST /api/service-bookings`. Every field dropped; the stored rows carry the server's own values. `Referral` is not reachable from any `/api` route at all. | **Accepted-no-action** (verified unreachable; now pinned) | `test_api_writes_cannot_smuggle_privileged_fields`, `test_a_public_booking_cannot_set_its_own_payment_state`, `test_a_public_space_booking_cannot_set_its_own_receipt_or_source` |
| **Info** | Receipt links are signed. `booking.receipt`, `booking.success` and `space.receipt` all carry `->middleware('signed')`. | `routes/web.php:86-108` | A sequential `/booking/receipt/5` with no signature is refused. | **Accepted-no-action** (already correct) | pre-existing `BookingReceiptTest::test_unsigned_and_tampered_links_are_refused`, `SpaceBookingReceiptTest::test_an_unsigned_or_tampered_link_is_rejected` |
| **Info** | Auth and role gates: all 47 `/api` routes checked one by one. Six are public by design (`/api/rooms`, `/api/services`, `/api/health`, `POST /api/contact`, `POST /api/bookings`, `POST /api/service-bookings`) plus `POST /api/auth/login`; every other route is 401 without a token. The 10 authenticated routes with no `hospi.role` gate (`/api/me`, `/api/user`, `/api/meals`, `/api/orders`, `/api/tables`, notifications, device-tokens and siblings) are each own-data or catalogue reads — `OrderController::index` scopes a non-privileged role to `placed_by_user_id = me` and a Chef to their own department, server-side. | `routes/api.php`, `app/Http/Controllers/Api/OrderController.php:51-74` | n/a — no route was found reachable that should not be. | **Accepted-no-action** | `test_every_api_route_is_either_public_by_design_or_401` |
| **Info** | `APP_DEBUG` on a 500: with debug **off**, an `/api` exception returns exactly `{"message":"Server Error"}` — no message, file, line or trace. With debug **on** it returns the message, file, line and full trace. Local `.env` has `APP_DEBUG=true`; `.env.example` correctly has `APP_DEBUG=false`. | `.env:4`, `.env.example:4` | A production box running with `APP_DEBUG=true` leaks framework paths and SQL on any 500. **Not verifiable from here** — nobody should read the live `.env` for this audit. | **Needs decision** — the lead must confirm `APP_DEBUG=false` on icelandbeach.com. The behaviour itself is correct and is now pinned. | `test_a_server_error_on_an_api_route_leaks_nothing_when_debug_is_off` |
| **Info** | Secrets: clean in both repos. WEB tracks only `.env.example` (every value blank; its one note records that the mail password was once there by mistake and no longer is) and `prompt/TOOLS/admin_schema.sql` (DDL plus a single `INSERT INTO tables (table_name, status)` — no credentials, no personal data). APP tracks `google-services.json` and `eas.json`. No `.env`, `*.pem`, `*.key` or keystore is tracked in either repo or anywhere in either history. | `.env.example`, `prompt/TOOLS/admin_schema.sql`, `../iceland app/google-services.json` | `google-services.json` is judged **not a leak**: Firebase's Android `api_key` is a client identifier, is package-name + SHA-1 restricted, and ships readable inside every APK, so publishing it gives an attacker nothing the store build does not. `hospi-sales-firebase-adminsdk-fbsvc-f63382274b.json` (a real `-----BEGIN PRIVATE KEY-----`) **exists on disk and is correctly ignored** by `.gitignore:25`; `credentials.json` by `:27`. Verified never committed: `git log --all -- <file>` is empty and `git grep "BEGIN PRIVATE KEY"` across all 16 app commits finds nothing. The APP stores its bearer token in `expo-secure-store`, not AsyncStorage. | **Accepted-no-action** | none (git state, not runtime) |
| **Info** | Token lifecycle: `POST /api/auth/logout` genuinely revokes. It deletes `currentAccessToken()`, the same bearer is 401 on the next request, and zero rows are left in `personal_access_tokens`. | `app/Http/Controllers/Api/AuthController.php:52-57` | Verified: login → 200 on `/api/me` → logout → 401 on `/api/me`. | **Accepted-no-action** (correct; now pinned) | `test_logout_actually_revokes_the_token` |
| **Info** | `POST /api/auth/login` is throttled `10,1` — per IP only, with no per-account counter and no lockout. | `routes/api.php:42` | 10 guesses per minute from one IP against one account (about 14k/day), or 10/minute against *each* account from a rotating-IP pool. `StaffController` accepts `min:6` passwords, which makes the account side weaker than the limiter. | **Needs decision** — a per-identifier limiter can lock a real staff member out mid-shift, so not changed. Recommended: a named limiter keyed on both `sha1(ip)` and `sha1(login)` (`RateLimiter::for('login', …)`, e.g. 5/min per login alongside the existing 10/min per IP), and raise the `StaffController` password floor above 6. | none added (no change made) |

### Files changed by this audit (WEB only, staged-but-uncommitted)

- `app/Http/Middleware/EnsureResortTenant.php` — **new**, the `hospi.resort` gate.
- `bootstrap/app.php` — registered the `hospi.resort` alias; added `redirectGuestsTo()` and `shouldRenderJsonWhen()`.
- `routes/api.php` — `hospi.resort` on the three `/api/bookings/*` routes; `throttle:30,1` on `/api/auth/logout`.
- `config/resort.php` — added `tenant_slug` (env `RESORT_TENANT_SLUG`, default `iceland-beach`).
- `.env.example` — documented `RESORT_TENANT_SLUG`.
- `tests/Feature/ApiSecurityAuditTest.php` — **new**, 17 tests / 141 assertions.
- `PROGRESS.md` — this section.

Nothing in the APP repo was changed.

## Security audit — remaining three findings closed (2026-10-21)

All three items previously marked "Needs decision" are now fixed. Nothing is
outstanding from the audit.

| Sev | Finding | Fix | Test |
|---|---|---|---|
| Medium | `/invoices/{id}/view` unauthenticated + unsigned on a sequential id, leaking customer name, address, phone, total | `signed` middleware on the route; both Filament generators now mint `URL::signedRoute` | `SecurityHardeningTest` ×5 |
| Medium | CORS allowed every origin (no `config/cors.php`, so the framework `*` default applied) | Created `config/cors.php` with the real origins; dev origins excluded in production; `supports_credentials` stays false | `test_cors_is_no_longer_a_wildcard`, `test_development_origins_are_excluded_in_production` |
| Medium | Sanctum tokens never expired — a lost phone kept role-level API access forever | `expiration => 60*24*30` (30 days) + `sanctum:prune-expired --hours=168` scheduled daily | `test_sanctum_tokens_now_expire`, `test_a_token_older_than_the_expiry_is_refused`, `test_a_token_inside_the_window_still_works`, `test_expired_tokens_are_prunable` |

### Decided, with the trade-off taken deliberately

**Invoices are signed for ALL ids, not grandfathered.** Grandfathering existing
ids would have left every current customer's details exposed, which is the
actual leak — it only protected future invoices. The two generators are both
inside the Filament admin, so a "saved link" is one a staff member copied out
of the admin, and recovery is reopening the invoice. Signatures carry no
expiry, so a freshly copied link keeps working indefinitely.

**Token expiry logs every device out once, on deploy.** Accepted: the app
clears its stored token on any 401 and falls back to Welcome, so staff see a
re-login rather than a broken session.

### Production verified (owner ran these)

- `tenants.slug` matches `config('resort.tenant_slug')` → id `1`. **The
  `hospi.resort` gate is ACTIVE**, so the High cross-tenant finding is genuinely
  closed in production, not just in code.
- `config('app.debug')` is `bool(false)`.
- No `EnsureResortTenant` fail-open warnings in `storage/logs/laravel.log`.

**WEB suite: 228 passed, 1061 assertions.**

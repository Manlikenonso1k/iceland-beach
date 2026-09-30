# Iceland Beach Resort — Working Context

**Last updated:** 2026-09-30
**Branch:** `main` · **Repo:** https://github.com/Manlikenonso1k/iceland-beach.git
**Test suite:** 37 passing, 145 assertions (`php artisan test`)

Working notes for picking this project back up. The user-facing changelog lives
in [README.md](README.md); Filament UI mechanics in
[FILAMENT_UI.md](FILAMENT_UI.md); ticketing in [RESULT.md](RESULT.md); the
payment integration guide for other projects in
[PAYMENTS_INTEGRATION.md](PAYMENTS_INTEGRATION.md); the planned mobile app in
[HOSPI_SALES_BRIEF.md](HOSPI_SALES_BRIEF.md).

This file is for the things that are **not obvious from the code** and cost time
to rediscover.

---

## Read this first: two booking systems

The single most confusing thing in this codebase.

| | `room` table | `bookings` table |
|---|---|---|
| Columns | `is_booked` enum, `start_date`, `end_date`, `customer_name`, `email` | `room_id`, `check_in`, `check_out`, `status`, `guest_*` |
| `is_booked` values | `no` · `booked` · `rejected` · `expired` | — |
| `status` values | — | `pending` · `confirmed` · `rejected` · `pending_payment` · `payment_failed` |

Note the table is **`room`**, singular and legacy. Validation reads `exists:room,id`.

**`bookings` is canonical** (decided 2026-08-29). It previously held only a
`room_type` *string* with no `room_id` and no `check_out`, so it could not be
joined to a room or tested for overlap — which is why availability had been read
off `room.is_booked` instead.

The rule lives in exactly one place, `Booking::scopeBlocking()`:

> **Only a `confirmed` booking holds a room.** `pending` and `rejected` never do.

Flow:

1. Public booking → `ReservationService::requestBooking()` creates a **pending**
   `Booking` and deliberately leaves `room.is_booked = 'no'`, staging guest
   details + dates on the room. That combination (`is_booked='no'` **with**
   dates) is what `PendingBookingsWidget` treats as awaiting review — it was not
   invented for this change.
2. Staff **Confirm** → `status='confirmed'` **and** the room flips to
   `is_booked='booked'` so the calendar widget stays in step.
3. Staff **Reject** → `status='rejected'` and the room is released to `'no'`.

`room.is_booked` is a *denormalised mirror* for the Room-based calendar and
widgets, not the source of truth. If they disagree, `bookings` wins.

⚠️ **`rejected` and `expired` are historical states, not live holds.** The old
front-end treated any value other than `'no'` as unavailable, so rejected and
expired bookings blocked rooms. Don't reintroduce `is_booked !== 'no'`.

**All status transitions go through `BookingWorkflowService`** —
`confirmBooking()`, `rejectBooking()`, `markBookingPaid()`. The Filament
resource action, `PendingBookingsWidget`, `ConfirmedBookingsWidget` and both
payment entry points all call it, so status, room mirror, activity log and guest
email cannot drift apart. They used to carry separate copies and had already
diverged — one emailed the guest, the other didn't.

---

## Two panels, two guards

`/admin` and `/referral` are separate Filament panels with separate auth guards.

| | `/admin` | `/referral` |
|---|---|---|
| Provider | `AdminPanelProvider` | `ReferralPanelProvider` |
| Guard | `web` (`App\Models\User`) | `referral` (`App\Models\Referral`) |
| Resources | `App\Filament\Resources\*` | `App\Filament\Referral\*` only |

`App\Models\Referral` extends `Authenticatable` and implements `FilamentUser`.
`config/auth.php` carries the `referral` guard and `referrals` provider.

⚠️ **This replaced the old approach.** Partners used to be `User`s with a
`Referral` role, scoped inside `ReferralResource::getEloquentQuery()`. That is
gone — `ReferralResource` is now staff-only (`canAccess()` checks
`auth()->guard('web')->check()`) and manages partner records. `'Referral'` was
removed from `User::canAccessPanel()`.

⚠️ **The trap that cost the most time here:** `ActivityLogService::log()` types
its user as `?App\Models\User`. The `Login` event listener in
`AppServiceProvider` fired for *every* guard, so a partner logging in passed a
`Referral` and threw a `TypeError` that aborted the login **with no visible
error on the form**. The listener now early-returns unless
`$event->user instanceof App\Models\User`.

---

## Roles and access control

`spatie/laravel-permission` 7.1. `RoleSeeder` creates: `Super Admin`, `Admin`,
`Manager`, `Front Desk`, `Waiter`, `Scanner`, `Referral`.

**No permissions are defined — only role names.** Every gate tests role names
directly. Don't reach for `can()`/permissions; they aren't wired up.

- `User::canAccessPanel()` — panel gate, plus an email bypass for
  `victorynonso9@gmail.com`. A user can authenticate successfully and still be
  refused the panel, so **any new admin account needs a role assigned**.
- `app/Filament/Concerns/RestrictsFrontDeskAndScanner.php` — Front Desk and
  Scanner lose Administration and Events. Overrides `canViewAny` / `canCreate` /
  `canEdit` / `canDelete` / `canDeleteAny`.
- ⚠️ **`/admin/scan-ticket` is the deliberate exception.** It lives in the
  Events group, so restricting Events originally locked the **Scanner** role out
  of the only page it exists for. `ScanTicket::canAccess()` now excludes Front
  Desk only. It is a *denylist, not an allowlist*, so the roleless owner account
  admitted by email is not locked out. Covered by
  `tests/Feature/RestrictedAdminSectionsTest.php`.
- ⚠️ **`app/Policies/UserPolicy.php` tests `hasRole('super_admin')`** but the
  seeder creates `Super Admin`. Snake_case never matches. The policy is
  effectively dead for `UserResource` because `canAccess()` is overridden. Use
  the seeded names; don't copy the typo.
- **Filament note:** `ListRecords::authorizeAccess()` is empty in Filament 3, so
  a resource's list page is not gated by `canViewAny()` alone. In practice
  `/admin/users` etc. *do* return 403 for a restricted role — verified by test —
  but don't assume a single `canAccess()` override protects a direct URL. Test it.

---

## Payments

Two gateways, three flows. Full portable writeup in
[PAYMENTS_INTEGRATION.md](PAYMENTS_INTEGRATION.md).

| Flow | Entry | Gateway |
|---|---|---|
| Tickets | `PaymentController` (inline popup → `/verify-payment`) | Paystack + TGI Pay |
| Room bookings | `BookingPaymentController` (redirect → callback) | Paystack |
| Vendor spots | `VendorPaymentController` | TGI Pay |

`app/Services/PaystackService.php` — `isConfigured()`, `initialize()`,
`verify()`, `verifyWebhookSignature()` (HMAC-SHA512 via `hash_equals`),
`generateReference('BOOK')`. **Amounts are integer kobo.**

⚠️ **Paystack allows ONE webhook URL per mode.** A single handler,
`PaystackWebhookController::handle()`, dispatches by reference prefix
(`BOOK-` → booking, otherwise ticket). Three route aliases point at it so
whichever URL is already registered in the dashboard keeps working:
`/webhooks/paystack`, `/webhooks/paystack/booking`, `/webhooks/paystack/tickets`.

⚠️ **The callback and the webhook routinely BOTH fire** for one transaction.
`BookingWorkflowService::markBookingPaid()` is idempotent — it early-returns on
`payment_status === 'paid'` — so the guest is never emailed twice. Always enter
through it.

**Paying auto-confirms.** `markBookingPaid()` calls `confirmBooking()`, so a
paid booking skips the staff review queue and is indistinguishable from a
hand-confirmed one. Toggled by `booking_payment_required` in `settings`
(`/admin/manage-settings`).

⚠️ **Receipt routes are `signed` middleware on purpose.** A booking id is a
small sequential integer; an unsigned `/booking/receipt/5` would let anyone walk
the range and harvest every guest's name, email and phone.

---

## Bookings: email is optional on the admin form

Staff take bookings at the counter and by phone, where no email exists.

- **Admin form** (`BookingResource`): `guest_phone` **required**, `guest_email`
  optional. Phone is the primary identifier and comes first.
- **Public form** (`Api\BookingController`): email still **required** — Paystack
  needs a receipt address. Don't "fix" this to match the admin form.
- ⚠️ `bookings.guest_email` was created as a plain `string()`, i.e. **NOT NULL**.
  Making the form field optional without the migration
  (`make_guest_email_nullable_on_bookings_table`) fails at the database with a
  1048 integrity violation, not a form error. The migration is load-bearing.
- Every guest email send is null-guarded (`if (! empty($booking->guest_email))`).
- ⚠️ **`CreateBooking::afterCreate()` and `mutateFormDataBeforeCreate()` are dead
  code.** They gate on `is_booked`, `customer_name`, `start_date` — all **Room**
  columns absent from this form — so they always return early. Manual bookings
  never email on create; mail goes out when staff hit Confirm.

**Rate limits on `POST /api/bookings`** (order matters, duplicate check first):

1. Duplicate (same email + room + dates, incl. `pending_payment`) → always 422.
2. One active booking per email → **only when payment is off**. Paying guests are
   self-limiting and must stay free to book several rooms in one sitting.
3. `throttle:10,1` per IP on the route itself.

---

## Filament gotchas

Full detail in [FILAMENT_UI.md](FILAMENT_UI.md). The ones that cost time:

- **`/admin/bookings` is `BookingCalendar`, not `ListBookings`** —
  `'index' => Pages\BookingCalendar::route('/')`. The list view is at `/list`.
- **`<x-filament-panels::page>` already renders `getHeaderWidgets()`.** Calling
  `<x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" />` inside
  it renders everything twice. That was the 4-widget bug — *not* auto-discovery.
  `$isDiscovered = false` on the widgets did nothing for it.
- ⚠️ **A long inline `<script>` in a page view can break Livewire.** libxml,
  which Livewire uses to count root elements, mis-parsed the scanner script and
  closed the `.fi-page` div early; Filament's three action-modal `<form>`s then
  became sibling roots and the page threw
  `MultipleRootElementsDetectedException` on **every load with `APP_DEBUG=true`**
  (production with debug off never runs the check). Fix: `@push('scripts')`.
  See `tests/Feature/ScanTicketPageTest.php`.
- **A bulk action's `visible()` closure resolves `false` even for a Super Admin.**
  Authorise *inside* the action instead — see `PendingBookingsWidget::runBulk()`.
  Row actions can use `visible()` normally.
- **`resources/css/app.css` never loads in the Filament panel.** It serves the
  public React site. Panel CSS goes through a render hook
  (`PanelsRenderHook::HEAD_END`).
- **Mailable property cannot be `$subject`** — `Illuminate\Mail\Mailable` already
  declares it, and a typed promoted property of that name is a fatal
  redeclaration. Ours is `$record`.
- Card borders are Tailwind `ring`s, not `border`s — set `--tw-ring-color`.

---

## Local development

```bash
php artisan serve --port=8001     # 8000 is often taken by another project
npm run build                     # production assets -> public/build/
npm run dev                        # Vite watch (separate terminal)
php artisan test                   # 37 passing
```

**Admin:** `admin@local.test` / `password` at `/admin/login` — created by
`AdminUserSeeder` (idempotent; `ADMIN_EMAIL`/`ADMIN_PASSWORD` override it).
Without that seeder a fresh `migrate:fresh --seed` produces **zero users** and
the panel is unreachable — `RoleSeeder` only assigns roles to accounts that
already exist.

```bash
php artisan optimize:clear                        # after provider/config edits
php artisan view:clear                            # after Blade/render-hook edits
php artisan db:seed --class=RoleSeeder            # after adding a role
```

⚠️ **Tests run on SQLite `:memory:`, production is MySQL.** SQLite ignores ENUM
constraints, so a status value MySQL would reject passes locally in silence. A
new `bookings.status` value needs a migration widening the MySQL enum — see
`add_payment_fields_to_bookings_table` and `widen_bookings_status_on_sqlite`.
`CACHE_STORE=array` in `phpunit.xml` means the rate limiter resets per test.

⚠️ **`vendor/composer/*` is tracked.** Staging commits `--no-dev` versions that
break local dev (`Laravel\Pail\PailServiceProvider not found`). Workflow:
`composer install` to work locally, then `git checkout -- vendor/` before
committing. Now that `deploy.sh` runs `composer install` on the server, this can
finally be untracked: `git rm -r --cached vendor/`.

**Browser testing:** `playwright` is a devDependency. Write throwaway scripts
**inside the project root** so `import { chromium } from 'playwright'` resolves,
and delete them after. Use `waitUntil: 'domcontentloaded'` — `networkidle` never
settles on the homepage because the hero video streams continuously.

Two traps when driving Filament in Playwright: the login fields are
`input[id="data.email"]` / `input[id="data.password"]` (the dot needs escaping,
so prefer the attribute selector), and **assert on page content, not the URL** —
Livewire swaps in the dashboard before it updates the path, which reads as a
false "login rejected".

**Shell note (Windows):** Git-Bash `/tmp` and Python's `/tmp` resolve
differently. A Bash `cp` into `/tmp` is invisible to a Python script started
from the same shell. Use a project-relative path or the scratchpad.

---

## Deployment

`deploy.sh` (repo root) is called by the GitHub Actions workflow on push to
`main`. ⚠️ **It was referenced by CI but did not exist for a long time**, so
migrations never ran on the server — which explained three separate "500 errors"
as one root cause. It runs:

```
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
npm run build
ln -sfn "$PWD/storage/app/public" "$PWD/public/storage"
php artisan config:cache && route:cache && view:cache
```

After a deploy: `php artisan migrate:status` and
`tail -n 100 storage/logs/laravel.log`.

⚠️ Don't run `route:cache` locally — it breaks the dev loop.

---

## Known open items

- **Scanner and the scan page:** fixed, but worth a real-device check that the
  camera permission flow still works now that the scripts moved to the layout
  stack. Tests prove the page renders and the assets are present, not that the
  camera starts.
- **`/gallery`** has a `grid-cols-3` element overflowing both edges
  (`left=-105`, `right=425` at 320px). Clipped, so no page-level bleed, but it
  looks wrong. Pre-existing, in the `animated-gallery` component.
- **`.git` carries ~1.27 GB** of large binaries from earlier commits (two files
  over GitHub's 50 MB warning). Shrinking needs a history rewrite. The SQLite DB
  itself is *not* tracked.
- **Hero video is 36 MB.** Lazy-loaded (`preload="none"` + IntersectionObserver)
  so it no longer costs an initial page load, but re-encoding to H.265/VP8
  (target 8–10 MB) is still worth doing.
- **Credentials:** the production DB password, mail credentials and TGI keys were
  scrubbed from `.env.example` and replaced with empty placeholders. **Rotating
  the live DB and mail passwords was advised and is not confirmed done.**
- **`default.php` and `public/default.php`** (195 lines each, arrived from
  staging) are unreviewed and may be stray web entry points.
- **`UserPolicy` role-name mismatch** — see Roles above.

---

## Design system (geothermal-coast)

| Token | Value | Use |
|---|---|---|
| `--ib-basalt` | `#0b0f0e` | Dark ground |
| `--ib-glacier` | `#f4f7f6` | Light text |
| `--ib-teal` | `#6fb8bc` | Focus rings, active |
| `--ib-ember` | `#e2924a` | Primary CTAs |

Brand navy for admin and email is `#002F61`.

Type: **Fraunces** (serif headings) + **Hanken Grotesk** (UI).
Motion: `cubic-bezier(0.16, 1, 0.3, 1)`, 0.8–1.6s, Framer Motion `whileInView`,
`staggerChildren: 0.08`. Everything respects `prefers-reduced-motion`.

**Aurora** (`components/AuroraCanvas.jsx`) is a real Three.js fragment shader —
5-octave fbm noise, additive blending — wrapped by `AuroraLayer.jsx` so Three.js
is **code-split out of the main bundle**. It pauses off-screen via
IntersectionObserver, caps DPR at 1.5, renders one static frame under reduced
motion, and falls back to a CSS gradient without WebGL. Used on the homepage
confidence section and the About/Contact heroes. The homepage
`ScrollExpandMedia` hero is deliberately untouched.

⚠️ **`min-width: 0` on carousel rails is load-bearing.** They are flex rows of
fixed-width cards inside grid items; with the default `min-width: auto` they
refuse to shrink, forcing the document to ~1027px at a 320px viewport. The
header is `position: fixed`, so it inherits that width and pushes the hamburger
off-screen. Don't remove it.

---

## Mobile app (planned, not started)

Hospi Sales — white-label multi-tenant hospitality ops app, built around
meal-order delay accountability. Nothing is implemented yet.

- [HOSPI_SALES_BRIEF.md](HOSPI_SALES_BRIEF.md) — codebase map, order/delay data
  model, Expo + React Native architecture decisions, build phases
- `prompt/agent-3-stitch.md` — UI design prompt
- `prompt/agent-2-build.md` — build prompt

Two facts worth knowing before touching it: **Sanctum is already installed**
(`personal_access_tokens` migration + an `auth:sanctum` route in
`routes/api.php`), and **no existing table has a `tenant_id`**.

---

## Attribution

Developer: Manlikenonso1k · victorynonso9@gmail.com
Design brief: `prompt/homepage-redesign.md`

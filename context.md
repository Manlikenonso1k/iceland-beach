# Iceland Beach Resort — Working Context

**Last updated:** 2026-08-29
**Branch:** `review/final-edits` · **Repo:** https://github.com/Manlikenonso1k/iceland-beach.git

Working notes for picking this project back up. The user-facing changelog lives
in [README.md](README.md); Filament UI mechanics live in
[FILAMENT_UI.md](FILAMENT_UI.md); the ticketing system is documented in
[RESULT.md](RESULT.md). This file is for the things that are **not obvious from
the code** and cost time to rediscover.

---

## Read this first: two booking systems

The single most confusing thing in this codebase.

| | `room` table | `bookings` table |
|---|---|---|
| Columns | `is_booked` enum, `start_date`, `end_date`, `customer_name`, `email` | `room_id`, `check_in`, `check_out`, `status`, `guest_*` |
| `is_booked` values | `no` · `booked` · `rejected` · `expired` | — |
| `status` values | — | `pending` · `confirmed` · `rejected` |

**`bookings` is now canonical** (decided 2026-08-29). It previously held only a
`room_type` *string* with no `room_id` and no `check_out`, so it could not be
joined to a room or tested for overlap — which is why availability had been
read off `room.is_booked` instead.

The rule now lives in exactly one place, `Booking::scopeBlocking()`:

> **Only a `confirmed` booking holds a room.** `pending` and `rejected` never do.

Flow:

1. Public booking → `ReservationService::requestBooking()` creates a **pending**
   `Booking` and deliberately leaves `room.is_booked = 'no'`, staging guest
   details + dates on the room. That combination (`is_booked='no'` **with**
   dates) is exactly what `PendingBookingsWidget` already treats as awaiting
   review — it was not invented for this change.
2. Staff **Confirm** in Filament → `status='confirmed'` **and** the room is
   flipped to `is_booked='booked'` so the calendar widget stays in step.
3. Staff **Reject** → `status='rejected'` and the room is released to `'no'`.

`room.is_booked` is therefore a *denormalised mirror* for the Room-based
calendar/widgets, not the source of truth. If they ever disagree, `bookings`
wins.

⚠️ **`rejected` and `expired` are historical states, not live holds.** The old
front-end treated any value other than `'no'` as unavailable, so rejected and
expired bookings were blocking rooms. Don't reintroduce `is_booked !== 'no'`.

---

## Referral programme

Ticket buyers enter a partner code at checkout; the partner is credited only on
successful payment.

**Why a model observer, not the payment callbacks:** a ticket reaches
`paid = true` from **four** places —

- `PaymentController::paystackVerify()`
- `PaymentController::tgiPayCallback()`
- `PaymentController::tgiPayWebhook()`
- `TicketPaymentRefreshService`

Attribution lives in `App\Observers\TicketObserver` (registered in
`AppServiceProvider::boot()`), so no path can be missed and the payment code
stays untouched.

- **Double-credit guard is the `unique` index on
  `referral_ticket_sales.ticket_id`**, not caller discipline. The `exists()`
  pre-check in the observer is just an optimisation; the `QueryException` catch
  is the real handler for racing callbacks.
- Unknown / deactivated codes are ignored silently and must **never** block a
  purchase.
- The checkout input is still `name="promo_code"` so the existing `ICECOCONUT`
  discount and the pricing AJAX endpoint keep working. Only the **label** says
  "Referral Code". The value is copied to `tickets.referral_code` for tracking —
  the referral layer sits *on top* of pricing, it does not replace it.
- Partners get the **Referral** role and are scoped in
  `ReferralResource::getEloquentQuery()` — scoping there (not in the page) means
  a partner cannot reach another's record by editing the URL.

---

## Filament gotchas

Full detail in [FILAMENT_UI.md](FILAMENT_UI.md). The ones that cost time:

- **`/admin/bookings` is `BookingCalendar`, not `ListBookings`** —
  `'index' => Pages\BookingCalendar::route('/')`. The list view is at `/list`.
- **`<x-filament-panels::page>` already renders `getHeaderWidgets()`.** Calling
  `<x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" />` inside
  it renders everything twice. That was the 4-widget bug — *not* auto-discovery.
  `$isDiscovered = false` on the widgets did nothing for it.
- `User::canAccessPanel()` gates on **role**, so a user can authenticate
  successfully and still be refused the panel. Any new admin account needs a
  role assigned.
- Card borders are Tailwind `ring`s, not `border`s — set `--tw-ring-color`.

---

## Local development

```bash
php artisan serve --port=8001     # 8000 is often taken by another project
npm run build                     # production assets → public/build/
npm run dev                       # Vite watch (separate terminal)
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

**Browser testing:** `playwright` is a devDependency (added 2026-08-29). Write
throwaway scripts **inside the project root** so `import { chromium } from
'playwright'` resolves, and delete them after. Use
`waitUntil: 'domcontentloaded'` — `networkidle` never settles on the homepage
because the hero video streams continuously.

Two traps when driving Filament in Playwright: the login fields are
`input[id="data.email"]` / `input[id="data.password"]` (the dot needs escaping,
so prefer the attribute selector), and **assert on page content, not the URL** —
Livewire swaps in the dashboard before it updates the path, which reads as a
false "login rejected".

---

## Known open items

- **`/gallery`** has a `grid-cols-3` element overflowing both edges
  (`left=-105`, `right=425` at 320px). Clipped, so no page-level bleed, but it
  looks wrong. Pre-existing, in the `animated-gallery` component.
- **`.git` carries ~1.27 GB** of large binaries from earlier commits (two files
  over GitHub's 50 MB warning). Shrinking needs a history rewrite. The SQLite DB
  itself is *not* tracked.
- **Hero video is 36 MB.** Now lazy-loaded (`preload="none"` +
  IntersectionObserver) so it no longer costs an initial page load, but
  re-encoding to H.265/VP8 (target 8–10 MB) is still worth doing.
- **`.env` holds live Hostinger MySQL credentials** while the app runs on
  SQLite locally. `.env` is gitignored, but consider rotating and keeping
  production secrets on the server only.
- **`BookingResource`'s form now edits booking fields**, not room fields. Room
  price / category / image are managed at `/admin/rooms`. Worth confirming no
  staff workflow depended on the old behaviour.

---

## Design system (geothermal-coast)

| Token | Value | Use |
|---|---|---|
| `--ib-basalt` | `#0b0f0e` | Dark ground |
| `--ib-glacier` | `#f4f7f6` | Light text |
| `--ib-teal` | `#6fb8bc` | Focus rings, active |
| `--ib-ember` | `#e2924a` | Primary CTAs |

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

## Attribution

Developer: Manlikenonso1k · victorynonso9@gmail.com
Design brief: `prompt/homepage-redesign.md`

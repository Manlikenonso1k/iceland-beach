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

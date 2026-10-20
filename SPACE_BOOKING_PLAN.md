# Space Booking Plan

Mirror the working room-booking system for **spaces** (venues), add a **Space Host** role, and
give Front Desk / Space Host read-only cross-visibility. Additive only; room booking and Front
Desk behaviour must not change.

---

## 1. Reality check — where `host.md` is wrong

`host.md` is the brief, not the ground truth. Three of its assumptions are false; do not build on them.

1. **There is no space booking ledger at all.** `app/Http/Controllers/Api/ServiceBookingController.php::store()`
   validates the request, sends `mail.service-booking-notification` + `mail.service-booking-confirmation`,
   and **creates no record** (its own comment: the `services` table is "the public catalogue, not a
   booking ledger"). So today there is nothing to confirm, deny, report on, or draw on a calendar.
   "Make spaces work like rooms" is not wiring a calendar onto existing data — the ledger must be created first.
2. **`SpaceResource` is not a booking system.** `app/Filament/Resources/SpaceResource.php` is a
   catalogue/pricing resource over the legacy `services` table, **Super Admin only**
   (`canAccess()` → `hasRole('Super Admin')`). No calendar, no confirm/deny, no reports. It stays
   catalogue-only; space bookings get their own resource.
3. **No `Host` role exists today.** `database/seeders/RoleSeeder.php` seeds exactly: Super Admin,
   Admin, Manager, Front Desk, Waiter, Scanner, Referral. Nothing named Host is in the DB or code.

**Naming:** use **`Space Host`**, because the planned Hospi Sales mobile app
(`HOSPI_SALES_BRIEF.md`, `prompt/agent-2-build.md`) defines its own `Host` role for restaurant
tables/beds/cabins — a future clash we avoid now rather than untangle later.

---

## 2. How room booking works today

**Ledger / model**
- `bookings` table is canonical. `app/Models/Booking.php`: `room_id`, `guest_name`,
  `guest_email` (nullable — phone bookings have none), `guest_phone`, `email_opt_in`, `room_type`
  (label snapshot), `check_in`, `check_out`, `status`, `payment_reference`, `payment_status`,
  `rejection_reason`.
- `Booking::BLOCKING_STATUSES = ['confirmed']` — **only a confirmed booking holds a room**; pending
  requests never block, so several guests may request the same dates.
- `scopeBlocking($start, $end)` uses half-open overlap (`check_in < $end`, `check_out > $start`
  or null), so same-day turnover stays bookable.
- Rooms live in the **singular legacy table `room`** (`app/Models/Room.php`).

**State transitions** — all go through `app/Services/BookingWorkflowService.php`:
- `confirmBooking()` — sets `confirmed`, clears `rejection_reason`, mirrors the hold onto the
  `room` row (`is_booked = 'booked'`, customer/date fields), logs via `ActivityLogService`, emails the guest.
- `rejectBooking()` — sets `rejected` + reason, releases the room (`is_booked = 'no'`), logs, emails.
- `markBookingPaid()` — **idempotent** (early-returns when `payment_status === 'paid'`), latches
  payment first, then calls `confirmBooking()` inside try/catch so a bad address never loses money.

**Reports** — `app/Services/BookingReportService.php`: `PERIODS` = daily/weekly/monthly;
`range()` resolves the window; `rows()` takes bookings whose `check_in` is in range and
`status != 'rejected'`; `amount()` exists because **`bookings` has no price column** — amount is
`room.room_price × nights` (min 1 night); `download()` renders `pdf.booking-report` through DomPDF
and returns a `StreamedResponse`. Exposed by `BookingResource::downloadReportAction()`.

**Admin UI** — `app/Filament/Resources/BookingResource.php` (`canAccess()` = any `web`-guard user,
so Front Desk included). Pages (`getPages()`):
`index => Pages\BookingCalendar` (**not** ListBookings), `list => /list`, `create`, `edit`.
`app/Filament/Resources/BookingResource/Pages/BookingCalendar.php` header actions: New Booking,
`downloadReportAction()`, Table View; header widgets: `BookingCalendarWidget`,
`PendingBookingsWidget`, `ConfirmedBookingsWidget`. Row actions `confirm` / `reject` are visible
only while `status === 'pending'` and both delegate to `BookingWorkflowService`.

**Public flow** — `POST /api/bookings` → `app/Http/Controllers/Api/BookingController.php::store()`
(`throttle:10,1`), which calls `app/Services/ReservationService.php::requestBooking()`
(availability via `scopeBlocking` + the legacy `room` hold check, then `Booking::create`).

**Payments** — gated by `Setting::get('booking_payment_required', false)` **and**
`PaystackService::isConfigured()`. Reference prefix `BOOK-`; amounts in integer **kobo**. Callback:
`app/Http/Controllers/BookingPaymentController.php`. `app/Http/Controllers/PaystackWebhookController.php`
is the single webhook entry point and dispatches by reference prefix (`BOOK-` → booking, else ticket)
**because Paystack allows only one webhook URL per mode**. Toggle:
`app/Filament/Pages/ManageSettings.php` (Super Admin only).

---

## 3. Spaces: today vs. needed

| Concern | Rooms (works) | Spaces (today) | To add |
|---|---|---|---|
| Catalogue | `room` / `Room` | `services` / `ServiceBooking` | unchanged |
| Ledger | `bookings` / `Booking` | **none** | `space_bookings` / `SpaceBooking` |
| Transitions | `BookingWorkflowService` | none | `SpaceBookingWorkflowService` |
| Reports | `BookingReportService` | none | `SpaceBookingReportService` |
| Admin UI | `BookingResource` + calendar | `SpaceResource` (catalogue, Super Admin) | `SpaceBookingResource` + calendar |
| Public API | `POST /api/bookings` | `POST /api/service-bookings` (emails only) | persist a `SpaceBooking` first, keep emails |
| Payments | `BOOK-` prefix, `booking_payment_required` | none | `SPACE-` prefix, `space_payment_required` |

`services` columns (price is **naira**, decimal 12,2 — not kobo): `service_name`,
`service_category`, `service_price`, `service_image`, `main_image`, `gallery_images`,
`description`, `capacity`, `is_booked` enum('no','booked','expired'), `customers_name`,
`customers_email`, `signin`, `signout`, `no_of_people`, `mailsent`, timestamps, `deleted_at`.

---

## 4. Schema: `space_bookings` (one additive migration)

| Column | Type | Note |
|---|---|---|
| `id` | bigIncrements | |
| `space_id` | unsignedBigInteger, **index, NOT a FK** | a constrained FK against the legacy `services` table risks MySQL **errno 150**, as already hit on `room` |
| `space_name` | string nullable | label snapshot, mirrors `bookings.room_type` |
| `guest_name` / `guest_phone` | string / string nullable | |
| `guest_email` | string **nullable** | phone bookings have no address |
| `guests` | unsignedInteger nullable | party size |
| `email_opt_in` | boolean default false | |
| `check_in` / `check_out` | dateTime / dateTime nullable | |
| `status` | string default `'pending'` | string, not enum: widening a MySQL enum needs a migration and SQLite ignores enums, so tests pass while prod rejects |
| `payment_reference` | string nullable, index | `SPACE-…` |
| `payment_status` | string nullable | |
| `rejection_reason` | text nullable | |
| timestamps | | |

Indexes: `(status, check_in)` for the calendar + blocking scope, plus `guest_email`.
Guard the migration with `Schema::hasTable('space_bookings')` so a re-run is safe. Never
`migrate:fresh`, never seed over data.

---

## 5. Roles and permissions here

- `spatie/laravel-permission 7.1`, but **no permissions are defined anywhere** — every gate tests
  **role names** directly. Follow that; do not introduce a permission layer.
- Panel entry: `app/Models/User.php::canAccessPanel()` checks role names, plus an email bypass for
  `victorynonso9@gmail.com`. `Referral` is deliberately excluded.
- Per-resource restriction pattern: `app/Filament/Concerns/RestrictsFrontDeskAndScanner.php`, a trait
  overriding `canViewAny()` / `canCreate()` / `canEdit()` / `canDelete()` / `canDeleteAny()`
  (used by ReferralResource, RoleResource, TicketResource, UserResource, VendorRequestResource).
- **Trap:** `app/Policies/UserPolicy.php` tests `hasRole('super_admin')` while the seeder creates
  `'Super Admin'` — a real bug. Do not copy that typo; always use the seeded names.
- **Filament 3 trap:** `ListRecords::authorizeAccess()` is empty, so a resource's list page is **not**
  gated by `canViewAny()` alone. Read-only must be real method overrides (and the custom
  confirm/deny actions must check too), and it must be tested — hiding buttons is not enforcement.

### Adding Space Host
1. `database/seeders/RoleSeeder.php` — append `'Space Host'` to `$roles` (`Role::findOrCreate`, so re-running is safe).
2. `User::canAccessPanel()` — add `'Space Host'` to the `hasAnyRole([...])` list.
3. New trait `app/Filament/Concerns/RestrictsSpaceBookingWrites.php` — used by `SpaceBookingResource`:
   `canViewAny()` → true for staff; `canCreate()`, `canEdit()`, `canDelete()`, `canDeleteAny()` →
   false for `Front Desk` / `frontdesk` / `Scanner`; the `confirm` and `reject` actions gate on the
   same helper via `->visible(...)` **and** re-check inside `->action()`.
4. New trait `app/Filament/Concerns/RestrictsRoomBookingWrites.php` — used by `BookingResource`:
   same shape, but the denied role is `Space Host` (plus `Scanner`). This is the only change to the
   room resource; Front Desk keeps every power it has today.

Net effect: Space Host writes spaces and **views** rooms + room reports; Front Desk writes rooms
(unchanged) and **views** spaces + space reports; Super Admin / Admin / Manager write both.
Report downloads stay available to viewers — reading figures is not a write.

---

## 6. Files to create

- `database/migrations/2026_10_20_090000_create_space_bookings_table.php` — section 4.
- `app/Models/SpaceBooking.php` — `$table = 'space_bookings'`, `BLOCKING_STATUSES = ['confirmed']`,
  `scopeBlocking()` copied from `Booking`, `space()` → `ServiceBooking` (`space_id`), datetime casts.
- `app/Services/SpaceBookingWorkflowService.php` — `confirmBooking()`, `rejectBooking()`,
  `markSpaceBookingPaid()`; mirrors `BookingWorkflowService` exactly, including the idempotent
  early-return, the activity log entries and the guest emails, and mirrors the hold onto
  `services.is_booked` (`'booked'` on confirm, `'no'` on reject).
- `app/Services/SpaceBookingReportService.php` — same `PERIODS` / `range()` / `rows()` / `amount()` /
  `download()`; **amount = `services.service_price` × nights** (naira, no conversion).
- `resources/views/pdf/space-booking-report.blade.php` — copy of `pdf/booking-report`.
- `app/Filament/Resources/SpaceBookingResource.php` + `SpaceBookingResource/Pages/`
  (`SpaceBookingCalendar.php` as `index`, `ListSpaceBookings.php` at `/list`,
  `CreateSpaceBooking.php`, `EditSpaceBooking.php`), with confirm/reject actions and
  `downloadReportAction()`.
- `app/Filament/Widgets/SpaceBookingCalendarWidget.php`, `PendingSpaceBookingsWidget.php`,
  `ConfirmedSpaceBookingsWidget.php` + their Blade views.
- `app/Filament/Concerns/RestrictsSpaceBookingWrites.php`, `RestrictsRoomBookingWrites.php`.
- `app/Http/Controllers/SpacePaymentController.php` — callback twin of `BookingPaymentController`.
- Tests: `tests/Feature/SpaceBookingWorkflowServiceTest.php`,
  `SpaceBookingReportTest.php`, `SpaceBookingRoleAccessTest.php`, `PublicSpaceBookingTest.php`.

Note: an untracked `space_bookings` migration and `app/Models/SpaceBooking.php` already exist in
the working tree from a parallel session; treat them as the starting point, not a second copy.

## 7. Files to change

- `database/seeders/RoleSeeder.php` — add `'Space Host'`.
- `app/Models/User.php` — add `'Space Host'` to `canAccessPanel()`.
- `app/Filament/Resources/BookingResource.php` — `use RestrictsRoomBookingWrites;` and gate the
  `confirm` / `reject` actions. Nothing else.
- `app/Http/Controllers/Api/ServiceBookingController.php` — create a `SpaceBooking` (status
  `pending`), keep both existing emails, add overlap rejection via `scopeBlocking`, and return a
  Paystack URL when payment is required. Route `POST /api/service-bookings` already exists with
  `throttle:10,1`.
- `app/Http/Controllers/PaystackWebhookController.php` — one additive branch:
  `SPACE-` → space, `BOOK-` → booking, else ticket. Keep the single-URL comment accurate.
- `app/Filament/Pages/ManageSettings.php` — add `space_payment_required` (**default `false`**:
  email-confirmation is the default flow; Super Admin can switch to pay-to-confirm). Page stays
  Super Admin only.
- `routes/web.php` — space payment callback route (`signed` where the room twin is).
- `app/Filament/Resources/SpaceResource.php` — unchanged (catalogue only); optionally link to the
  new calendar.

---

## 8. Test checklist

**Space Host**
- Reaches the panel; sees Space Bookings.
- Creates, edits, confirms and denies a space booking; confirm sets `confirmed` + `services.is_booked = 'booked'`; deny sets `rejected` + releases it.
- Downloads daily / weekly / monthly space reports.
- Views room bookings and the room list page, and downloads room reports.
- **403** on room create / edit / delete, including direct URLs (`/admin/bookings/create`, `/admin/bookings/{id}/edit`) and the confirm/reject actions — not merely hidden buttons.

**Front Desk**
- Rooms behave exactly as today: create, edit, confirm, deny, reports.
- Views space bookings and the space list page, and downloads space reports.
- **403** on space create / edit / delete / confirm / deny, via direct URL too.

**Super Admin** (and Admin / Manager)
- Full write access to both ledgers; `ManageSettings` reachable and both toggles visible.

**Public guest**
- `POST /api/service-bookings` persists a `pending` `SpaceBooking` **and** still sends both emails.
- The request appears on the Space Host calendar and in `PendingSpaceBookingsWidget`.
- A pending request does **not** block the same dates; a confirmed one does (half-open: a booking ending when another begins is allowed).
- Rate limit `throttle:10,1` still applies.

**Payment toggle, both ways**
- `space_payment_required = false` → request is created `pending`, no Paystack reference, email flow only.
- `space_payment_required = true` **and** `PaystackService::isConfigured()` → reference `SPACE-…`, kobo amount; callback and webhook both land on `markSpaceBookingPaid()` and produce one confirmation only (idempotency: run it twice, assert a single email and a single log entry).
- Webhook dispatch: `SPACE-` → space, `BOOK-` → booking (room payments still work), unknown → ticket.

**Regression**
- Whole existing suite green, especially `BookingWorkflowServiceTest`, `BookingReportTest`,
  `BookingCalendarWidgetTest`, `BookingRateLimitTest`, `RestrictedAdminSectionsTest`.

**Deploy:** `git pull` → `php artisan migrate --force` → `php artisan db:seed --class=RoleSeeder`
→ `php artisan optimize:clear`.

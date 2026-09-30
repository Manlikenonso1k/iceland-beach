# Hospi Sales — Architecture Brief

White-label hospitality operations app (Android + iOS) extending the existing
Iceland Beach Resort Laravel 12 + Filament 3 system.

This document is the map Agent 2 and Agent 3 build from. Every path, table,
column and route named here was read out of this repository, not assumed.

---

## 1. PRIORITY 1 — The product exists to kill meal-order delays

A guest orders food, it arrives late, and the manager says *"I wasn't aware there
was a delay."* Hospi Sales removes that excuse. Everything else in this document
serves this loop.

```
Waitress enters order  ──>  server stamps due_at  ──>  kitchen queue (Chef)
        │                         │                          │
        │                         ▼                          ▼
        │                 countdown is server-          accept / decline
        │                 authoritative                 with reason / ready
        │                         │                          │
        ▼                         ▼                          │
 notify kitchen + manager   Manager Order Board  <───────────┘
                            RED / YELLOW / GREEN
                                  │
                                  ▼
                       overdue ──> push to manager
```

### Traffic-light rule (single source of truth)

Computed from `due_at` vs `now()` vs `completed_at` — **server-side, never from
the phone clock**, which staff can change:

| Colour | Condition |
|---|---|
| **GREEN** | `completed_at IS NOT NULL` AND `completed_at <= due_at` |
| **YELLOW** | `completed_at IS NULL` AND `now() <= due_at` (pending/accepted, in time) |
| **RED** | `completed_at IS NULL` AND `now() > due_at` (**overdue**) — or `status = declined` |
| GREY | `completed_at IS NOT NULL` AND `completed_at > due_at` (late completion — still counts against SLA) |

Expose this as a computed `status_colour` plus `seconds_remaining` on every order
payload, so the app never recomputes the rule and the two cannot drift.

### `due_at` is set once, at insert

`due_at = placed_at + target_minutes`, where `target_minutes` is copied from the
meal's `prep_time_minutes` **at order time** (fallback 30). Snapshot it on the
order — exactly the pattern `referral_ticket_sales.commission_rate` already uses
in `app/Observers/TicketObserver.php`: a manager raising a prep time later must
not retroactively make past late orders look on-time.

For a multi-item order, `target_minutes = MAX(item prep_time_minutes)` — the
order is done when the slowest dish is done.

---

## 2. What already exists and MUST be reused

### 2.1 Authentication — Sanctum is already installed

- `database/migrations/*_create_personal_access_tokens_table.php` — present
- `routes/api.php` already contains `Route::middleware('auth:sanctum')->get('/user', ...)`

**No auth package needs installing.** Add token issue/revoke endpoints only.

### 2.2 Roles and permissions — `spatie/laravel-permission` 7.1

- `database/seeders/RoleSeeder.php` seeds exactly: `Super Admin`, `Admin`,
  `Manager`, `Front Desk`, `Waiter`, `Scanner`, `Referral`
- **No permissions are defined — only role names.** Every gate in this codebase
  tests role names directly.
- `app/Models/User.php::canAccessPanel()` — panel gate
- `app/Filament/Concerns/RestrictsFrontDeskAndScanner.php` — per-resource
  restriction trait (`canViewAny` / `canCreate` / `canEdit` / `canDelete` / `canDeleteAny`)
- Policies: `app/Policies/{ActivityLog,Invoice,Role,User}Policy.php`
- Second-panel precedent: `app/Providers/Filament/ReferralPanelProvider.php`
  (`->id('referral')->path('referral')->authGuard('referral')`) with its own guard
  in `config/auth.php`. This is the proven pattern if Hospi Sales ever needs a
  web panel of its own.

> **Known defect — do not copy it:** `app/Policies/UserPolicy.php` tests
> `hasRole('super_admin')` (snake_case) but `RoleSeeder` creates `Super Admin`.
> Use the seeded names.

### 2.3 Payments — `app/Services/PaystackService.php`

| Method | Purpose |
|---|---|
| `isConfigured()` | secret key present and not a placeholder |
| `initialize($email, $amountKobo, $reference, $callbackUrl, $metadata)` | start transaction |
| `verify($reference)` | **server-side** verification — never trust the client |
| `verifyWebhookSignature($payload, $signature)` | HMAC-SHA512 via `hash_equals` |
| `generateReference($prefix = 'BOOK')` | `PREFIX-RANDOM-timestamp` |

Amounts are **kobo** (integer). Config lives at `config/services.php` → `services.paystack.*`.

Flow controllers:

- `app/Http/Controllers/BookingPaymentController.php` — `callback()`, `webhook()`,
  private `markPaid()` delegating to `BookingWorkflowService::markBookingPaid()`
- `app/Http/Controllers/PaystackWebhookController.php` — Paystack allows **one
  webhook URL per mode**, so a single endpoint dispatches by reference prefix
  (`BOOK-` → booking, otherwise ticket)

Routes in `routes/web.php`:

```
GET  /booking/payment/callback      booking.payment.callback
POST /webhooks/paystack             paystack.webhook
POST /webhooks/paystack/booking     booking.payment.webhook
POST /webhooks/paystack/tickets     ticket.payment.webhook
```

TGI Pay is a second gateway inside `app/Http/Controllers/PaymentController.php`
(`initiateTgiPay`, `tgiPayCallback`, `tgiPayWebhook`). Ticket flow only.

`markBookingPaid()` is **idempotent** — it early-returns when
`payment_status === 'paid'`, because callback and webhook routinely both fire for
one transaction. Any mobile payment must enter through that same method.

### 2.4 Bookings — rooms

- `app/Models/Booking.php`, table `bookings`
- Columns: `room_id, guest_name, guest_email` (nullable), `guest_phone,
  email_opt_in, room_type, check_in, check_out, status, payment_reference,
  payment_status, rejection_reason`
- `const BLOCKING_STATUSES = ['confirmed']` and `scopeBlocking($start, $end)` —
  **only a confirmed booking holds a room**; overlap is half-open so same-day
  turnover stays bookable
- `app/Models/Room.php` — the table is **`room`** (singular, legacy). Validation
  in this repo reads `exists:room,id`.
- `app/Services/ReservationService.php` — `requestBooking()`, `assertNotOverlapping()`
- `app/Services/BookingWorkflowService.php` — `confirmBooking()`, `rejectBooking()`,
  `markBookingPaid()`. All status changes go through here so the room mirror, the
  activity log and the guest email cannot drift.
- API: `GET /api/rooms` (annotates `is_available` for a date range),
  `POST /api/bookings` (`throttle:10,1`)

### 2.5 Services — tables / beds / cabins (the Hosts department)

- `app/Models/ServiceBooking.php` — the table is **`services`**, not `service_bookings`
- Columns: `service_name, service_category, service_price, service_image,
  main_image, gallery_images, description, capacity, is_booked, customers_name,
  customers_email, signin, signout, no_of_people` plus `deleted_at` (SoftDeletes)
- API: `GET /api/services` → `app/Http/Controllers/Api/CatalogController.php`;
  `POST /api/service-bookings`

### 2.6 Invoices — the receipt pattern for Gate Sales

- `app/Models/Invoice.php` — `invoice_number, invoice_date, customer_name,
  customer_address, telephone, total_amount, total_in_words, bank_name,
  bank_account_number, bank_account_name`
- `app/Models/InvoiceItem.php` — `invoice_id, description, quantity, unit_price, sub_total`
- The invoice number is generated with a hash prefix, and `total_amount` /
  `total_in_words` are synced from items — covered by `tests/Feature/InvoiceModelTest.php`
- Print / PDF: `GET /invoices/{id}/view` (`invoices.view`) →
  `resources/views/pdf/invoice.blade.php`
- Admin: `app/Filament/Resources/InvoiceResource.php` → `/admin/invoices`

### 2.7 Existing POS — the closest analogue to meal orders

**Read this before designing `orders`.** There is already a POS in this codebase:

| Table | Columns | Model |
|---|---|---|
| `products` | `name, category, price, stock_quantity, low_stock_threshold` | `Product` |
| `sales` | `waiter_id, table_id, total_amount, payment_method, sale_date` | `Sale` |
| `sale_items` | `sale_id, product_id, quantity, price, is_voided` | `SaleItem` |
| `dining_tables` | `table_name, assigned_waiter_id, status` | `DiningTable` |
| `waiters` | `full_name, username, password_hash, pin_hash, role, is_active` | `Waiter` |
| `void_logs` | void audit trail | `VoidLog` |

`app/Services/PosService.php::processSale()` already handles: table-assignment
guard, a 60-second duplicate-sale window, per-item stock checks, a mandatory
`void_reason`, and one atomic DB transaction.

**The gap Hospi Sales fills:** `sales` and `sale_items` carry **no order status,
no accept/decline, no timing and no prep target**. A sale is a completed cash
event, not a tracked order. So the order/delay engine is genuinely **new** — but
it should reuse `products` for the meal-catalogue shape, `dining_tables` for
table references, and copy `PosService`'s transaction and duplicate-window
discipline rather than inventing its own.

### 2.8 Gate Sales — tickets

- `app/Models/Ticket.php` — `uuid, name, email, phone, ticket_type,
  pickup_location, quantity, amount` (kobo), `order_ref, referral_code,
  payment_gateway, payment_ref, paid, checked_in, scan_count, qr_code_path`
- `app/Models/TicketScanLog.php`, `app/Observers/TicketObserver.php`
- Scan page: `app/Filament/Pages/ScanTicket.php` +
  `resources/views/filament/pages/scan-ticket.blade.php`
- QR verify route: `GET /ticket/verify/{uuid}` (`ticket.verify`)

### 2.9 Configurable prices — `settings`

- `app/Models/Setting.php` — `key, value, type`, with `Setting::get($key, $default)`
  and `Setting::set($key, $value, $type)`; casts `boolean` / `integer` / string
- `app/Filament/Pages/ManageSettings.php` → `/admin/manage-settings`
- Already in use for `booking_payment_required`

**This table is global — it has no tenant column.** See §4.

### 2.10 Receipts and email

- `app/Http/Controllers/BookingReceiptController.php` — `success()`, `download()`
- `GET /booking/success/{booking}` and `GET /booking/receipt/{booking}` both use
  **`signed`** middleware, deliberately: a booking id is a small sequential
  integer, and an unsigned URL would let anyone walk the range and harvest every
  guest's name, email and phone. Mobile receipt links must be signed the same way.
- Mailables: `app/Mail/{BookingConfirmed,BookingDeclined,BookingCustomerConfirmation,BookingNotification,TicketReceipt,SaleReceipt}Mail.php`
- Every guest send is null-guarded (`if (! empty($booking->guest_email))`) because
  `bookings.guest_email` is nullable — phone-only bookings are normal.

### 2.11 Activity log

`app/Services/ActivityLogService.php::log(event:, description:, subject:, properties:)`.

It **types its user as `?App\Models\User`** — passing any other model throws a
TypeError that has previously aborted a login silently. Use it for order
accept / decline / overdue auditing.

---

## 3. The order and delay data model (NEW)

Every new table carries `tenant_id` (§4). Money is **kobo** (integer), matching
`tickets.amount`.

### `tenants`

```
id, name, slug (unique), logo_path, primary_colour,
enabled_departments (json), timezone, is_active, created_at, updated_at
```

### `meals`

One table, not two. Main Kitchen and Barbecue have the **same shape** — a
`department` column keeps their catalogues separate, which makes "add a third
kitchen" a data change rather than a migration.

```
id, tenant_id,
department (enum: main_kitchen | barbecue | ice_cream),
name, category, price (kobo), prep_time_minutes (default 30),
is_available (bool), sku (nullable), created_at, updated_at

UNIQUE (tenant_id, department, name)     -- makes CSV re-import idempotent
INDEX  (tenant_id, department, is_available)
```

`prep_time_minutes` is manager-editable and is what drives every countdown.

### `orders`

```
id, tenant_id,
department (enum: main_kitchen | barbecue | ice_cream),
placed_by_user_id          -- the waitress
table_id (nullable -> dining_tables.id)
guest_ref (nullable)       -- room number or name when there is no table
status (enum: pending | accepted | declined | ready | completed | cancelled)
placed_at, target_minutes, due_at,
accepted_at, ready_at, completed_at,
declined_at, decline_reason (nullable), declined_by_user_id (nullable),
total_amount (kobo),
notified_overdue_at (nullable)   -- so the overdue push fires exactly once
created_at, updated_at

INDEX (tenant_id, status, due_at)        -- the Order Board query
INDEX (tenant_id, department, status)    -- the kitchen queue query
```

**`decline_reason` is effectively mandatory:** enforce at the service layer that
`status = declined` requires a non-empty reason. A DB CHECK constraint is not
portable across this project's MySQL/SQLite split, so validate in the service and
cover it with a test — the way `PosService` already enforces `void_reason`.

### `order_items`

```
id, order_id, meal_id,
name_snapshot, unit_price (kobo), quantity,
prep_time_minutes_snapshot,
notes (nullable)           -- "no pepper"
created_at, updated_at
```

Snapshots rather than joins for name, price and prep time — a renamed or
repriced meal must not rewrite history. Same reasoning as
`referral_ticket_sales.commission_rate`.

### Notifications

Use Laravel's **existing** `notifications` table —
`database/migrations/*_create_notifications_table.php` is already present.

| Trigger | Recipients |
|---|---|
| order created | every Chef in that `department` + every Manager (same tenant) |
| order tips overdue | every Manager (same tenant) |
| order declined | the waitress who placed it + Managers |

Overdue detection is a scheduled job running every minute, selecting
`status IN (pending, accepted) AND due_at < now() AND notified_overdue_at IS NULL`,
then stamping `notified_overdue_at` inside the same transaction so a manager is
never paged twice for one order.

---

## 4. Multi-tenancy

**No existing table has a `tenant_id`.** The current state, precisely:

| Tables | Tenant concept today |
|---|---|
| `bookings`, `room`, `services`, `tickets`, `invoices`, `invoice_items`, `products`, `sales`, `sale_items`, `dining_tables`, `waiters`, `settings`, `users` | **none** — single-tenant |
| `referrals` | has its own auth guard, but no tenant |

**Documented plan — do not alter existing tables in phase 1:**

1. New `tenants` table. Every **new** Hospi Sales table gets `tenant_id`, a
   foreign key, and a compound index leading with `tenant_id`.
2. `users.tenant_id` (nullable) added when the app onboards its second tenant.
   Nullable keeps every existing Iceland admin working untouched.
3. A `BelongsToTenant` global scope on the new models, resolving the tenant from
   the authenticated Sanctum token — **never from a request parameter.** A
   client-supplied `tenant_id` is the single most likely way Beach A ends up
   reading Beach B's data.
4. Existing Iceland rows are backfilled to tenant 1 when that day comes.
5. Prices and prep times move from the global `settings` table to
   `tenant_settings (tenant_id, key, value, type)`, keeping the proven
   `Setting::get` / `Setting::set` shape.

`GET /api/me` returns tenant identity so nothing is hardcoded in the app:

```json
{
  "user":   { "id": 12, "name": "Ada", "roles": ["Waitress"] },
  "tenant": { "id": 1, "name": "Iceland Beach Resort",
              "logo_url": "https://.../logo.png", "primary_colour": "#002F61",
              "enabled_departments": ["gate_sales", "hosts", "front_desk",
                                      "ice_cream", "main_kitchen", "barbecue"],
              "timezone": "Africa/Lagos" },
  "prices": { "gate_ticket": 300000, "per_head": 200000, "swimming_per_head": 200000 }
}
```

Values are kobo — ₦3,000 is `300000`.

---

## 5. Android and iOS app architecture

### 5.1 Framework — React Native via Expo

**Decision: Expo (managed) + React Native + TypeScript.**

Justification, given Nonso knows React.js and Laravel/Filament but is new to mobile:

- One codebase ships Android **and** iOS. Flutter would mean learning Dart;
  native would mean two codebases in two languages.
- Expo keeps Xcode and Android Studio out of the daily loop — EAS Build compiles
  in the cloud, so iOS builds are possible without owning a Mac.
- React hooks, props and JSX carry over directly from the existing
  `resources/js/pages/*.jsx`.
- Expo Notifications is the least-effort route to the manager push alerts that
  PRIORITY 1 depends on. Bare React Native means configuring APNs and FCM by hand.

| Concern | Choice | Why |
|---|---|---|
| Language | TypeScript | catches payload-shape drift against the API |
| Navigation | Expo Router (file-based) | mirrors folder routing already familiar from React Router |
| Server state | TanStack Query (React Query) | polling, cache invalidation and retry for free — this drives the live board |
| Session token | `expo-secure-store` | Sanctum token in the OS keychain, not `AsyncStorage` |
| Client state | Zustand | small; avoids Redux boilerplate |
| Forms | React Hook Form + Zod | the Zod schema doubles as an API response validator |
| Push | Expo Notifications | device token registered against the user |
| Money | integer kobo everywhere, formatted at the edge | matches `tickets.amount`; never a float |

### 5.2 Backend link — an additive JSON API on the existing Laravel app

The app is a **client**. It must not reimplement bookings, invoices, payments or
roles — it calls the existing models and services.

New code goes in `routes/api.php` (additions) and `app/Http/Controllers/Api/*`.
Auth is **Laravel Sanctum** tokens, already installed (§2.1).

```
POST   /api/auth/login              email OR phone + password -> token
POST   /api/auth/logout             revoke current token
GET    /api/me                      user + tenant + roles + prices

GET    /api/meals?department=       tenant-scoped catalogue
PATCH  /api/meals/{meal}            manager only: price, prep_time_minutes
POST   /api/meals/import            manager only: CSV upload

POST   /api/orders                  waitress: create -> stamps due_at
GET    /api/orders?status=&dept=    board / queue, server-computed colour
POST   /api/orders/{order}/accept   chef
POST   /api/orders/{order}/decline  chef — reason REQUIRED
POST   /api/orders/{order}/ready    chef
POST   /api/orders/{order}/complete waitress / manager
GET    /api/orders/board            manager: RED first, then YELLOW, then GREEN

GET    /api/gate-sales              per-day / per-shift, reuses Invoice
POST   /api/gate-sales              ticket + per-head
GET    /api/dashboard/revenue       CEO: per-department revenue

POST   /api/device-tokens           register Expo push token
```

Reuse, explicitly:

- room availability → `Booking::scopeBlocking()`, **not** a new query
- booking create / confirm / reject → `ReservationService` + `BookingWorkflowService`
- payments → `PaystackService` + `BookingWorkflowService::markBookingPaid()`
- receipts → `Invoice` + `resources/views/pdf/invoice.blade.php`
- keep `throttle:` middleware on every write route, as `routes/api.php` already does

### 5.3 Timers and live updates — the server owns the clock

`due_at` is computed and stored server-side. The API returns `due_at`,
`server_time`, `seconds_remaining` and `status_colour`. The app renders a local
countdown **seeded from `server_time`**, never from `Date.now()` alone —
otherwise a waitress changing her phone clock changes the SLA.

**Recommendation: short polling with React Query.** Laravel Reverb and websockets
are **not** installed in this repo (no `laravel/reverb`, no Echo configuration),
and adding a websocket server is a second piece of unfamiliar infrastructure for
a first mobile app.

| Screen | `refetchInterval` |
|---|---|
| Manager Order Board | 10s, plus refetch on app foreground |
| Chef kitchen queue | 10s |
| Waitress order list | 30s |
| CEO dashboard | 60s |

Overdue escalation does **not** depend on any phone being awake: the scheduled
job in §3 fires the push server-side. Polling drives the visible board; the push
is the accountability guarantee. Reverb can replace polling later without
touching the data model.

### 5.4 Payments on mobile

Do **not** embed a card form — that drags PCI scope into the app.

1. The app POSTs the order or booking; the server calls
   `PaystackService::initialize()` and returns `authorization_url`.
2. The app opens that URL with `expo-web-browser` (`openAuthSessionAsync`) — a
   real in-app browser, so Paystack's own 3-D Secure and bank redirects work.
3. Paystack redirects to the **existing** `booking.payment.callback` route, which
   calls `PaystackService::verify()` server-side.
4. The browser closes; the app invalidates the React Query key and reads the new status.
5. The **existing** webhook remains the safety net for an app killed mid-payment.

Nothing new is trusted from the device, and the `BOOK-` reference prefix keeps
`PaystackWebhookController`'s dispatch working unchanged.

### 5.5 Per-tenant CSV meal import

The manager uploads from the app or admin → `POST /api/meals/import`
(`multipart/form-data`: `file`, `department`).

```csv
name,category,price_naira,prep_time_minutes
Jollof Rice & Chicken,Rice,4500,25
Suya Platter,Grill,6000,40
```

- `department` comes from the request, so Main and Barbecue stay separate sets
- `price_naira` × 100 → kobo on import
- a blank `prep_time_minutes` defaults to 30
- upsert on `(tenant_id, department, name)` so re-importing a corrected file
  updates rather than duplicates
- the whole import runs in one transaction; report row-level errors with line
  numbers and commit nothing on failure
- **never** trust a `tenant_id` column in the CSV — it comes from the token

### 5.6 Build phases

| Phase | Deliverable | Done when |
|---|---|---|
| **0** | `tenants`, `tenant_id`, `/api/auth/login`, `/api/me`, Sanctum, role gates | a Waitress token cannot read another tenant's row, proven by test |
| **1** | Welcome/setup, login, role-based home | logo and title come from `/api/me`; nothing hardcoded |
| **2** | **ORDER / DELAY ENGINE** — waitress entry, chef queue, manager board, notifications, overdue job | an order with a 5-minute target turns RED and pushes to the manager with the app closed |
| **3** | Main Kitchen + Barbecue catalogues, CSV import, manager prep-time editor | two separate catalogues; editing a prep time changes only future orders |
| **4** | Ice Cream counter | records sales, items carry prep targets, feeds the same board |
| **5** | Gate Sales | per-day and per-shift receipts via `Invoice` |
| **6** | Hosts — tables / beds / cabins via `services` | booking + invoice, same payment flow |
| **7** | Front Desk — rooms + swimming | reuses `Booking::scopeBlocking()` |
| **8** | CEO dashboard | cross-department revenue, all tenant-scoped |

Phase 2 ships before any other department. Phase 0 must be verified end to end
first — auth, tenant isolation and role isolation — because every later phase
inherits its scoping.

---

## 6. Non-negotiables

1. **Additive only.** Do not change existing routes, webhooks, `PaystackService`,
   `BookingWorkflowService`, `PosService`, or the Filament panels. The live site,
   `/admin`, `/referral` and all three Paystack webhook aliases must keep working.
2. **Tenant comes from the token, never the request.**
3. **The server owns time.** `due_at` and `status_colour` are computed server-side.
4. **A decline requires a stored reason.** Enforced in the service, covered by a test.
5. **Prices and prep times are per-tenant and manager-controlled.** Never a
   constant in the app bundle.
6. **Role isolation.** A waitress only enters orders. A chef sees only their own
   department's queue. Neither ever sees the CEO dashboard.
7. **Money is integer kobo** end to end.
8. **Payment transitions stay idempotent** — reuse `markBookingPaid()`, do not
   re-implement it.
9. Use the seeded role names (`Super Admin`, not `super_admin`) — see §2.2.

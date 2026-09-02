# PROJECT MAP
## Iceland Beach Resort — Complete Codebase Map

> Last verified: **2026-09-02** (commit `208f3f7`). Facts checked against the running app,
> not inferred from the graph alone.
> **Read this file at the start of every session before touching any code.**
>
> Produced by following [INSTRUCTIONS.md](INSTRUCTIONS.md), the portable mapping playbook —
> which contains the method only and no facts about this project, so it can be copied to any repo.

---

## 🗺️ PROJECT OVERVIEW

Public website, booking engine and back-office for **Iceland Beach Resort** (Lagos, Nigeria) at
**icelandbeach.com**. It covers four largely independent product lines that share one Laravel app
and one database:

1. **Room bookings** — public React site → API → staff review or Paystack payment → confirmation email + PDF receipt.
2. **Event tickets** — a server-rendered Blade page with two payment gateways (Paystack inline popup, TGI Pay redirect), issuing a QR code scanned at the gate.
3. **Vendor spots** — vendors apply for a pitch at an event and pay via TGI Pay.
4. **POS / back office** — Filament admin covering sales, products, inventory, waiters, invoices, referrals and activity logs.

The public site is a **standalone React SPA** (react-router + axios) served from one Blade shell
(`welcome`). It is **not Inertia** — there is no Inertia anywhere. Server-rendered Blade is used
where a page must paint on first load from a redirect (ticket purchase, booking success, vendor payment).

**Tech stack**
| Layer | Choice |
|---|---|
| Backend | Laravel 12.55, PHP 8.3 |
| Admin | Filament 3 (`/admin`) |
| Frontend | React 18 SPA (`resources/js`), Vite/Rolldown, react-router-dom, axios, lucide-react |
| Server-rendered | Blade (ticket page, booking success, vendor payment, all emails, PDFs) |
| Database | MySQL in production · SQLite locally and in tests |
| Payments | Paystack (rooms + tickets), TGI Pay (tickets + vendor spots) |
| PDF | `barryvdh/laravel-dompdf` ^3.1 |
| Auth/roles | `spatie/laravel-permission` |
| Hosting | Hostinger shared, `~/domains/icelandbeach.com/public_html/` |
| CI/CD | GitHub Actions → SSH → `git reset --hard` → `bash deploy.sh` |

**Brand:** navy `#002F61` on white.

---

## 📁 DIRECTORY STRUCTURE

```
Iceland/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/                      ← JSON endpoints for the React SPA
│   │   │   ├── BookingController      rooms list + availability, booking store, rate limits
│   │   │   ├── CatalogController      services list
│   │   │   ├── ContactController      contact form
│   │   │   └── ServiceBookingController
│   │   ├── BookingPaymentController   Paystack ROOM callback + webhook → markPaid
│   │   ├── BookingReceiptController   signed success page + PDF receipt
│   │   ├── PaymentController          ALL ticket logic (Paystack + TGI). Legacy, high risk.
│   │   ├── PaystackWebhookController  single webhook entry, dispatches by "BOOK-" prefix
│   │   └── VendorPaymentController    vendor spot payments via TGI
│   ├── Models/                       23 models — see DATABASE TABLES
│   ├── Mail/                         7 mailables — see EMAIL SYSTEM
│   ├── Services/
│   │   ├── BookingWorkflowService     ★ canonical confirm/reject/markPaid. Everything routes here.
│   │   ├── ReservationService         public booking creation + legacy room-record writes
│   │   ├── PaystackService            init / verify / webhook signature
│   │   ├── PosService                 point-of-sale
│   │   ├── ActivityLogService         audit trail
│   │   └── TicketPaymentRefreshService
│   ├── Filament/
│   │   ├── Resources/                14 resources (Booking, Room, Ticket, Sale, Invoice, …)
│   │   ├── Widgets/                  9 widgets — 8 are $isDiscovered=false, see GOTCHAS
│   │   └── Pages/                    Dashboard, ManageSettings, ScanTicket
│   ├── Observers/                    AuditObserver, TicketObserver
│   ├── Policies/                     ActivityLog, Invoice, Role, User
│   └── Livewire/                     VendorSpotMap
│
├── resources/
│   ├── js/
│   │   ├── app.jsx                   ★ SPA root: router + the Booking form component
│   │   └── pages/                    HomePage, AboutPage, RoomsPage, ServicesPage,
│   │                                 ServiceBookingPage, EventsPage, GalleryPage, ContactPage
│   └── views/
│       ├── welcome.blade.php         the SPA shell every React route renders
│       ├── ticket/purchase.blade.php ★ ticket page (gateway cards + Paystack inline JS)
│       ├── booking/                  success.blade.php, receipt-pdf.blade.php
│       ├── mail/                     7 email templates
│       ├── pdf/invoice.blade.php     DomPDF invoice
│       └── filament/                 custom admin views (booking calendar wrapper)
│
├── database/
│   ├── migrations/                   ~40 migrations
│   └── seeders/                      RoleSeeder (idempotent, runs on every deploy), DatabaseSeeder
│
├── routes/
│   ├── web.php                       public pages, tickets, payments, webhooks
│   └── api.php                       /api/* for the SPA — all throttled
│
├── tests/Feature/                    18 tests. BookingWorkflowServiceTest + BookingReceiptTest
│                                     cover the money paths.
├── .github/workflows/deploy.yml      CI/CD
├── deploy.sh                         post-pull deploy steps
├── .htaccess                         ★ docroot bridge → public/  (see GOTCHAS #3)
└── graphify-out/                     graph.json, GRAPH_REPORT.md, graph.html
```

> `.claude/`, `.agents/`, `.cursor/`, `.github/skills/` are committed **agent tooling**, not
> application code (~2,000 files). Ignore them when reasoning about the app.

---

## 🗄️ DATABASE TABLES

| Table | Key columns | Relationships | Notes |
|---|---|---|---|
| `bookings` | id, room_id, guest_name, guest_email, guest_phone, room_type, check_in, check_out, status, payment_status, payment_reference, email_opt_in, rejection_reason | belongsTo `room` | **Canonical booking record.** status: `pending`, `confirmed`, `rejected`, `pending_payment`, `payment_failed`. Only `confirmed` blocks availability. |
| `room` | id, room_name, room_category, room_price, room_image, main_image, gallery_images, is_booked, customer_name, email, start_date, end_date, total_price, mailsent | hasMany bookings (via room_id) | Singular table name. **Production copy has no created_at/updated_at** → `Room::$timestamps = false`. SoftDeletes. `is_booked`: `no`/`booked`/`rejected`/`expired`. |
| `tickets` | id, uuid, name, email, phone, ticket_type, quantity, amount, order_ref, payment_gateway, payment_ref, paid, checked_in, qr_code_path, scan_count, checked_in_at, pickup_location, referral_code | hasMany ticket_scan_logs | `amount` stored in **kobo**. uuid drives the QR URL. |
| `ticket_scan_logs` | id, ticket_id, user_id, status | belongsTo ticket, user | Gate scan audit |
| `services` / `service_bookings` | service_name, service_price, signin, signout, no_of_people | — | Spa/experience bookings |
| `vendor_spots` | id, event_id, name, price, pos_x, pos_y, status | hasMany vendor_requests | pos_x/pos_y drive the Livewire map |
| `vendor_requests` | id, vendor_spot_id, name, email, address, expected_to_sell, order_ref, status | belongsTo vendor_spot | |
| `sales` / `sale_items` / `void_logs` | waiter_id, table_id, total_amount, payment_method, sale_date | belongsTo waiter, table | POS. SoftDeletes throughout. |
| `products` | name, category, price, stock_quantity, low_stock_threshold | — | Inventory |
| `tables` / `waiters` | table_name, assigned_waiter_id, status / full_name, pin_hash, role | | POS floor |
| `invoices` / `invoice_items` | invoice_number, customer_name, total_amount, total_in_words, bank_* | hasMany items | `total_in_words` via NairaAmountFormatter |
| `referrals` / `referral_ticket_sales` | code, commission_rate, user_id / ticket_id, commission_amount | belongsTo user, ticket | Affiliate ticket sales |
| `membership` | full_name, email, mplan, member_status, start_date, end_date | — | Legacy schema, snake-case oddities |
| `settings` | key, value, type | — | `Setting::get()`. Drives `booking_payment_required`. |
| `activity_logs` | user_id, event, subject_type, subject_id, description, properties, ip_address | morphTo subject | Written by ActivityLogService + AuditObserver |
| `users`, `roles`, `permissions`, `model_has_roles`, … | | | spatie/laravel-permission |

---

## 🔀 CORE FLOWS

### Room booking — payment OFF (`booking_payment_required` = false)
1. React `Booking` component in `resources/js/app.jsx` POSTs `/api/bookings`.
2. `Api\BookingController@store` validates, then applies **two rate limits**:
   - exact duplicate (same email + room + dates, incl. `pending_payment`) → 422
   - one active booking per email (`pending`/`confirmed`) → 422 *(this check is skipped when payment is on)*
3. `ReservationService::requestBooking()` — checks `Booking::scopeBlocking` overlap, creates the
   booking as `pending`, stages guest details onto the `room` row **without** setting `is_booked`.
4. Sends `BookingNotificationMail` (admins) + `BookingCustomerConfirmationMail` (guest, "request received").
5. Booking waits in `PendingBookingsWidget` for staff.

### Room booking — payment ON (Paystack)
1. Same validation and duplicate check; the one-active-booking rule is **not** applied.
2. Booking created, then flipped to `pending_payment` with `payment_reference`; guest emails suppressed.
3. Amount = `room_price × nights × 100` (**kobo**). Zero price → 422, booking deleted.
4. `PaystackService::initialize()` → guest redirected to Paystack.
5. Return: `GET /booking/payment/callback?reference=…` → verify → `markPaid()`.
   Safety net: `POST /webhooks/paystack` → `PaystackWebhookController::creditBooking()`.
6. Both call **`BookingWorkflowService::markBookingPaid()`** — idempotent, latches
   `payment_status = paid` first, then `confirmBooking()`.
7. **Payment auto-confirms.** Status → `confirmed`, room held, activity logged,
   `BookingConfirmedMail` sent carrying a signed **Download receipt** link.
8. Guest redirected to a **signed** `/booking/success/{booking}`.

### Admin confirm / reject
- `PendingBookingsWidget` (row + bulk) and `BookingResource` both call `BookingWorkflowService`.
- **Confirm** → status `confirmed`, mirrors hold onto `room` (`is_booked='booked'` + dates), logs, sends `BookingConfirmedMail`.
- **Reject** → status `rejected` + reason, releases the room (`is_booked='no'`), logs, sends `BookingDeclinedMail`.
- `ConfirmedBookingsWidget`'s Cancel button calls the same `rejectBooking()`.
- Bulk actions isolate each record in try/catch and report one summary; gated to Super Admin / Manager.

### Ticket purchase (Paystack + TGI Pay)
- `GET /ticket` → `resources/views/ticket/purchase.blade.php`. One form, two gateway cards
  (logos at `images/tgi.jpg`, `images/paystack.png`), button always reads **"Pay Now"**.
- **Paystack:** inline popup (`js.paystack.co/v1/inline.js`) → JS gets a reference →
  POST `/verify-payment` → `PaymentController@paystackVerify` recalculates the amount **server-side**.
- **TGI Pay:** form POSTs `/initiate-tgipay` → redirect → return on `ANY /tgipay/callback`
  (CSRF-exempt per route) → webhook `POST /webhooks/tgi`.
- On success: ticket row, QR to `storage/app/public/qrcodes/{uuid}.svg`, `TicketReceiptMail`.
- Gate scan: `GET /ticket/verify/{uuid}` → `ScanTicket` Filament page logs to `ticket_scan_logs`.

---

## 🛣️ ROUTES REFERENCE

| Method | URI | Handler | Name |
|---|---|---|---|
| GET | `/`, `/about`, `/rooms`, `/services`, `/event`, `/gallery`, `/membership`, `/contact`, `/book` | `view('welcome')` — React SPA | — |
| GET | `/api/rooms` | `Api\BookingController@rooms` | throttle:60,1 |
| POST | `/api/bookings` | `Api\BookingController@store` | throttle:10,1 |
| POST | `/api/contact` | `Api\ContactController@store` | throttle:10,1 |
| GET | `/api/services` | `Api\CatalogController@services` | throttle:60,1 |
| POST | `/api/service-bookings` | `Api\ServiceBookingController@store` | throttle:10,1 |
| GET | `/booking/payment/callback` | `BookingPaymentController@callback` | `booking.payment.callback` |
| GET | `/booking/success/{booking}` | `BookingReceiptController@success` | `booking.success` **signed** |
| GET | `/booking/receipt/{booking}` | `BookingReceiptController@download` | `booking.receipt` **signed** |
| POST | `/webhooks/paystack` · `/webhooks/paystack/booking` · `/webhooks/paystack/tickets` | `PaystackWebhookController@handle` | CSRF-exempt |
| GET | `/ticket` | `PaymentController@showTicketForm` | `ticket.form` |
| POST | `/ticket/calculate-price` | `PaymentController@calculatePrice` | CSRF-exempt |
| GET | `/ticket/verify/{uuid}` | `PaymentController@verifyEntry` | `ticket.verify` |
| GET | `/ticket/{uuid}/success` | `PaymentController@success` | `ticket.success` |
| POST | `/verify-payment` | `PaymentController@paystackVerify` | — |
| POST | `/initiate-tgipay` | `PaymentController@initiateTgiPay` | — |
| ANY | `/tgipay/callback` | `PaymentController@tgiPayCallback` | CSRF-exempt |
| POST | `/webhooks/tgi` | `PaymentController@tgiPayWebhook` | CSRF-exempt |
| GET | `/vendor-spots` | `view('vendor-spots')` | |
| GET/POST | `/vendor-payment/{vendorRequest}` · `/pay` · `ANY /callback` | `VendorPaymentController` | CSRF-exempt on callback |
| GET | `/invoices/{id}/view` | closure → `pdf.invoice` | `invoices.view` |
| — | `/admin/*` | Filament panel | |

> Ordering matters in `routes/web.php`: specific ticket sub-routes are declared **before**
> `GET /ticket`, and the React `Route::view` entries sit last. There is **no catch-all**.

---

## 💳 PAYMENT GATEWAYS

### Paystack — rooms
- Env: `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`, `PAYSTACK_PAYMENT_URL`
- `PaystackService` · `BookingPaymentController` · `BookingWorkflowService::markBookingPaid()`
- Redirect checkout. Amount in **kobo**. Callback resolves the booking by `reference` query param
  (one dashboard URL serves every transaction).

### Paystack — tickets
- Same keys, entirely separate code path: **inline popup**, not redirect.
- `PaymentController@paystackVerify` recomputes the amount server-side from `ticket_type`.

### TGI Pay — tickets and vendor spots
- Env: `TGIPAY_MERCHANT_ID`, `TGIPAY_INTEGRATION_KEY`, `TGIPAY_SECRET`, `TGIPAY_BASE_URL`, `TGIPAY_CALLBACK_URL`
- `PaymentController` (tickets) · `VendorPaymentController` (spots)
- Redirect flow, returns on `ANY /tgipay/callback`. **Amount in naira, not kobo.**

---

## 👥 ROLES & PERMISSIONS

Seeded by `RoleSeeder` (idempotent, runs every deploy): **Super Admin, Admin, Manager,
Front Desk, Scanner, Waiter, Referral**.

Booking confirm/reject — row actions, bulk actions and the Cancel button — is gated to
**Super Admin** and **Manager** via `hasAnyRole`. Note `PendingBookingsWidget::operatorCanManage()`
is deliberately **static**: `$this` is not reliably bound inside Filament bulk-action closures, and
an unbound call silently returned false, hiding the row checkboxes.

Policies: `ActivityLogPolicy`, `InvoicePolicy`, `RolePolicy`, `UserPolicy`.

---

## 📧 EMAIL SYSTEM

| Mailable | Trigger | Recipient | View |
|---|---|---|---|
| `BookingNotificationMail` | Public booking request created | Admin list (`config/resort.php`) | `mail/booking-notification` |
| `BookingCustomerConfirmationMail` | Public request created, payment **off** | Guest | `mail/booking-customer-confirmation` |
| `BookingConfirmedMail` | Staff confirm **or** payment received | Guest | `mail/booking-confirmed` |
| `BookingDeclinedMail` | Staff reject / cancel | Guest | `mail/booking-declined` |
| `TicketReceiptMail` | Ticket paid (either gateway) | Guest | `mail/ticket-receipt` |
| `SaleReceiptMail` | POS sale | Customer | `mail/sale-receipt` |
| `VendorApprovalMail` | Vendor request approved | Vendor | `mail/vendor-approval` |

- `BookingConfirmedMail` and `BookingDeclinedMail` accept **`Room|Booking`** — two code paths reach them.
- `BookingConfirmedMail` adds a signed **Download receipt** button only when `payment_status === 'paid'`.
- Logos are **base64 data URIs**, never URLs: `APP_URL` on a private host is unreachable from Gmail.
  `iceland-logo-email.png` (14 KB) is used over `iceland-logo.png` (67 KB) to stay under Gmail's ~102 KB clipping threshold.
- Templates are **table-based with inline styles only** — no flexbox, no `<style>` blocks. Outlook's Word renderer supports none of it.
- SMTP: `MAIL_*` env, from `booking@icelandbeach.com` (Hostinger).

---

## ⚠️ KNOWN GOTCHAS — READ BEFORE TOUCHING ANYTHING

1. **`room` table has no timestamps in production.** The migration declares them and local SQLite
   *has* them, which is why this only ever breaks on icelandbeach.com as
   `Unknown column 'updated_at'`. Fixed once at the model: `Room::$timestamps = false`
   (`app/Models/Room.php`). **Never remove that line.** Per-call-site `$room->timestamps = false`
   in `ReservationService` is now redundant but harmless.

2. **Mass-assignment on `Booking`.** `payment_reference` and `payment_status` must stay in
   `$fillable`. They were missing, so `update()` silently dropped them, the reference was never
   persisted, and **no Paystack payment could ever be matched back to its booking.**

3. **`.htaccess` IS tracked and IS the source of truth.** It is the two-line docroot bridge
   (`RewriteRule ^(.*)$ public/$1`). It used to be the old PHP site's 5 KB rule set which routed at
   `laravel-app/public/*` and 404'd every Laravel URL through `ErrorDocument /soon.php` —
   `git reset --hard` restored it on every deploy and killed the site until `deploy.sh` overwrote it.
   `deploy.sh` **no longer touches it**. Do not reintroduce a `cat > .htaccess` step.

4. **Only `confirmed` bookings block availability** — `Booking::BLOCKING_STATUSES` /
   `scopeBlocking()`. Overlap is half-open, so same-day turnover stays bookable.
   *Known wart:* `Api\BookingController@rooms` also treats `room.is_booked === 'booked'` as
   unavailable **ignoring dates**, so a confirmed stay marks that room sold out for all dates in the UI.

5. **Paystack = kobo. TGI Pay = naira.** Never mix them.

6. **`PaymentController` is legacy and load-bearing.** All ticket + TGI logic lives there and is in
   production use. Do not refactor it, and do not merge the ticket Paystack flow with the room one.

7. **Payment auto-confirms a booking.** Paid bookings skip the staff review queue entirely
   (`markBookingPaid` → `confirmBooking`). If staff should review paid bookings, that is the one
   place to change.

8. **Both payment entry points must stay routed through `BookingWorkflowService`.**
   `BookingPaymentController` and `PaystackWebhookController` previously held separate copies of
   the credit logic and had already drifted — one emailed the guest, the other did not.

9. **DomPDF supports no flexbox, no grid, and ignores gradients.** Use tables. Font subsetting is
   enabled per-document in `BookingReceiptController` — without it the receipt is 1.16 MB instead of 67 KB,
   because DejaVu Sans (needed for ₦) embeds whole.

10. **Success/receipt routes are `signed`.** Booking ids are small sequential integers; unsigned
    these would let anyone enumerate and harvest guest names, emails and phones.

11. **8 of 9 Filament widgets set `$isDiscovered = false`** and are registered explicitly on the
    Dashboard / BookingCalendar page. Removing the flag makes them render twice, or panel-wide.
    Only `MostBookedRoomsWidget` is auto-discovered.

12. **SQLite enforces enums as CHECK constraints.** The payment-fields migration widened
    `bookings.status` for MySQL only; a follow-up migration widens it on SQLite, or tests cannot
    store `pending_payment`.

13. **`storage:link` cannot run on Hostinger** — `exec()` and `symlink()` are disabled.
    `deploy.sh` uses `ln -sfn` directly.

14. **`APP_NAME` must be quoted** in `.env` — unquoted breaks dotenv parsing and every artisan command.

15. **Do not touch** the ticket/TGI flow, the referral system, or existing role guards unless the
    task is explicitly about them.

---

## 🚀 DEPLOYMENT

**Push to `main` → GitHub Actions → SSH → `git reset --hard origin/main` → `bash deploy.sh`.**

`deploy.sh` steps:
1. `composer install --no-dev --optimize-autoloader` (tracked `vendor/composer/*` is overwritten by the reset)
2. `php artisan migrate --force`
3. `php artisan db:seed --class=RoleSeeder --force`
4. `npm ci --omit=dev && npm run build` (falls back to the committed `public/build`)
5. `ln -sfn storage/app/public public/storage`, `mkdir -p storage/app/public/qrcodes`, `chmod -R 775`
6. `optimize:clear` → `config:cache` → `route:cache` → `view:cache`

There is deliberately **no `.htaccess` step** (gotcha #3) and no `queue:restart` (`QUEUE_CONNECTION=sync`).

**Server:** Hostinger shared · path `~/domains/icelandbeach.com/public_html/` · PHP 8.3 · `exec()` disabled.

`route:cache` works — all closures in `routes/web.php` are cacheable. Verify before shipping route changes.

---

## 🔍 HOW TO USE THIS MAP IN NEW SESSIONS

Start every session with:

```
Read PROJECT_MAP.md before touching anything.
Use graphify query/explain/path to look things up instead of reading files.
```

```bash
graphify query "how does X work"          # BFS over the graph, returns nodes + file:line
graphify explain "BookingWorkflowService" # a node, its degree, and every edge
graphify path "BookingPaymentController" "BookingConfirmedMail"
```

`explain` needs an unambiguous name — pass the repo-relative path or node id when a label
appears in several files (`Booking` matches the model, a test, and the React component).

**Rebuilding the graph — important.** Do **not** run `graphify update .` at the repo root. The repo
carries ~2,000 committed agent-tooling files in `.claude/`, `.agents/`, `.cursor/` and
`.github/skills/`; a root build yields 34,466 nodes of which only ~613 are application code, and a
44 MB `graph.json`. Build against application source only:

```bash
# stage app source, then build there and copy graphify-out back
mkdir -p /tmp/gstage/resources
cp -r app routes database config tests bootstrap /tmp/gstage/
cp -r resources/js resources/views /tmp/gstage/resources/
cp composer.json package.json deploy.sh /tmp/gstage/
cd /tmp/gstage && graphify update .
cp graphify-out/{graph.json,GRAPH_REPORT.md,graph.html,manifest.json} <repo>/graphify-out/
```

That yields **1,220 nodes / 2,376 edges / 138 communities** — 97% EXTRACTED, 3% INFERRED.

---

## 📊 GRAPH SUMMARY

Full detail in **`graphify-out/GRAPH_REPORT.md`** (15 KB). Interactive view: open
`graphify-out/graph.html` in a browser (gitignored — regenerate locally).

**Community hubs** (highest-degree nodes, i.e. what the codebase is actually organised around):
`Ticket` · `Booking` · `BookingResource` · `BookingWorkflowService` · `Room` · `User` · `Sale` ·
`Invoice` · `Referral` · `Product` · `Setting` · `NairaAmountFormatter` · `app.jsx` ·
`AdminPanelProvider` · `RolePolicy`

`BookingWorkflowService` has **degree 16** — imported by `BookingResource`, `PendingBookingsWidget`,
`ConfirmedBookingsWidget`, `BookingPaymentController`, `PaystackWebhookController` and its test,
exposing `confirmBooking()`, `rejectBooking()`, `markBookingPaid()`, `confirm()`, `reject()`.
It is the single chokepoint for every booking state change — **change behaviour there, not at call sites.**

---
*Generated with Graphify 0.9.53. Update with the scoped rebuild above, then revise this file
and its `Last verified` line. Method: [INSTRUCTIONS.md](INSTRUCTIONS.md).*

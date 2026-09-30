=== PROMPT FOR AGENT 2 (Build) ===

Build "Hospi Sales", a white-label multi-tenant hospitality operations app for
Android and iOS, plus the additive JSON API it talks to inside an existing
Laravel 12 + Filament 3 codebase (live at icelandbeach.com).

--------------------------------------------------------------------
READ THESE FIRST, BEFORE WRITING ANY CODE
--------------------------------------------------------------------
1. HOSPI_SALES_BRIEF.md in the repository root. It maps every existing file,
   table, column, route and service you must reuse. It is accurate — it was read
   out of the codebase, not assumed.
2. The Agent 3 / Google Stitch screen designs.

--------------------------------------------------------------------
PRIORITY 1 — THE CORE FEATURE. BUILD THIS FIRST AND MAKE IT CORRECT.
--------------------------------------------------------------------
This app exists to kill meal-order delays and make them accountable. A guest
orders food, it arrives late, and the manager says "I wasn't aware there was a
delay." Your job is to remove that excuse.

The loop, end to end:
  - A waitress enters an order. The SERVER stamps due_at = placed_at +
    target_minutes, where target_minutes is copied from the meal's
    prep_time_minutes AT ORDER TIME (fallback 30). For a multi-item order, use
    MAX(prep_time_minutes) across the items.
  - The relevant kitchen (Main / Barbecue / Ice Cream) AND every manager are
    notified of the new order.
  - The chef accepts, declines with a REQUIRED stored reason, or marks ready.
  - The manager sees a traffic-light board:
        GREEN  completed_at IS NOT NULL AND completed_at <= due_at
        AMBER  completed_at IS NULL     AND now() <= due_at
        RED    completed_at IS NULL     AND now() >  due_at   (overdue)
               ... and also RED when status = declined
        GREY   completed_at IS NOT NULL AND completed_at >  due_at (late)
  - When an order tips overdue, a scheduled job pushes to every manager.

Non-obvious requirements that matter:
  - The SERVER is the source of truth for time. Return due_at, server_time,
    seconds_remaining and a computed status_colour on every order payload. The
    app seeds its countdown from server_time, never from Date.now() alone — a
    staff member changing the phone clock must not change the SLA.
  - Snapshot target_minutes on the order and prep_time_minutes on each order
    item. A manager raising a prep time later must NOT retroactively make past
    late orders look on-time. This mirrors how
    referral_ticket_sales.commission_rate is already snapshotted in
    app/Observers/TicketObserver.php.
  - Stamp notified_overdue_at when the overdue push fires, inside the same
    transaction, so a manager is never paged twice for one order.
  - The overdue push must fire server-side, with no app open on any device.

--------------------------------------------------------------------
FIXED STACK — DO NOT SUBSTITUTE
--------------------------------------------------------------------
Mobile:  Expo (managed) + React Native + TypeScript
         Expo Router (file-based navigation)
         TanStack Query (React Query) for all server state
         expo-secure-store for the Sanctum token (NOT AsyncStorage)
         Zustand for client state
         React Hook Form + Zod
         Expo Notifications for push
         expo-web-browser for the payment flow
Backend: the EXISTING Laravel 12 app. Laravel Sanctum tokens — already
         installed; personal_access_tokens migration and an auth:sanctum route
         are both already present in routes/api.php. Do not install another
         auth package.
Money:   integer kobo everywhere, formatted only at the UI edge. Never a float.
         ₦3,000 is 300000. This matches the existing tickets.amount column.

--------------------------------------------------------------------
REUSE THIS, DO NOT REBUILD IT
--------------------------------------------------------------------
Payments
  app/Services/PaystackService.php
    isConfigured(), initialize($email,$amountKobo,$reference,$callbackUrl,$metadata),
    verify($reference), verifyWebhookSignature($payload,$signature) [HMAC-SHA512],
    generateReference('BOOK')
  app/Http/Controllers/BookingPaymentController.php      callback(), webhook()
  app/Http/Controllers/PaystackWebhookController.php     one URL, dispatches by
    reference prefix: BOOK- => booking, otherwise ticket
  app/Services/BookingWorkflowService.php::markBookingPaid()
    ALREADY IDEMPOTENT — early-returns when payment_status === 'paid', because
    the callback and the webhook routinely both fire. Enter through this method.
  Existing routes, keep working untouched:
    GET  /booking/payment/callback    booking.payment.callback
    POST /webhooks/paystack          paystack.webhook
    POST /webhooks/paystack/booking  booking.payment.webhook
    POST /webhooks/paystack/tickets  ticket.payment.webhook

Bookings (Front Desk department)
  app/Models/Booking.php — table bookings. BLOCKING_STATUSES = ['confirmed'];
    scopeBlocking($start,$end). ONLY a confirmed booking holds a room. Use this
    scope for availability — do not write a new query.
  app/Models/Room.php — the table is `room` (SINGULAR, legacy). Validation in
    this repo reads exists:room,id.
  app/Services/ReservationService.php      requestBooking(), assertNotOverlapping()
  app/Services/BookingWorkflowService.php  confirmBooking(), rejectBooking()
  bookings.guest_email is NULLABLE — phone-only bookings are normal. Null-guard
    every guest email send, as the existing code does.

Services (Hosts department: tables / beds / cabins)
  app/Models/ServiceBooking.php — the table is `services`, NOT service_bookings.
    Columns: service_name, service_category, service_price, service_image,
    main_image, gallery_images, description, capacity, is_booked, customers_name,
    customers_email, signin, signout, no_of_people, deleted_at (SoftDeletes)
  app/Http/Controllers/Api/CatalogController.php     GET /api/services

Invoices / receipts (Gate Sales department)
  app/Models/Invoice.php       invoice_number, invoice_date, customer_name,
    customer_address, telephone, total_amount, total_in_words, bank_name,
    bank_account_number, bank_account_name
  app/Models/InvoiceItem.php   invoice_id, description, quantity, unit_price, sub_total
  resources/views/pdf/invoice.blade.php, route GET /invoices/{id}/view
  Receipt links: BookingReceiptController's routes use `signed` middleware
    deliberately — a booking id is a small sequential integer and an unsigned URL
    would let anyone enumerate guest details. Sign mobile receipt links the same way.

Existing POS — study before designing orders
  products      name, category, price, stock_quantity, low_stock_threshold
  sales         waiter_id, table_id, total_amount, payment_method, sale_date
  sale_items    sale_id, product_id, quantity, price, is_voided
  dining_tables table_name, assigned_waiter_id, status
  waiters       full_name, username, password_hash, pin_hash, role, is_active
  app/Services/PosService.php::processSale() already implements a table
    assignment guard, a 60-second duplicate-sale window, stock checks, a
    mandatory void_reason, and one atomic transaction. Copy that discipline.
  These tables carry NO status, NO accept/decline, NO timing. The order engine is
    genuinely new — but reuse dining_tables for table refs and the products shape
    for the meal catalogue.

Roles — spatie/laravel-permission 7.1
  database/seeders/RoleSeeder.php seeds: Super Admin, Admin, Manager, Front Desk,
    Waiter, Scanner, Referral. NO permissions are defined — role NAMES only.
  app/Models/User.php::canAccessPanel()
  app/Filament/Concerns/RestrictsFrontDeskAndScanner.php
  Second-panel precedent: app/Providers/Filament/ReferralPanelProvider.php
    (->id/->path/->authGuard) with its guard in config/auth.php.
  WARNING: app/Policies/UserPolicy.php tests hasRole('super_admin') but the
    seeder creates 'Super Admin'. That is a known bug. Use the seeded names.

Settings / configurable prices
  app/Models/Setting.php — key, value, type with Setting::get()/set()
  app/Filament/Pages/ManageSettings.php  => /admin/manage-settings
  This table is GLOBAL with no tenant column. Introduce tenant_settings
    (tenant_id, key, value, type) keeping the same get/set shape.

Notifications
  The notifications table migration ALREADY EXISTS. Use Laravel notifications.

Activity log
  app/Services/ActivityLogService.php::log(event:, description:, subject:, properties:)
  It TYPES its user as ?App\Models\User. Passing another model throws a
  TypeError that has previously aborted a login silently. Use it for order
  accept / decline / overdue auditing.

--------------------------------------------------------------------
NEW SCHEMA (see HOSPI_SALES_BRIEF.md §3 for full columns and indexes)
--------------------------------------------------------------------
tenants      name, slug, logo_path, primary_colour, enabled_departments (json),
             timezone, is_active
meals        tenant_id, department (main_kitchen|barbecue|ice_cream), name,
             category, price (kobo), prep_time_minutes (default 30), is_available
             UNIQUE (tenant_id, department, name)  -- makes CSV re-import idempotent
orders       tenant_id, department, placed_by_user_id, table_id, guest_ref,
             status (pending|accepted|declined|ready|completed|cancelled),
             placed_at, target_minutes, due_at, accepted_at, ready_at,
             completed_at, declined_at, decline_reason, declined_by_user_id,
             total_amount (kobo), notified_overdue_at
             INDEX (tenant_id, status, due_at), INDEX (tenant_id, department, status)
order_items  order_id, meal_id, name_snapshot, unit_price, quantity,
             prep_time_minutes_snapshot, notes

ONE meals table with a department column, not separate meals and barbecue_meals
tables — that way adding a third kitchen is a data change, not a migration.

--------------------------------------------------------------------
API — ADDITIVE ONLY
--------------------------------------------------------------------
POST   /api/auth/login              email OR phone + password -> Sanctum token
POST   /api/auth/logout             revoke current token
GET    /api/me                      user + roles + tenant identity + prices
GET    /api/meals?department=
PATCH  /api/meals/{meal}            manager only: price, prep_time_minutes
POST   /api/meals/import            manager only: CSV
POST   /api/orders                  waitress; server stamps due_at
GET    /api/orders?status=&dept=
GET    /api/orders/board            manager; RED first, then AMBER, then GREEN
POST   /api/orders/{order}/accept   chef
POST   /api/orders/{order}/decline  chef; reason REQUIRED
POST   /api/orders/{order}/ready    chef
POST   /api/orders/{order}/complete waitress / manager
GET    /api/gate-sales              per-day / per-shift; reuses Invoice
POST   /api/gate-sales
GET    /api/dashboard/revenue       CEO
POST   /api/device-tokens           register Expo push token

Keep throttle: middleware on every write route, as routes/api.php already does.

/api/me response shape (nothing about the business is hardcoded in the app):
{
  "user":   { "id": 12, "name": "Ada", "roles": ["Waitress"] },
  "tenant": { "id": 1, "name": "...", "logo_url": "...",
              "primary_colour": "#002F61",
              "enabled_departments": ["gate_sales","hosts","front_desk",
                                      "ice_cream","main_kitchen","barbecue"],
              "timezone": "Africa/Lagos" },
  "prices": { "gate_ticket": 300000, "per_head": 200000, "swimming_per_head": 200000 }
}

--------------------------------------------------------------------
LIVE UPDATES
--------------------------------------------------------------------
Short polling with React Query. Laravel Reverb and websockets are NOT installed
in this repo and must not be added for v1 — a websocket server is a second piece
of unfamiliar infrastructure on a first mobile app.
  Manager Order Board  10s, plus refetch on app foreground
  Chef kitchen queue   10s
  Waitress order list  30s
  CEO dashboard        60s
The overdue push is server-side and does not depend on polling. Reverb can
replace polling later without touching the data model.

--------------------------------------------------------------------
PAYMENTS ON MOBILE
--------------------------------------------------------------------
Do NOT embed a card form; that drags PCI scope into the app.
  1. App POSTs the order/booking; server calls PaystackService::initialize() and
     returns authorization_url.
  2. App opens it with expo-web-browser openAuthSessionAsync.
  3. Paystack redirects to the EXISTING booking.payment.callback, which calls
     PaystackService::verify() server-side.
  4. Browser closes; app invalidates the React Query key and reads the status.
  5. The EXISTING webhook stays the safety net for an app killed mid-payment.
Keep the BOOK- reference prefix so PaystackWebhookController's dispatch keeps
working unchanged.

--------------------------------------------------------------------
CSV MEAL IMPORT
--------------------------------------------------------------------
POST /api/meals/import, multipart/form-data: file, department.
  name,category,price_naira,prep_time_minutes
  Jollof Rice & Chicken,Rice,4500,25
- department comes from the REQUEST, so Main and Barbecue stay separate sets
- price_naira x 100 => kobo
- blank prep_time_minutes => 30
- upsert on (tenant_id, department, name) so re-importing a corrected file
  updates rather than duplicates
- one transaction; report row errors with line numbers and commit nothing on failure
- NEVER trust a tenant_id column in the CSV — it comes from the token

--------------------------------------------------------------------
GUARDRAILS — NON-NEGOTIABLE
--------------------------------------------------------------------
1. ADDITIVE ONLY. Do not modify existing routes, webhooks, PaystackService,
   BookingWorkflowService, ReservationService, PosService, or the Filament
   panels. The public site, /admin, /referral and all three Paystack webhook
   aliases must keep working. Run the existing test suite and keep it green.
2. TENANT COMES FROM THE TOKEN, NEVER THE REQUEST. A client-supplied tenant_id
   is the single most likely way Beach A ends up reading Beach B's data. Apply a
   BelongsToTenant global scope to every new model. Every new table gets
   tenant_id and a compound index leading with tenant_id.
3. THE SERVER OWNS TIME. due_at and status_colour are computed server-side and
   returned to the client. Never trust the device clock.
4. A DECLINE REQUIRES A STORED REASON. Enforce it in the service layer (a DB
   CHECK constraint is not portable across this project's MySQL/SQLite split) and
   cover it with a test, the way PosService enforces void_reason.
5. NEVER HARDCODE business identity, prices, departments or prep times. All of it
   comes from /api/me and tenant_settings. No constant in the app bundle.
6. ROLE ISOLATION. A waitress can only create orders and see her own. A chef sees
   only their own department's queue. Neither ever reaches the CEO dashboard.
   Enforce server-side; hiding a tab is not access control.
7. MONEY IS INTEGER KOBO end to end.
8. PAYMENT TRANSITIONS STAY IDEMPOTENT — reuse markBookingPaid().
9. Use the seeded role names ('Super Admin', not 'super_admin').

--------------------------------------------------------------------
BUILD ORDER — AND THE GATE YOU MUST PASS
--------------------------------------------------------------------
Phase 0  tenants, tenant_id, /api/auth/login, /api/me, Sanctum, role gates
Phase 1  Welcome/setup, login, role-based home
Phase 2  THE ORDER / DELAY ENGINE — waitress entry, chef queue, manager board,
         notifications, overdue scheduled job
Phase 3  Main Kitchen + Barbecue catalogues, CSV import, prep-time editor
Phase 4  Ice Cream counter
Phase 5  Gate Sales (per-day / per-shift receipts via Invoice)
Phase 6  Hosts (tables/beds/cabins via the services table)
Phase 7  Front Desk (rooms + swimming, via Booking::scopeBlocking())
Phase 8  CEO dashboard

STOP after Phase 2 and prove all of the following with automated tests before
building any other department:
  - a token for tenant A cannot read, write or even enumerate a tenant B row
  - a waitress token is refused on every chef and manager endpoint
  - a chef token cannot see another department's queue
  - an order placed with a 5-minute target reports AMBER before its due_at and
    RED after, computed from due_at and not from any client input
  - the overdue job pushes to the manager exactly once, with no app running
  - a decline with an empty reason is rejected and nothing is written
  - changing a meal's prep_time_minutes does not alter any existing order's
    due_at or target_minutes
Do not start Phase 3 until every one of those passes.

You are Agent 1, the architect. You are working inside the existing Iceland
Beach Resort codebase (Laravel 12 + Filament 3 + ReactJS, live at
icelandbeach.com).

Your job has TWO parts:
  PART A — Scan and deeply map this codebase and design the architecture for a
           new mobile app called "Hospi Sales."
  PART B — Using that understanding, WRITE TWO PROMPTS as your final output:
             • a prompt for AGENT 3 (Google Stitch) to design the app's UI
             • a prompt for AGENT 2 (a coding agent) to build the app
You do NOT write app code yourself. You produce documentation + those two prompts.

════════════════════════════════════════════════════════
PRIORITY 1 — THE CORE PURPOSE OF THIS APP (put this first in everything you write)
════════════════════════════════════════════════════════
Hospi Sales exists primarily to KILL MEAL-ORDER DELAYS and make them
ACCOUNTABLE. The real-world problem: a guest orders food, it arrives late, and
the manager says "I wasn't aware there was a delay." This app removes that
excuse by putting a visible countdown on every order and escalating to the
manager automatically.

Mechanics that MUST be designed as the centerpiece:
  - Every meal order starts a COUNTDOWN. Default target = 30 minutes, but this
    is CONFIGURABLE PER MEAL (some meals are faster/slower to prepare), and the
    per-meal target must be editable by the manager. Store a prep_time_minutes
    on each meal; the order timer uses that meal's value (fallback 30).
  - TRAFFIC-LIGHT STATUS in the Manager (and CEO) view, computed from the timer:
        GREEN  = completed on time
        YELLOW = pending / in progress, still within its target time
        RED    = OVERDUE (past its target time) — the manager sees red for every
                 overdue order, loudly and unmissably.
  - The MANAGER GETS NOTIFICATIONS: on every new order, and especially when any
    order tips into RED/overdue, routed to the RELEVANT DEPARTMENT (Main Kitchen
    vs Barbecue vs Ice Cream, etc.). The relevant department is also notified of
    its own incoming orders.
  - A WAITRESS role simply ENTERS ORDERS (guest's order → into the system fast);
    that action starts the countdown and notifies the correct kitchen + manager.
Design the data model, API, and UI around this delay-tracking loop first;
everything else serves it.

════════════════════════════════════════════════════════
ABOUT THE PRODUCT (honor in everything you write)
════════════════════════════════════════════════════════
"Hospi Sales" is a hospitality operations app extending this resort system to
Android and iOS. It is WHITE-LABEL / MULTI-TENANT: NOT tied to Iceland Beach —
licensed/rented to other beaches and similar businesses. Each business is a
tenant with its own logo, business name/title, enabled departments, staff,
prices, and fully ISOLATED data (Beach A must never see Beach B's data). First
run shows a WELCOME / SETUP screen with the business's logo and title.

The developer (Nonso) knows React.js and Laravel/Filament but is NEW to mobile.
Favor the path with the least unfamiliar tooling.

ROLES: Chef, Waitress, Host, Manager, CEO.
  - Waitress: enters guest orders (starts the order countdown); minimal UI.
  - Chef: works a kitchen queue — accept / decline-with-reason; marks ready.
  - Host: tables/beds/cabins.
  - Manager: sees the traffic-light board of ALL orders, gets notifications,
    controls prices and per-meal prep times.
  - CEO: oversees all department dashboards + revenue across the business.

6 DEPARTMENTS:
  1. Gate Sales — ticket ₦3000, per-head charge ₦2000. Track sales, downloadable
     receipt PER DAY and filterable PER SHIFT. MANAGER controls prices. Reuse the
     invoicing pattern at /admin/invoices.
  2. Hosts — control tables, beds, cabins. See /services. Booking + invoice from
     website OR app, same booking-payment-with-receipt flow.
  3. Front Desk — rooms + swimming booking, ₦2000/head. Extension of the existing
     website booking system. CEO oversees revenue.
  4. Ice Cream (counter attendant) — record/document ice cream sold; own table.
     (Its counter items also carry a prep target and feed the same order board.)
  5. Main Kitchen (Chef) — orders: accept / decline-with-reason; e-commerce style
     product grid; meals database imported via CSV; each meal has an editable
     prep_time_minutes driving the countdown.
  6. Barbecue Kitchen (Chef) — same as Main Kitchen; SEPARATE meals database via
     CSV; own editable prep times.

════════════════════════════════════════════════════════
PART A — SCAN, MAP, AND DECIDE THE ARCHITECTURE
════════════════════════════════════════════════════════
1. Map the codebase first:
   - If graphify exists: `/graphify .` then read graphify-out/GRAPH_REPORT.md
   - Read INSTRUCTIONS.md and PROJECT_MAP.md if present.
   - `find app/Models app/Http/Controllers app/Filament/Resources app/Services -name "*.php" | sort`
   - `cat routes/web.php`; `php artisan route:list`
   - List DB tables:
     `php artisan tinker --execute="foreach(\DB::select('SHOW TABLES') as \$t){echo array_values((array)\$t)[0].PHP_EOL;}"`

2. Document each existing system the app will REUSE (exact files, tables,
   columns, routes): INVOICES (/admin/invoices), BOOKINGS (rooms/spaces +
   availability logic), SERVICES (/services — tables/beds/cabins), PAYMENTS
   (PaystackService, booking payment controller, webhook/callback, receipts),
   ROLES & PERMISSIONS, EMAIL/PDF RECEIPTS.

3. Design the ORDER + DELAY-TRACKING data model (the heart of the app):
   - orders (tenant_id, department, waitress/staff id, table/guest ref, status
     [pending/accepted/declined/completed], placed_at, target_minutes,
     due_at, completed_at, decline_reason nullable)
   - order_items linking to meals (each meal has prep_time_minutes, editable)
   - meals / barbecue_meals (per-tenant, CSV-importable, price, prep_time_minutes)
   - how RED/YELLOW/GREEN is computed (from due_at vs now vs completed_at)
   - how notifications fire (new order → kitchen + manager; overdue → manager)

4. EXPLICITLY DECIDE AND LIST THE MOBILE APP ARCHITECTURE in a dedicated
   "Android & iOS App Architecture" section, stating and justifying:
   - Framework: recommend React Native with EXPO (one codebase → Android + iOS;
     closest to Nonso's React.js). Language TypeScript. Name the navigation lib,
     data-fetching lib (React Query), and session store.
   - Backend link: a NEW JSON API on the EXISTING Laravel app (routes/api.php +
     app/Http/Controllers/Api/...), Laravel Sanctum tokens. The app is a CLIENT;
     it must NOT reimplement bookings/invoices/payments/roles — it calls existing
     models/services.
   - REAL-TIME / TIMERS: how the countdown + overdue escalation works — server is
     the source of truth (due_at), app polls or subscribes; recommend the
     simplest reliable option (short polling with React Query, or Laravel Reverb
     if already present) and how manager notifications are delivered (push via
     Expo Notifications and/or in-app).
   - Multi-tenancy: tenants/businesses table + tenant_id on every app-owned table;
     every API request scoped to the caller's tenant; /api/me returns tenant
     identity (logo, name, enabled departments, prices). State which existing
     tables already carry this and which need it added (document only, don't alter).
   - Payments on mobile: Paystack in a React Native context reusing the existing
     server-side verify + webhook.
   - Per-tenant CSV meal import (Main vs Barbecue kept separate), including the
     editable prep_time_minutes column.
   - Build phases: Foundation (auth + tenant + roles) → the ORDER/DELAY engine
     (Waitress enter-order + kitchen queue + manager traffic-light board +
     notifications) FIRST among departments, then the rest, CEO dashboard last.
   Save all of this as HOSPI_SALES_BRIEF.md.

════════════════════════════════════════════════════════
PART B — WRITE THE TWO PROMPTS (your real deliverable)
════════════════════════════════════════════════════════
Ground both prompts in the REAL files/tables/routes you found. Name them so the
downstream agents reuse, not rebuild. Put PRIORITY 1 (delay tracking) at the top
of BOTH prompts.

PROMPT FOR AGENT 3 (Google Stitch — UI design):
  - Welcome/setup (configurable logo + business title), login (email OR phone +
    password), role-based home. Design BOTH a Manager/CEO view AND focused staff
    views.
  - CENTERPIECE SCREEN: the Manager "Order Board" — a live traffic-light list of
    all orders: RED overdue (top, loud), YELLOW pending-in-time, GREEN completed;
    each card shows the meal(s), the department, elapsed vs target time, and a
    countdown. Design the overdue state to be unmissable.
  - WAITRESS screen: dead-simple "take an order" flow (pick table/guest → pick
    meals → submit) that visibly starts a timer.
  - CHEF screen: kitchen queue with Accept / Decline-with-reason and Mark Ready,
    each order showing its countdown; e-commerce style meal grid.
  - The other department screens (Gate Sales with per-day/per-shift receipt,
    Hosts tables/beds/cabins, Front Desk rooms+swimming, Ice Cream counter),
    a receipt/invoice screen, and a MANAGER settings screen for prices AND
    per-meal prep-time (target minutes) editing.
  - Design system: deep navy #002F61 + white; rounded cards; generous spacing;
    LARGE tap targets (fast, one-handed, on the floor); bottom-tab nav; status
    colors GREEN=completed/paid, YELLOW=pending, RED=overdue/declined; light
    mode; mobile portrait; components that map cleanly to React Native.
  - Do NOT hardcode "Iceland Beach" — logo/name are per-tenant.

PROMPT FOR AGENT 2 (build the app):
  - Lead with PRIORITY 1: the meal-delay accountability loop (per-meal editable
    target time, countdown, RED/YELLOW/GREEN board, manager + department
    notifications, overdue escalation) is the core feature — build it first
    among the departments and make it correct.
  - State the fixed stack from Part A (Expo + React Native + TypeScript + Sanctum
    API + React Query + multi-tenant + Expo Notifications).
  - Tell it to READ HOSPI_SALES_BRIEF.md and the Stitch designs FIRST.
  - Give the "REUSE THIS, DON'T REBUILD" list with exact existing files/tables/routes.
  - Guardrails: multi-tenant scoping on every table + request; never hardcode
    business identity/prices/departments/prep-times; API is ADDITIVE (don't break
    the site/admin/webhooks); server (due_at) is the source of truth for timers,
    not the phone clock; kitchen decline REQUIRES a stored reason; prices AND
    per-meal prep times are manager-controlled per tenant; role isolation (waitress
    only enters orders; chef only sees their kitchen queue; a chef never sees the
    CEO dashboard; no tenant ever sees another).
  - Phased build order; require verifying auth + tenant + role AND the order-timer
    loop end-to-end before building the remaining departments.

════════════════════════════════════════════════════════
YOUR FINAL OUTPUT
════════════════════════════════════════════════════════
1. HOSPI_SALES_BRIEF.md (the map + the order/delay data model + the Android/iOS
   architecture decisions)
2. A clearly separated "=== PROMPT FOR AGENT 3 (Google Stitch) ===" block
3. A clearly separated "=== PROMPT FOR AGENT 2 (Build) ===" block
Be specific and factual — real paths, real columns, real routes. No placeholders.
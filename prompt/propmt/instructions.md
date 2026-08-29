You are working inside a Laravel 12 + Filament 3 + ReactJS (Inertia or standalone) project for Iceland Beach Resort.

=== STEP 0: MAP THE PROJECT FIRST ===
Before touching any code:
1. Run: `node -e "const fs=require('fs'),path=require('path');function walk(d,depth=0){if(depth>4)return;try{fs.readdirSync(d).forEach(f=>{const p=path.join(d,f);if(['.git','node_modules','vendor','storage','bootstrap/cache'].some(x=>p.includes(x)))return;const s=fs.statSync(p);console.log(' '.repeat(depth*2)+f+(s.isDirectory()?'/':''));if(s.isDirectory())walk(p,depth+1);})}catch(e){}}walk('.');" > project-map.txt && cat project-map.txt`
2. Read package.json, composer.json, and routes/web.php to understand the architecture.
3. Identify: all Filament Resources, all React JSX pages, all Models, all Migrations, the Booking model relationships, and the existing Roles/Permissions setup (Spatie or custom).
4. DO NOT modify anything until you have mapped and read the relevant files for each task below.

=== TASK 1: Fix Booking Availability Logic ===
PROBLEM: Rooms show as "unavailable" in rooms.jsx when bookings are merely "pending" (not yet approved by staff in Filament). This is wrong.

RULES:
- A room should only show as UNAVAILABLE if a booking has status = "approved" (or "confirmed") AND the dates overlap with the selected check-in/check-out.
- A "pending" booking must NEVER block a room from being shown as available.
- Users can book any available room freely. Staff review happens after the fact.

STEPS:
1. Find the API endpoint or Inertia controller that serves room availability to rooms.jsx. Likely in app/Http/Controllers/ — look for RoomController or BookingController.
2. Find the query that checks for conflicting bookings. It will look something like:
   `Booking::where('room_id', $id)->where('status', '!=', 'cancelled')->whereBetween(...)`
3. Change it to ONLY exclude approved/confirmed bookings:
   `Booking::where('room_id', $id)->whereIn('status', ['approved', 'confirmed'])->whereBetween(...)`
4. Check rooms.jsx for any front-end logic that also marks rooms unavailable — fix it to match the same rule.
5. Check the Filament BookingResource to make sure the calendar widget reflects this correctly too.
6. DO NOT touch existing roles, permissions, middleware, or policies — only the availability query.

=== TASK 2: Email Newsletter Opt-in ===
PROBLEM: Need to collect emails for marketing campaigns with an explicit opt-in checkbox.

STEPS:
1. Find all booking/reservation forms in the React JSX pages (rooms.jsx, the booking modal/form component).
2. Add a checkbox field: `[ ] Subscribe to receive news, promos, and events from Iceland Beach Resort via email.`
3. In the Booking model/migration: add a boolean column `email_opt_in` defaulting to false.
   - Run: `php artisan make:migration add_email_opt_in_to_bookings_table`
   - Add: `$table->boolean('email_opt_in')->default(false);`
4. In the BookingController store() method: save `email_opt_in` from the request (validate as boolean).
5. In the Filament BookingResource: add `email_opt_in` as a visible column in the table and a toggle in the form so staff can see who opted in.
6. Optional: Create an `email_subscribers` table for standalone signups (if there's a newsletter section on the homepage separate from bookings), with columns: id, email, name (nullable), opted_in_at, created_at.

=== TASK 3A: Promo Code → Referral Code System ===
PROBLEM: The ticket page (PHP, at /ticket) has a "Promo Code" field. Convert this to a "Referral Code" system with a new Referral role that can track successful ticket sales.

IMPORTANT: The /ticket page PHP files — DO NOT break the existing payment flow. Only add the referral tracking layer on top.

STEPS:
1. Create a migration for a `referrals` table:
2. Create a migration for `referral_ticket_sales` table:
3. Create a `Referral` model with relationships to User and ReferralTicketSales.
4. In the ticket payment success callback/webhook (find it — likely a POST route handling Paystack or Flutterwave success), add:
   - Look up the referral code used during checkout (store it in session or a pending_referrals temp column on the order)
   - On SUCCESSFUL payment only: create a ReferralTicketSale record with status = 'successful'
   - On failed payment: do NOT create any record
5. Create a new Filament Role called "Referral" (using your existing roles system — do not recreate it).
6. Create a Filament Resource: `ReferralResource` with:
   - Panel for admins: full CRUD — create referral account (name, email, password, auto-generates unique code), view all referrals and their stats
   - Panel for referral users: read-only view showing ONLY their own referral code and a table of their successful sales (count + total amount)
7. In the Filament user creation form for referrals, auto-generate the unique referral code on save.
8. On the ticket PHP page: change the label from "Promo Code (Optional)" to "Referral Code (Optional)" and ensure the code is passed through to the order and stored for the payment callback to use.

=== TASK 3B: Fix Duplicate Widgets on /admin/bookings ===
PROBLEM: The bookings page shows 4 widgets — 2 booking calendars and 2 pending booking widgets. Need exactly 1 of each.

STEPS:
1. Find the Filament BookingResource — look in app/Filament/Resources/BookingResource.php
2. Find where widgets are registered — check:
   - `getWidgets()` method in BookingResource
   - `BookingResource/Pages/ListBookings.php` → `getHeaderWidgets()` and `getFooterWidgets()`
   - Any separate `Pages/ManageBookings.php` that might also register widgets
3. Ensure widgets are declared ONLY ONCE — in one place (preferably ListBookings.php → getHeaderWidgets())
4. The final result should be: [BookingCalendarWidget, PendingBookingsWidget] — one each, no duplicates.
5. Check app/Filament/Widgets/ for any auto-discovered widgets that might be registering globally AND in the resource — if so, exclude them from global discovery using `protected static bool $isDiscovered = false;` on the widget class, so they only appear when explicitly added to the resource page.

=== TASK 4: Fix Rooms.jsx and Spaces.jsx Desktop Max-Width ===
PROBLEM: rooms.jsx and spaces.jsx content doesn't use the full max-width on desktop — it's fine on mobile but constrained on desktop.

STEPS:
1. Open resources/js/Pages/rooms.jsx (or wherever it lives)
2. Open resources/js/Pages/spaces.jsx
3. Look for the outer wrapper div class. It likely has a Tailwind class like `max-w-xl`, `max-w-2xl`, `max-w-3xl`, `container`, or a fixed `px-4` with no `max-w-screen-xl` equivalent.
4. Fix the wrapper to use full desktop width. Pattern to use:
```jsx
   <div className="w-full max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">
```
   Or if the project uses a Layout component, check the Layout for a constrained inner wrapper and fix it there so it applies globally — but ONLY if rooms and spaces are the ones affected (don't break other pages).
5. Verify the rooms grid (cards layout) also stretches — it should be something like:
```jsx
   <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
```
6. Check if there's a shared `<PageLayout>` or `<AppLayout>` component with a hardcoded max-width — if so, consider making the max-width a prop so rooms and spaces can override it.

=== FINAL CHECKS ===
After completing all tasks:
1. Run `php artisan migrate` for any new migrations.
2. Run `php artisan filament:upgrade` if Filament caches need clearing.
3. Run `php artisan optimize:clear` and `npm run build`.
4. Test: book a room → confirm it still shows available while pending.
5. Test: check the /admin/bookings page — confirm 1 calendar widget, 1 pending widget.
6. Test: desktop view of /rooms and /spaces — confirm full-width layout.
7. DO NOT change any existing auth middleware, role guards, or payment logic beyond what's specified above.
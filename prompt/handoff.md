# Iceland Beach Laravel + React Migration Handoff

## Project and constraints

- Workspace: `C:\Users\Tenstrings Music Ins\Documents\Iceland`
- Laravel application: `laravel-app`
- Legacy PHP source: project root (`index.php`, `rooms.php`, `services.php`, `event.php`, etc.). It remains the visual and content source of truth. Do **not** delete it yet.
- Keep Laravel + Filament as the admin application.
- Do **not** modify `laravel-app/app/Models/Ticket.php`, ticket payments, ticket scan/verify routes, or vendor-payment routes.
- Never commit `.env`, production database credentials, SMTP passwords, or payment keys. The current `.env.example` should be reviewed before publishing because it must not contain real credentials.

## Current stack

- Laravel 12.55.1 (not yet Laravel 13; do not force the upgrade while Filament 3 compatibility is unresolved).
- React 19 + React Router 7 + Vite 8.
- Tailwind v4, Axios, Lucide React, Framer Motion, Three/R3F, GSAP, Swiper.
- React entry: `laravel-app/resources/js/app.jsx`
- SPA Blade shell: `laravel-app/resources/views/welcome.blade.php`
- Shared styling: `laravel-app/resources/css/app.css`
- React page folder: `laravel-app/resources/js/pages/`

## What the user asked for and what was completed

### Legacy public-site migration

The user asked to migrate the legacy public site to React in `laravel-app` while preserving Laravel/Filament administration.

- Home page was reworked from legacy `index.php` content.
- Shared header/footer were added in `resources/js/app.jsx`.
- Header uses `/images/iceland-logo.png`; booking CTA uses beach blue.
- Legacy image/video assets are available under `public/legacy-images/` and `public/legacy-video/`.
- Legacy source files have not been removed.

### Rooms and room booking

The user requested the Rooms page use actual local database rooms, and that choosing a room should carry through to booking.

- Dedicated component: `resources/js/pages/RoomsPage.jsx`.
- `GET /api/rooms` returns room id, name, category, price, image, and availability.
- Rooms use categories, filtering, pricing, selection cards, and responsive layout.
- `Select room` links to `/book?room={id}`.
- Booking page reads the query string and pre-selects the room; it shows a selected-room summary and a Change room link.
- `POST /api/bookings` uses `ReservationService` for date overlap validation and room/customer email delivery.
- Admin booking recipient comes from `BOOKING_PRIMARY_EMAIL` in `.env`, with optional additional booking recipient variables in `config/resort.php`.

### Spaces / services and service booking (latest request)

The user asked to convert the legacy spaces/services and event pages to React, place them in the correct `.jsx` folder, clean routing, and make services bookable in the same UI style as Rooms.

Completed:

- `resources/js/pages/ServicesPage.jsx`
  - Powers `/services`.
  - Fetches actual service catalogue data from `GET /api/services`.
  - Uses the Rooms-style summary panel, filters, responsive cards, pricing, and selection flow.
  - `Select space` links to `/book-service?service={id}`.
- `resources/js/pages/ServiceBookingPage.jsx`
  - Powers `/book-service`.
  - Pre-selects the selected service from the URL and collects dates, guest count, name, email, and phone.
- `resources/js/pages/EventsPage.jsx`
  - Powers `/event`.
  - Converts the legacy event calendar into React cards using the legacy event titles/copy and site assets.
- `resources/js/app.jsx`
  - Imports the dedicated components and contains clean routes for `/services`, `/event`, and `/book-service`.
- `routes/api.php`
  - Adds `POST /api/service-bookings`.
- `app/Http/Controllers/Api/ServiceBookingController.php`
  - Validates the service booking request.
  - Sends an admin notification to the configured booking recipient and a confirmation email to the customer.
  - Intentionally does **not** mark a service catalogue row unavailable. The legacy `services` table is a catalogue, not a bookings ledger; permanently marking a service booked would wrongly hide it after the first request.
- Mail views added:
  - `resources/views/mail/service-booking-notification.blade.php`
  - `resources/views/mail/service-booking-confirmation.blade.php`

No live email was sent by the agent. Test SMTP by submitting one controlled local booking.

### Other pages

React routes exist for `/about`, `/gallery`, `/membership`, and `/contact`, but they still need a page-by-page visual/functional fidelity pass against their legacy PHP sources before calling the migration complete. Contact submission must also be verified against `POST /api/contact`.

## Routing

`routes/web.php` must keep server-rendered ticket, payment and vendor routes before React routes.

React public routes:

- `/`
- `/about`
- `/rooms`
- `/services`
- `/event`
- `/gallery`
- `/membership`
- `/contact`
- `/book`
- `/book-service`

API routes in `routes/api.php`:

- `GET /api/rooms`
- `POST /api/bookings`
- `GET /api/services`
- `POST /api/service-bookings`
- `POST /api/contact`
- `GET /api/user` (Sanctum)

## Local data and environment

A production SQL dump supplied by the user was imported locally through `database/seeders/ProductionSqlCatalogSeeder.php`.

- Local SQLite contains 14 rooms and 35 services from that dump.
- The seeder also copies room assets to `public/legacy-images/rooms`.
- The seeder currently references the original local SQL-dump location and is not portable without changing that path or supplying an equivalent dump.

For local development, use SQLite rather than production MySQL:

```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Use `.env` for local values only. Keep the production environment separate. Production booking email requires a valid SMTP configuration and:

```env
BOOKING_PRIMARY_EMAIL=your-booking-inbox@example.com
```

After changing environment values:

```powershell
php artisan optimize:clear
```

## Start locally after restarting the PC

Open PowerShell and run:

```powershell
cd 'C:\Users\Tenstrings Music Ins\Documents\Iceland\laravel-app'
```

First confirm dependencies and the local database are ready (normally needed only after a fresh clone, deleting `vendor`, deleting `node_modules`, or changing migrations):

```powershell
composer install
npm.cmd install
New-Item -ItemType File -Path database\database.sqlite -Force
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ProductionSqlCatalogSeeder
php artisan optimize:clear
npm.cmd run build
```

For normal daily startup after a PC restart, run these in **two separate PowerShell windows**:

```powershell
# Window 1: Laravel server
cd 'C:\Users\Tenstrings Music Ins\Documents\Iceland\laravel-app'
php artisan serve --host=127.0.0.1 --port=8001
```

```powershell
# Window 2: Vite hot reload during frontend work
cd 'C:\Users\Tenstrings Music Ins\Documents\Iceland\laravel-app'
npm.cmd run dev
```

Then open `http://127.0.0.1:8001`.

If only testing the already-built frontend, `npm.cmd run build` is enough and Window 2 is optional. Stop either process with `Ctrl+C`.

## Checks already run

- `php -l app/Http/Controllers/Api/ServiceBookingController.php` passed.
- `php artisan route:list --path=api/service-bookings` confirmed the new route.
- `npm.cmd run build` passed after the Spaces/Event updates.
- Earlier room booking workflow test passed:

```powershell
php artisan test --filter=BookingWorkflowServiceTest
```

## Recommended next work

1. Test a local room booking and space booking with safe test email addresses; verify the configured inbox and customer confirmations.
2. Create a proper separate `service_reservations` ledger/table if service-date conflict management and admin history are required. The current flow deliberately matches the legacy email-request model.
3. Finish exact legacy-to-React fidelity work for About, Gallery, Membership, and Contact.
4. Verify the contact form actually posts to `/api/contact` and emails correctly.
5. Test Filament, ticket purchase/payment, and scan routes without changing `Ticket.php`.
6. Do not remove legacy PHP until the user approves a complete local regression check and backup plan.
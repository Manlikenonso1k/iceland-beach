# 🏖️ Iceland Beach Resort
<div align="center">

![Iceland Beach Resort Banner](https://icelandbeach.com/static/images/img%20(1).png)

</div>

> A luxury beachfront escape offering serene views, sandy shorelines, and a vibrant coastal atmosphere in Lagos, Nigeria.

## 📋 Overview

Iceland Beach Resort is a modern, responsive web application showcasing a premium beachfront destination. The platform provides visitors with an immersive experience through stunning visuals, comprehensive resort information, and an intuitive booking interface.

It now includes a **full event ticketing system** — customers can purchase tickets online, pay via Paystack or TGI Titan, receive a receipt email with a QR code, and be checked in at the gate by an admin scanning the QR.

## ✨ Key Features

- **🎥 Video Hero Section**: Captivating full-screen video background showcasing the resort's ambiance
- **📱 Fully Responsive Design**: Optimized viewing experience across all devices
- **🖼️ Interactive Gallery**: Dynamic image gallery highlighting resort amenities
- **🎟️ Event Ticketing System**: Full purchase → payment → QR receipt → gate check-in flow
- **💳 Dual Payment Gateways**: Paystack (inline popup) + TGI Titan (hosted redirect)
- **📧 Automated Email Receipts**: QR code embedded in receipt email on payment success
- **🤝 Referral Programme**: Partner codes at checkout, credited only on successful payment, with a self-service partner view
- **📬 Newsletter Opt-in**: Explicit, unticked consent captured on the booking form
- **✅ Staff Booking Approval**: Pending requests never hold a room — only a confirmed booking does
- **🛠️ Admin Dashboard**: Filament 3 admin panel for order management and QR check-in
- **🌌 WebGL Aurora**: Custom Three.js shader layer, code-split and paused off-screen
- **🔒 Secure Architecture**: UUID-based URLs, server-side payment verification, CSRF-safe webhooks
- **🚀 Automated Deployment**: CI/CD pipeline for instant updates to production

## 🛠️ Technologies Used

### Frontend (Public Site)
- **React 19 + React Router 7 + Vite 8**
- **Tailwind CSS v4**: Utility-first styling for modernized components
- **Framer Motion & GSAP**: Advanced animations and interactions
- **Three.js**: Custom GLSL aurora shader (lazy-loaded into its own chunk)
- **Lucide React & Swiper**: Iconography and carousels
- **Axios**: API communication with the Laravel backend

### Backend – Admin, Ticketing & API
- **Laravel 12**: Full-stack PHP framework serving as API and Admin panel
- **Filament 3**: Admin panel with TicketResource, room/service management, filters, check-in actions
- **SQLite**: Database (production-ready, no MySQL needed)
- **simplesoftwareio/simple-qrcode**: SVG QR code generation

### Payment Gateways
- **Paystack**: Inline JS popup → server-side verification
- **TGI Titan (TGIPAY)**: Server-to-server redirect flow via `integration-key` header

### DevOps & Deployment
- **GitHub Actions**: Automated CI/CD workflow
- **SSH Deployment**: Secure file transfer to production server
- **Hostinger**: Linux-based shared hosting

## 📁 Project Structure

```
iceland-beach/
├── app/                  # Laravel 12 backend & API logic
│   ├── Http/Controllers/ # API controllers (Rooms, Bookings, Services)
│   ├── Models/           # Eloquent models
│   ├── Observers/        # TicketObserver — referral attribution on payment
│   ├── Services/         # ReservationService, payment & workflow services
│   └── Filament/         # Filament v3 admin resources
├── config/               # Application configuration
├── database/             # SQLite DB and seeders
├── public/               # Static assets (images, legacy assets, index.php)
├── resources/
│   ├── css/              # Tailwind CSS and global styles
│   ├── js/               # React SPA entry and routing (app.jsx)
│   │   ├── components/   # Reusable UI components
│   │   ├── pages/        # Page components (Home, Rooms, Services)
│   └── views/            # Blade templates (SPA shell, emails, receipts)
├── routes/
│   ├── api.php           # API endpoints (React integration)
│   └── web.php           # Admin routes and SPA fallback
├── .env                  # Environment config
├── artisan               # Laravel CLI tool
├── package.json          # Node dependencies for React + Vite
└── composer.json         # PHP dependencies
```

### 📚 Further documentation

| Document | Covers |
|---|---|
| **[RESULT.md](RESULT.md)** | Full technical documentation of the ticketing system |
| **[FILAMENT_UI.md](FILAMENT_UI.md)** | How the Filament admin UI is customised — render hooks, DOM reference, pitfalls |
| **[context.md](context.md)** | Working context: design system, current state, known issues, next steps |

## 🚀 Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git
- A local web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Manlikenonso1k/iceland-beach.git
   cd iceland-beach
   ```

2. **Install Laravel dependencies**
   ```bash
   cd laravel-app
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Set up environment**
   ```bash
   nano .env   # add your payment keys + mail config
   ```

4. **Run migrations and seed**
   ```bash
   php artisan migrate
   php artisan db:seed     # creates the admin account — see below
   ```

5. **Link storage** (for QR codes)
   ```bash
   php artisan storage:link
   mkdir -p storage/app/public/qrcodes
   ```

6. **Serve the Laravel app**
   ```bash
   php artisan serve
   ```

7. **Serve the PHP legacy site** (separate terminal)
   ```bash
   cd ..   # back to project root
   php -S localhost:8000
   ```

### 🔑 Local admin login

The `AdminUserSeeder` (run as part of `db:seed`) creates the admin account:

| | |
|---|---|
| **URL** | http://127.0.0.1:8000/admin/login |
| **Email** | `admin@local.test` |
| **Password** | `password` |

The account is granted **Super Admin** by the seeder. That role assignment
matters — `User::canAccessPanel()` gates the panel on role, so an account
without one can authenticate and still be refused entry.

Re-create or reset it at any time (idempotent, safe to re-run):

```bash
php artisan db:seed --class=AdminUserSeeder
```

> ⚠️ **Local only.** `password` is a development convenience. Override it
> anywhere that is not your machine by setting these before seeding:
> ```bash
> ADMIN_EMAIL=you@example.com
> ADMIN_PASSWORD=<a-strong-password>
> ```
> To change the password on an existing install:
> ```bash
> php artisan tinker --execute="\App\Models\User::where('email','admin@local.test')->update(['password' => Hash::make('new-password')]);"
> ```

## 🔄 Deployment (Production)

This project uses **GitHub Actions** for automated deployment. Every push to `main` triggers the CI/CD pipeline.

### After `git pull` on server — run these commands:

```bash
cd domains/icelandbeach.com/public_html/laravel-app

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run any new migrations
php artisan migrate --force

# Sync roles (adds the Referral role introduced with the referral programme).
# Idempotent — findOrCreate, safe to re-run on every deploy.
php artisan db:seed --class=RoleSeeder --force

# Fix storage symlink (exec() disabled on this host — use ln directly)
ln -sf /home/u519226541/domains/icelandbeach.com/public_html/laravel-app/storage/app/public \
        /home/u519226541/domains/icelandbeach.com/public_html/laravel-app/public/storage

# Ensure QR folder exists
mkdir -p storage/app/public/qrcodes
chmod -R 775 storage bootstrap/cache

# Re-cache for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> ⚠️ `php artisan storage:link` fails on this Hostinger server (`exec()` is disabled). Always use the `ln -sf` command above instead.

## 🆕 Recent Updates

### Booking workflow, referrals & admin fixes — August 2026

**Pending bookings no longer hold a room.** The `bookings` table is now the
canonical reservation record, so staff approval and room availability are
finally the same system.

- `bookings` gained `room_id` (FK) and `check_out` — it previously stored only a
  `room_type` string and a single `check_in`, so it could not be joined to a room
  or tested for a date overlap.
- `Booking::scopeBlocking()` defines the rule in one place: **only `confirmed`
  bookings hold a room.** `pending` and `rejected` never do.
- A public booking creates a **pending** row and deliberately leaves
  `room.is_booked = 'no'` — which is exactly what `PendingBookingsWidget`
  already treats as awaiting review. Confirming in Filament is what actually
  holds the room; rejecting releases it.
- Fixed on the front end: `RoomsPage` treated *any* `is_booked` value other than
  `'no'` as unavailable, so **`rejected` and `expired` bookings were blocking
  rooms**.
- `GET /api/rooms` now accepts optional `check_in` / `check_out` and returns an
  `is_available` flag for that exact range.

**Newsletter opt-in.** The booking form has an explicit, unticked consent box —
`bookings.email_opt_in` defaults to `false`, so consent is never implied. Staff
see who opted in as a column and a toggle in the Filament booking record.

**Referral programme.** Ticket buyers can enter a partner's code at checkout and
the partner is credited once the payment actually succeeds.

- New tables: `referrals` (partner + unique code) and `referral_ticket_sales`
  (one row per credited ticket); `tickets` gained `referral_code`.
- A ticket can reach `paid = true` from four places — Paystack verify, the TGI
  callback, the TGI webhook, and the background refresh service. Attribution
  therefore lives in **`App\Observers\TicketObserver`**, not in the payment
  code, so no path can be missed and the payment flow is untouched.
- A `unique` index on `referral_ticket_sales.ticket_id` is what prevents double
  credit on a repeated callback — not caller discipline.
- Failed or pending payments are never credited. Unknown and deactivated codes
  are ignored silently and never block a purchase.
- New **Referral** role. Partners sign in to `/admin` and see only their own code
  and sales — scoping is applied in `getEloquentQuery()`, so a partner cannot
  reach another's record by editing the URL. Sales are read-only.
- The ticket page's "Promo Code" field is relabelled **"Referral Code"**. The
  input keeps its `promo_code` name so the existing discount and pricing
  endpoint still work; the referral layer sits on top.

**Admin fixes**

- `/admin/bookings` showed **4 widgets** (two calendars, two pending lists).
  `booking-calendar.blade.php` was calling `<x-filament-widgets::widgets>`
  *inside* `<x-filament-panels::page>`, which already renders header widgets.
  Now 1 of each.
- `BookingResource` declared `$model = Room::class` while its whole table was
  written against `Booking`'s columns — so list columns rendered empty and
  **Confirm/Reject were permanently invisible** (`$record->status` is always null
  on a Room). Now bound to `Booking`.
- Rooms and Spaces are full-bleed on desktop; the shared `.room-selection-page`
  cap was removed.

> ⚠️ `BookingResource`'s form now edits **booking** fields rather than room
> fields. Room price, category and image are managed at `/admin/rooms`.

### Geothermal-coast redesign — August 2026

A visual overhaul of the public site and admin login, plus several
correctness and performance fixes found along the way.

**Design system**

A single palette and motion language now runs across the site, defined as CSS
custom properties in `resources/css/app.css`:

| Token | Value | Use |
|---|---|---|
| `--ib-basalt` | `#0b0f0e` | Dark ground |
| `--ib-glacier` | `#f4f7f6` | Light text |
| `--ib-teal` | `#6fb8bc` | Focus rings, active states |
| `--ib-ember` | `#e2924a` | Primary CTAs |

Type is **Fraunces** (serif headings) + **Hanken Grotesk** (UI). Motion uses
`cubic-bezier(0.16, 1, 0.3, 1)` at 0.8–1.6s, with scroll reveals via Framer
Motion `whileInView`. Everything respects `prefers-reduced-motion`.

**Pages redesigned**

- **Header / Footer** — frosted glass header that solidifies on scroll; footer on dark basalt with an aurora accent
- **Homepage** — every post-hero section restyled with scroll reveals and glass cards *(the scroll-expansion hero was deliberately left untouched)*
- **About / Contact** — extracted from inline definitions in `app.jsx` into `resources/js/pages/AboutPage.jsx` and `ContactPage.jsx`
- **Admin login** — full-bleed resort photography with a dark glass card — see **[FILAMENT_UI.md](FILAMENT_UI.md)**

**New components**

- `components/AuroraCanvas.jsx` — WebGL aurora: a 5-octave fbm-noise GLSL shader driving drifting curtains over a fog bank. Pauses via `IntersectionObserver` when off-screen, caps DPR at 1.5, renders one static frame under reduced motion, and falls back to a CSS gradient without WebGL.
- `components/AuroraLayer.jsx` — lazy wrapper so Three.js is code-split rather than shipped in the main bundle.

**Fixes**

- **Mobile layout blowout (320px)** — carousel rails are flex rows of fixed-width cards inside grid items. With the default `min-width: auto` they refused to shrink, forcing the document to **1027px** at a 320px viewport. Because the header is `position: fixed`, it inherited that width and pushed the hamburger to `x=987` — off-screen and unreachable. Fixed with `min-width: 0` on the rail wrappers so their own `overflow-x: auto` engages.
- **Hero video** — was `preload="auto"`, pulling **36 MB** on every page load. Now `preload="none"` with `IntersectionObserver`-triggered playback.
- **Bundle size** — Three.js moved out of the main chunk; app bundle is **459 kB** (147 kB gzipped), with the aurora as a separate on-demand chunk.
- **Search persistence** — check-in / check-out / guests survive a reload via `localStorage`, wrapped in try/catch for private browsing.
- **Nav CTA colour** — a legacy `.nav-cta { background: #005b7f !important }` was silently overriding the ember gradient.
- **Text encoding** — repaired double-mojibake apostrophes in the contact copy.

**Verified** at 320 / 375 / 414 / 1440px with Playwright: no horizontal bleed
on any route, hamburger hit-tests correctly and opens all 7 links, admin login
renders and still rejects bad credentials.

**Known open items** — see [context.md](context.md):
- `/gallery` has a `grid-cols-3` element overflowing both edges (clipped, no page bleed) in the pre-existing animated-gallery component
- `.git` history carries ~1.27 GB of large binary assets; shrinking it needs a history rewrite

## 🖼️ Screenshots

### Homepage
![Homepage Screenshot](https://icelandbeach.com/static/images/hompage.jpg)
*Stunning hero section with video background*

### Gallery
![Gallery Screenshot](https://icelandbeach.com/static/images/gallery.jpg)
*Interactive photo gallery showcasing resort features*

### Mobile View
![Mobile Screenshot](https://icelandbeach.com/static/images/mobile.jpeg)
*Fully responsive design on mobile devices*

## 🤝 Contributing

Contributions are welcome! If you'd like to improve Iceland Beach Resort, please follow these steps:

1. **Fork the repository**
   
   Click the "Fork" button at the top right of this page.

2. **Create a feature branch**
   ```bash
   git checkout -b feature/AmazingFeature
   ```

3. **Commit your changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```

4. **Push to the branch**
   ```bash
   git push origin feature/AmazingFeature
   ```

5. **Open a Pull Request**
   
   Navigate to your fork on GitHub and click "New Pull Request"

### Coding Standards

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Ensure responsive design principles are maintained
- Test across multiple browsers and devices

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- LinkedIn: [Your Profile](https://linkedin.com/in/yourprofile)
- Email: your.email@example.com

## 🙏 Acknowledgments

- Bootstrap team for the excellent framework
- The open-source community for inspiration and resources
- Hostinger for reliable hosting services

## 📞 Contact & Support

For questions, issues, or suggestions:
- Open an issue on GitHub
- Email: support@icelandbeachresort.com
- Visit: [Iceland Beach Resort](https://yourwebsite.com)

---

<div align="center">

**⭐ Star this repository if you find it helpful!**

Made with ❤️ in Lagos, Nigeria

</div>
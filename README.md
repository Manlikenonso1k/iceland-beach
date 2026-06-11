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
- **🛠️ Admin Dashboard**: Filament 3 admin panel for order management and QR check-in
- **🔒 Secure Architecture**: UUID-based URLs, server-side payment verification, CSRF-safe webhooks
- **🚀 Automated Deployment**: CI/CD pipeline for instant updates to production

## 🛠️ Technologies Used

### Frontend (Public Site)
- **HTML5 / CSS3 / JavaScript (ES6+)**
- **Bootstrap 5.3.2**: Responsive grid and components
- **Tailwind CSS** (via CDN): Used on the events and ticketing pages
- **Paystack Inline JS**: `PaystackPop.setup()` for card popup

### Backend — Public Site
- **PHP Legacy**: Server-side scripting (`ticket.php`, `event.php`, etc.)

### Backend — Admin & Ticketing
- **Laravel 12**: Full-stack PHP framework (`laravel-app/`)
- **Filament 3**: Admin panel with TicketResource, filters, check-in actions
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
├── ticket.php                  # 🎟️ Public ticket purchase form (PHP Legacy)
├── event.php                   # Events listing page
├── index.php                   # Homepage
├── about.php                   # About page
├── gallery.php                 # Gallery page
├── includes/                   # Shared PHP components (header, footer)
├── static/                     # CSS, JS, images
├── admin/                      # Legacy admin tools
├── RESULT.md                   # 📄 Full build documentation (read this!)
│
└── laravel-app/                # 🚀 Laravel 12 backend
    ├── .env                    # Environment config (payment keys live here)
    ├── config/services.php     # Paystack + Titan config
    ├── routes/web.php          # All payment + ticketing routes
    │
    ├── database/migrations/
    │   └── ..._create_tickets_table.php
    │
    ├── app/
    │   ├── Models/Ticket.php
    │   ├── Http/Controllers/PaymentController.php
    │   ├── Mail/TicketReceiptMail.php
    │   └── Filament/Resources/TicketResource.php
    │
    └── resources/views/
        ├── ticket/
        │   ├── success.blade.php   # Receipt page with QR code
        │   └── verify.blade.php    # Gate scan result (green/red screen)
        └── mail/
            └── ticket-receipt.blade.php
```

> 📄 For full technical documentation of the ticketing system, see **[RESULT.md](RESULT.md)**

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

4. **Run migrations**
   ```bash
   php artisan migrate
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

## 🔄 Deployment (Production)

This project uses **GitHub Actions** for automated deployment. Every push to `main` triggers the CI/CD pipeline.

### After `git pull` on server — run these commands:

```bash
cd domains/icelandbeach.com/public_html/laravel-app

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run any new migrations
php artisan migrate --force

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
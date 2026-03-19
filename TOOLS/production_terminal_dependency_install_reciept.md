# Production Terminal Dependency Install Guide

Use this guide on your office PC when connected to the production server terminal.

## Scope

- Project root: /path/to/Iceland
- Laravel app: /path/to/Iceland/laravel-app
- Goal: install runtime dependencies required to run the Laravel admin and invoice PDF feature.

## 1. Check Runtime Versions First

Run:

    php -v
    php -m
    composer -V

Expected:

- PHP 8.4 recommended (current lock file expects 8.4-compatible packages).
- Composer 2.x installed.

If composer is missing, install it first (if server allows):

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
    composer -V

## 2. Required PHP Extensions

Ensure these are enabled on production PHP:

- bcmath
- ctype
- curl
- dom
- fileinfo
- json
- mbstring
- openssl
- pdo
- pdo_mysql
- tokenizer
- xml
- zip

Quick check:

    php -m | grep -Ei "mbstring|openssl|pdo|pdo_mysql|xml|zip|curl|bcmath"

If your host uses cPanel/Hostinger panel, enable missing extensions from PHP Configuration in the hosting dashboard.

## 3. Pull Latest Code

From project root:

    cd /path/to/Iceland
    git fetch origin
    git checkout main
    git pull origin main

## 4. Install Laravel Composer Dependencies

From laravel-app:

    cd /path/to/Iceland/laravel-app
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

Install invoice PDF dependency added in this project:

    composer update barryvdh/laravel-dompdf --with-all-dependencies --no-interaction

## 5. Configure Environment and Database

If first deploy only:

    cp -n .env.example .env

Then set production values in .env (DB, APP_URL, MAIL, etc.), then run:

    php artisan key:generate --force
    php artisan migrate --force

## 6. Build Frontend Assets

Option A: server has Node/npm:

    npm ci
    npm run build

Option B: no npm on server:

- Build on office/local machine and upload laravel-app/public/build to production.
- After upload, continue with cache steps below.

## 7. Cache and Optimize

    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

## 8. Verify Install

    php artisan about
    php artisan migrate:status

Then open in browser:

- /admin/login
- /admin/invoices

## 9. Common Fixes

Composer says platform PHP mismatch:

- Upgrade production PHP to 8.4.

Vite manifest missing error:

- Build assets and ensure laravel-app/public/build exists on server.

PDF download fails in invoices:

- Confirm DOMPDF package is installed:

    composer show barryvdh/laravel-dompdf

- Clear caches:

    php artisan optimize:clear

## 10. One-Line Fast Deploy Sequence

Run this after git pull when environment is already configured:

    cd /path/to/Iceland/laravel-app && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction && composer update barryvdh/laravel-dompdf --with-all-dependencies --no-interaction && php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache

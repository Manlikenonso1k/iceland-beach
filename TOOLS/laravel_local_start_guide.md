# Laravel Local Start Guide (Iceland Project)

This guide is for the Laravel app in `laravel-app` (Filament admin), not the legacy PHP admin pages.

## Scope

- Project root: `c:/Users/TENSTRINGS MUSIC INS/Documents/Iceland`
- Laravel app: `c:/Users/TENSTRINGS MUSIC INS/Documents/Iceland/laravel-app`
- Admin login URL: `http://127.0.0.1:8000/admin/login`

## One-Time Machine Setup

### 1) Verify PHP is the expected installation

Run:

```bash
which php
php -v
php --ini
```

Expected PHP path should point to `C:/tools/php/php.exe`, and loaded ini should be `C:\tools\php\php.ini`.

### 2) Ensure required extensions are enabled in php.ini

Edit `C:\tools\php\php.ini` and set:

```ini
extension_dir = "C:/tools/php/ext"
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
```

If any of these are commented, uncomment them.

### 3) Validate extension load

Run:

```bash
php -m
php -r "echo 'mb_split='.(function_exists('mb_split')?'yes':'no').PHP_EOL;"
php -r "echo 'openssl='.(function_exists('openssl_cipher_iv_length')?'yes':'no').PHP_EOL;"
```

Expected:

- `mb_split=yes`
- `openssl=yes`
- `php -m` includes `mbstring`, `mysqli`, `pdo_mysql`, `pdo_sqlite`.

## First Project Bootstrap

From `laravel-app`:

```bash
composer install
```

Confirm `.env` has local sqlite:

```env
DB_CONNECTION=sqlite
```

Create sqlite file if missing:

```bash
touch database/database.sqlite
```

Run migrations:

```bash
php artisan migrate --force
```

Clear caches once:

```bash
php artisan optimize:clear
```

## Create/Reset Local Admin User

Run this in `laravel-app`:

```bash
php artisan tinker
```

Then paste in tinker:

```php
\App\Models\User::updateOrCreate(
    ['email' => 'admin@icelandbeach.com'],
    [
        'name' => 'Admin',
        'password' => 'admin123',
    ]
);
```

Then exit tinker:

```php
exit
```

## Daily Start Commands

From `laravel-app`:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open:

- `http://127.0.0.1:8000/`
- `http://127.0.0.1:8000/admin/login`

Note: the terminal usually prints only one line (`Server running on [http://127.0.0.1:8000]`). The admin URL is a route on that same server.

## Quick Health Check

From `laravel-app`:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/admin/login
```

Expected: both return `200`.

## If You See HTTP 500

### Check log

```bash
tail -n 120 storage/logs/laravel.log
```

### Common fixes used in this project

1. `Call to undefined function ... mb_split()`
- Enable `extension=mbstring`.

2. `Call to undefined function ... openssl_cipher_iv_length()`
- Enable `extension=openssl`.

3. `could not find driver`
- Enable `extension=pdo_sqlite` (for local sqlite) and/or `extension=pdo_mysql`.

4. Extensions still not loading
- Confirm `extension_dir = "C:/tools/php/ext"`.
- Confirm you are using `C:/tools/php/php.exe` via `which php`.

After any php.ini change:

- Stop current `php artisan serve` process.
- Start it again.
- Run `php artisan optimize:clear`.

## Local Credentials (Dev Only)

- Admin email: `admin@icelandbeach.com`
- Admin password: `admin123`

Change these before any production use.

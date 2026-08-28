# Iceland Deploy Guide (Office PC -> Production)

Use this runbook when you push code to GitHub from your dev machine, then pull and deploy from your office PC that has production access.

## Scope

- Legacy PHP site lives in project root.
- Laravel admin app lives in `laravel-app`.
- This guide assumes production server access via SSH.

## Production Requirements

### Required runtime

- PHP 8.4 (recommended for current lock file)
- Composer 2.x
- MySQL/MariaDB
- Web server (Apache or Nginx)

### Required PHP extensions

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

### Verify on server

```bash
php -v
php -m
composer -V
```

If `composer install` fails with PHP version errors, upgrade server PHP to 8.4.

## One-Time Server Setup

Run once on production server after first clone.

```bash
cd /path/to/project
cd laravel-app

# create env if missing
cp -n .env.example .env

# app key
php artisan key:generate --force

# install prod dependencies
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# database
php artisan migrate --force

# cache warmup
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set correct DB and mail values in `.env` before running migrations.

## Normal Deploy Flow (Each Release)

### Step 1: From your dev machine

```bash
cd /path/to/Iceland
git add -A
git commit -m "your deployment message"
git push origin main
```

### Step 2: From office PC (with production SSH access)

```bash
ssh your_user@your_server
cd /path/to/project

# sync latest code
git fetch origin
git checkout main
git pull origin main

# laravel deploy steps
cd laravel-app
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Frontend Assets (No npm on Production)

This project uses Vite. If production server has no npm/node, use this process:

### Build assets on office PC (or any machine with Node)

```bash
cd /path/to/Iceland/laravel-app
npm ci
npm run build
```

### Upload built assets to production

`public/build` is gitignored, so it will not be pulled by git.

Use `scp` (or rsync/SFTP) to upload built files:

```bash
scp -r public/build your_user@your_server:/path/to/project/laravel-app/public/
```

Then run Laravel cache commands on server:

```bash
ssh your_user@your_server
cd /path/to/project/laravel-app
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If you install Node on production, you can instead run `npm ci && npm run build` there.

## Quick Health Check

```bash
cd /path/to/project/laravel-app
php artisan about
php artisan migrate:status
```

Open in browser:

- Site home page
- `/admin/login`
- `/admin/bookings`

If app fails, check logs:

```bash
tail -n 150 storage/logs/laravel.log
```

## Quick Rollback

```bash
cd /path/to/project
git log --oneline -n 10

# replace with previous good commit
git reset --hard <commit_hash>

cd laravel-app
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Common Errors

1. Composer fails with PHP constraint
- Cause: server PHP too low.
- Fix: switch server to PHP 8.4.

2. `Vite manifest not found`
- Cause: missing `laravel-app/public/build`.
- Fix: build assets with npm and upload build folder.

3. HTTP 500 after deploy
- Check `storage/logs/laravel.log`.
- Confirm `.env` DB credentials and required PHP extensions.

## Safe Practice Notes

- Never commit `.env`.
- Keep production secrets only on server.
- Deploy from `main` only when tests pass.

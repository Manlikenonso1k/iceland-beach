#!/usr/bin/env bash
#
# Post-pull deploy steps, run by .github/workflows/deploy.yml after
# `git reset --hard origin/main`.
#
# This file was referenced by the workflow but did not exist, so none of these
# steps had ever run on the server: the database was never migrated and caches
# were never rebuilt. That is why /admin threw 500s for anything touching the
# settings table or the bookings payment columns.
#
set -euo pipefail

# NOTE: there is deliberately no .htaccess step here any more. The committed
# .htaccess is now the correct two-line docroot bridge, so `git reset --hard`
# above already puts the right file in place. This script used to overwrite it
# on every deploy because the committed copy was the old site's rule set — with
# both in play, whichever ran last won, and a deploy that stopped short of this
# script left the site dead.
echo "==> Deploying $(git rev-parse --short HEAD)"

# 1. PHP dependencies.
#    `git reset --hard` overwrites vendor/composer/* (those files are tracked),
#    so the autoloader must be regenerated to match this checkout.
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
else
    echo "!! composer not on PATH — skipping dependency install"
fi

# 2. Database. --force is required because APP_ENV=production is non-interactive.
php artisan migrate --force

# 3. Roles/settings defaults. Both seeders are idempotent.
php artisan db:seed --class=RoleSeeder --force || echo "!! RoleSeeder skipped"

# 4. Front-end assets. Only rebuild when Node is available; otherwise keep the
#    committed public/build output rather than leaving the site with none.
if command -v npm >/dev/null 2>&1; then
    npm ci --omit=dev || npm install --omit=dev
    npm run build
else
    echo "!! npm not on PATH — using committed public/build"
fi

# 5. Storage symlink. `php artisan storage:link` cannot work on this host:
#    both symlink() and exec() are disabled in PHP, so link it directly.
#    -n stops a re-run from nesting public/storage/public inside itself.
ln -sfn "$PWD/storage/app/public" "$PWD/public/storage"
mkdir -p storage/app/public/qrcodes
chmod -R 775 storage bootstrap/cache

# 6. Caches. Clear first so a stale compiled config can never survive a deploy.
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Deploy complete"

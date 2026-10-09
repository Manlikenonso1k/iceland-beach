# Deploying the Iceland app (website + Hospi Sales API)

This Laravel app is **one codebase** that serves both the Iceland website and the
Hospi Sales mobile API (the `/api/...` routes). Deploying it to Hostinger puts
**both** live in a single step — the phone app's backend is the same server as
the website.

The mobile app simply points at this server's `/api`, e.g.
`https://<your-domain>/api`. No separate hosting, no sub-app.

---

## Requirements on the host

- PHP **8.2+** with the usual Laravel extensions (mbstring, openssl, pdo,
  tokenizer, xml, ctype, json, bcmath, fileinfo, curl, gd).
- Composer.
- MySQL (or MariaDB) database.
- **HTTPS** (SSL). The mobile app must talk to `https://`, not `http://`.
  Hostinger issues free SSL — turn it on for the domain.

If the Iceland website already runs here as Laravel, this environment is already
in place and the API needs nothing extra.

---

## Deploy / update steps

From the project root on the server:

```bash
git pull                              # get the latest code
composer install --no-dev --optimize-autoloader
php artisan migrate --force           # apply new tables (additive, safe)
php artisan storage:link              # once, for uploaded images
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`.env` on the server must have at least:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your-domain>
# DB_*, MAIL_*, PAYSTACK_* as already configured for the website
```

"Easily push updates" = this is it: edit code, commit, `git pull` on the server,
re-run migrate + the cache commands. One deploy updates the website **and** the
mobile backend.

---

## The scheduler — REQUIRED (do not skip)

Overdue-order escalation (the core "no meal gets forgotten" promise) runs on
Laravel's scheduler. **Without it, overdue alerts never fire in production.**

Add a cron job that runs **every minute**:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

On a VPS you can instead run `php artisan schedule:work` as a systemd service.

---

## Queue worker — if you use queues

If `QUEUE_CONNECTION` is not `sync`, run a worker so notifications/push are sent:

```
php artisan queue:work --tries=3
```

Keep it alive with Supervisor (VPS) or a Hostinger "always-on" process. If
`QUEUE_CONNECTION=sync`, skip this — jobs run inline and the cron above is enough.

---

## Pointing the mobile app here

In the Expo app repo (`iceland app`), the API URL per build variant lives in
`app.config.ts` (`API_BASE_URL_BY_VARIANT`). Set it to this server:

- production → `https://<your-domain>/api`
- staging/preview → the same, or a staging domain if you run one

A build can also override it with the `EXPO_PUBLIC_API_BASE_URL` env in
`eas.json`. Rebuild (or `eas update`) after changing it.

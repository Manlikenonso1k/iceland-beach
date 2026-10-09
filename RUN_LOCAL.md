# Hospi Sales — Run the backend locally

The additive Hospi Sales API on top of the existing Iceland Beach Laravel app.
Get the API running on your PC so a phone on the same Wi-Fi (or the Expo app) can
reach it, with the full order → timer → board loop working.

> Status: **Phase 0 + Phase 2 backend are complete and proven** (55/55 tests pass:
> the original 37 plus 10 Phase-0 and 8 Phase-2 stop-gate tests). The Expo mobile
> app is not scaffolded yet — these steps cover the API and the demo data it serves.

---

## 1. One-time setup

```bash
cd Iceland
composer install
php artisan migrate --force
php artisan db:seed --class="Database\Seeders\HospiDemoSeeder"
```

The seeder creates one demo tenant (Iceland Beach Resort), one account per role,
a price list, and a handful of meals with prep times — enough to log in and click
through immediately. It is idempotent; re-running it does no harm.

## 2. Find your PC's LAN IP (so the phone can reach it)

On Windows:

```powershell
ipconfig
```

Use the **IPv4 Address** of your active Wi-Fi adapter, e.g. `192.168.0.42`. The
phone must use this, **not** `localhost` / `127.0.0.1` — on the phone those mean
the phone itself. The Expo dev profile's `apiBaseUrl` should be
`http://<LAN-IP>:8000/api`.

## 3. Start the API (bound to the LAN, not just localhost)

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

`--host=0.0.0.0` is what makes the phone able to reach it. Confirm from the PC:

```bash
curl http://127.0.0.1:8000/api/health
# {"status":"ok","version":"hospi-sales-v1","env":"local","time":"..."}
```

## 4. Start the scheduler (so overdue orders actually flip to RED)

In a **second terminal** — this is the dev equivalent of cron. Without it,
PRIORITY 1 (the overdue push) cannot fire, and orders will not escalate.

```bash
php artisan schedule:work
```

This runs `orders:flag-overdue` every minute, which pages every manager exactly
once for each order past its `due_at`, server-side, with no app open.

## 5. Demo accounts (password is `password` for all)

| Role     | Email               | Phone         | Lands on            |
|----------|---------------------|---------------|---------------------|
| Manager  | manager@hospi.test  | 08100000001   | Order Board         |
| Waitress | waiter@hospi.test   | 08100000002   | Take an order       |
| Chef     | chef@hospi.test     | 08100000003   | Main-kitchen queue  |
| CEO      | ceo@hospi.test      | 08100000004   | Revenue dashboard   |
| Host     | host@hospi.test     | 08100000005   | Tables/beds/cabins  |

Login accepts **email OR phone** plus the password. The chef account is scoped to
`main_kitchen` and sees only that kitchen's queue.

## 6. Smoke test the whole loop from the shell

```bash
# 1) log in as the waitress (by phone), capture the token
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"08100000002","password":"password"}' \
  | python -c "import sys,json;print(json.load(sys.stdin)['token'])")

# 2) read business identity + prices (nothing hardcoded in the app)
curl -s http://127.0.0.1:8000/api/me -H "Authorization: Bearer $TOKEN"

# 3) list the main-kitchen menu
curl -s "http://127.0.0.1:8000/api/meals?department=main_kitchen" -H "Authorization: Bearer $TOKEN"

# 4) place an order — the SERVER stamps due_at and status_colour
curl -s -X POST http://127.0.0.1:8000/api/orders \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"department":"main_kitchen","table_id":5,"items":[{"meal_id":1,"quantity":2}]}'
# -> status_colour "amber", due_at = placed_at + target_minutes, server_time returned
```

A placed order is AMBER until its `due_at`, then the scheduler turns it RED and
pushes to the manager.

## 7. Run the tests

```bash
php artisan test                       # full suite (55 passing)
php artisan test --filter=HospiPhase0Test
php artisan test --filter=HospiPhase2Test
```

---

## What exists so far (API surface)

```
GET  /api/health                      public reachability probe
POST /api/auth/login                  email OR phone + password -> Sanctum token
POST /api/auth/logout                 revoke current token
GET  /api/me                          user + roles + tenant identity + prices
GET  /api/meals?department=           tenant-scoped catalogue
POST /api/orders                      waitress: create -> server stamps due_at
GET  /api/orders?status=&dept=        role-scoped list (waitress own / chef dept / manager all)
GET  /api/orders/board                manager: RED first, then AMBER, then GREEN
POST /api/orders/{order}/accept       chef
POST /api/orders/{order}/decline      chef; reason REQUIRED
POST /api/orders/{order}/ready        chef
POST /api/orders/{order}/complete     waitress / manager
POST /api/device-tokens               register an Expo push token
```

Not yet built (Phase 3+): CSV meal import, prep-time editor endpoint, Gate Sales,
Hosts, Front Desk, CEO dashboard, and the entire Expo app.

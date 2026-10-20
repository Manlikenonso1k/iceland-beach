# Role & Access Audit — Iceland Beach Resort

**Scope:** the `admin` Filament panel, the `referral` panel, `routes/web.php`, `routes/api.php`, the four
policies in `app/Policies/`, and the dashboard widgets.
**Date:** 2026-10-20 · **Target:** Laravel 12 / Filament 3.3 (`vendor/filament/filament`), PHP 8.5
**Method:** every cell in the access matrix below was produced by **running the application** — throwaway
PHPUnit feature tests logged in as each role against in-memory SQLite, issued real HTTP requests to every
`/admin/*` URL, and called every `can*()` static with that role acting under
`Filament::setCurrentPanel(Filament::getPanel('admin'))`. Nothing in the matrix is inferred from reading
source. Four privilege paths were additionally proven by executing the attack (Livewire form saves and
unauthenticated HTTP requests). The throwaway tests were deleted after the run; the commands needed to
reproduce them are described in each finding.

Authorisation in this codebase is **role-name based**. `spatie/laravel-permission` is installed but
**zero permissions are defined** — every gate compares role name strings. That is a design the findings
below work *with*, not against.

---

## Executive summary

| # | Severity | Finding |
|---|----------|---------|
| 1 | **Critical** | **`Manager` can grant itself `Super Admin`.** `/admin/users` and `/admin/roles` are reachable with full create/edit/delete, and the user form contains a `roles` multi-select. Proven end to end: a Manager saved `Super Admin` onto its own account and then opened `/admin/manage-settings` (previously 403) with HTTP 200. |
| 2 | **High** | **`/invoices/{id}/view` is fully public** — no login, no `signed`, sequential integer id. Walking `/invoices/1/view … /invoices/N/view` harvests every customer's name, address, telephone and invoice total. |
| 3 | **High** | **`InvoicePolicy` is bypassed for reading.** `InvoiceResource::canAccess()` is overridden to a bare "is logged in" check, so the policy's `viewAny` denial never gates the page. A `Manager` (Gate: `viewAny` = DENY) opens `/admin/invoices` with HTTP 200 and the invoice rows render in the HTML. The same bare check gives Scanner, Space Host and Waiter-shaped roles the invoice list. |
| 4 | **High** | **The `Host` alias is missing from the room-booking denylist.** `Space Host` is read-only on room bookings; the accepted alias `Host` is not, so a role named plainly `Host` gets create/edit/delete on room bookings. This is the *same* alias-not-propagated bug class as the already-fixed `Space Host` escalation. |
| 5 | **Medium** | **`/ticket/verify/{uuid}` performs an unauthenticated state change.** A plain GET flips `checked_in` to `true`, increments `scan_count`, writes a scan log with `user_id = NULL`, and returns the ticket holder's name and e-mail as JSON. Anyone who photographs a QR code can burn the ticket remotely; the `Scanner` role gates the *page*, not the *endpoint*. |
| 6 | **Medium** | **The seeded `Admin` role is locked out of the panel entirely** (403 on every URL, including the dashboard). `User::PANEL_ROLES` lists lowercase `admin` but not the seeded `Admin`. Availability bug today, and a trap: creating an `admin` role to "fix" it silently hands out full Administration + Events. |

---

## Access matrix (empirical)

Legend: **WRITE** = index loads *and* at least one reachable create / edit / delete / state-changing action
is allowed · **VIEW** = index loads, all writes refused · **—** = HTTP 403 (or 302 to login).

Columns are the navigation areas. `Reports` is VIEW-only for everyone because `ReportResource` registers
only an index page and `->actions([])` (`app/Filament/Resources/ReportResource.php:57,67-71`) — its
`canCreate`/`canEdit`/`canDelete` all return true but there is no route or button that reaches them.

| Role | Dash | Room bookings | Space bookings | Rooms | Spaces | Sales | Reports | Inventory | Invoices | Tickets | Scan logs | Scan page | Referrals | Vendor reqs | **Users** | **Roles** | Activity log | Settings |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **Super Admin** | VIEW | WRITE | WRITE | WRITE | WRITE | WRITE | VIEW | WRITE | WRITE | WRITE | WRITE | VIEW | WRITE | WRITE | **WRITE** | **WRITE** | VIEW | WRITE |
| **Manager** | VIEW | WRITE | WRITE | — | — | WRITE | VIEW | WRITE | VIEW | WRITE | WRITE | VIEW | WRITE | WRITE | **WRITE** | **WRITE** | — | — |
| **Front Desk** | VIEW | WRITE | VIEW | — | — | WRITE | VIEW | WRITE | WRITE | — | — | — | — | — | — | — | — | — |
| **Space Host** | VIEW | VIEW | WRITE | — | — | WRITE | VIEW | WRITE | VIEW | — | — | — | — | — | — | — | — | — |
| **`Host`** (accepted alias) | VIEW | **WRITE** ⚠ | WRITE | — | — | WRITE | VIEW | WRITE | VIEW | — | — | — | — | — | — | — | — | — |
| **Scanner** | VIEW | WRITE | VIEW | — | — | WRITE | VIEW | WRITE | VIEW | — | — | VIEW | — | — | — | — | — | — |
| **Waiter** | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| **`Admin`** (seeded) ⚠ | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| **Referral** (web guard) | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| no role at all | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| owner e-mail bypass, no role | VIEW | WRITE | VIEW | — | — | WRITE | VIEW | WRITE | WRITE | WRITE | WRITE | VIEW | WRITE | WRITE | **WRITE** | **WRITE** | — | — |
| `super_admin` (legacy snake) | VIEW | WRITE | WRITE | — | — | WRITE | VIEW | WRITE | WRITE | WRITE | WRITE | VIEW | WRITE | WRITE | **WRITE** | **WRITE** | — | — |
| `admin` (legacy lowercase) | VIEW | WRITE | WRITE | — | — | WRITE | VIEW | WRITE | WRITE | WRITE | WRITE | VIEW | WRITE | WRITE | **WRITE** | **WRITE** | — | — |

Notes on cells that look surprising but are correct:

* `Rooms` / `Spaces` are `Super Admin`-only by explicit override
  (`RoomResource.php:50-54`, `SpaceResource.php:49-53`), so even `Manager` gets 403. Intentional.
* `Activity log` and `Settings` are `Super Admin` by *exact* name
  (`ActivityLogResource.php:25-30`, `ManageSettings.php:35-40`), which is why the legacy `super_admin`
  and `admin` rows and the owner e-mail bypass are refused there.
* `Tickets` has no create page by design (`TicketResource.php:222-225`) but does have a `check_in`
  row action, hence WRITE.
* The owner e-mail bypass (`User.php:100-102`) grants *panel entry*, not role membership, so it is
  refused by every gate that asks for a role name — Settings, Activity log, and space-booking writes.

### Widget visibility (empirical, `canView()`)

| Widget | Who sees it |
|---|---|
| `RevenueStatsWidget` | **Super Admin only** (`RevenueStatsWidget.php:39-43`) |
| `RoomSalesPieChart`, `RoomBreakdownPieChart` | Super Admin, Manager |
| `ConfirmedBookingsWidget`, `PendingBookingsWidget` | rendered to all panel roles, but their confirm/reject actions are gated to Super Admin + Manager (`PendingBookingsWidget.php:251-263`) |
| `PendingSpaceBookingsWidget` | all panel roles; buttons gated on `SpaceBookingResource::canWrite()` **and** re-checked with `abort_unless` inside the action (`PendingSpaceBookingsWidget.php:93,119`) |
| `SalesOverview`, `PaymentMethodBreakdown`, `MostBookedRoomsWidget`, `BookingCalendarWidget`, `SpaceBookingCalendarWidget` | **ungated** — any panel role. `BookingCalendarWidget` therefore shows guest names to Scanner and Space Host. |

---

## Findings

### F1 — Critical: `Manager` can escalate to `Super Admin`

**What it is.** `UserResource` and `RoleResource` are gated by the `RestrictsManagementAreas` trait, which
is a **denylist**: `canAccessRestrictedSection()` returns `true` for anyone *not* holding a role in
`FLOOR_ROLES` (`app/Filament/Concerns/RestrictsManagementAreas.php:34-49`). `Manager` is not in
`FLOOR_ROLES`, so it receives the full Administration area — including the user editor, whose form has a
`roles` relationship multi-select (`app/Filament/Resources/UserResource.php:63-67`) and the role editor,
whose form has an "Assign Users" select (`app/Filament/Resources/RoleResource.php:52-57`).

**Files / lines.**
* `app/Filament/Concerns/RestrictsManagementAreas.php:34-49` — the `FLOOR_ROLES` denylist and the default-allow decision.
* `app/Filament/Resources/UserResource.php:22-25` (`canAccess` → `canViewAny` → trait) and `:63-67` (the `roles` select).
* `app/Filament/Resources/RoleResource.php:22-25` and `:52-57`.
* `app/Models/User.php:89-96` — `Manager` is in `PANEL_ROLES`.

**Concrete abuse path (executed, not theorised).** Acting as a user whose only role is `Manager`:
`GET /admin/users` → 200. `GET /admin/users/{own id}/edit` → 200. Submit the Filament form with
`roles = [Manager, Super Admin]` → saved. The account's roles then read `Manager, Super Admin`; a
follow-up `GET /admin/manage-settings` — which returned 403 one request earlier — returns **200**. From
there the Manager owns the payment toggles, the activity log, the room and space catalogues, and the
revenue widget. The same two writes let a Manager delete the real Super Admin's account or strip its role.

**Why it matters in practice.** `Manager` is a routine shift role at a resort; it is not the owner
account. Any manager — or anyone who phishes one manager password — becomes the system owner silently,
and `ActivityLogResource` is Super-Admin-only, so the victim cannot even read the audit trail of their own
demotion.

**Recommended fix.** Stop relying on a denylist for the two resources that can hand out roles. Add an
explicit allowlist to `UserResource` and `RoleResource` only:

```php
// UserResource.php and RoleResource.php
public static function canAccess(): bool
{
    $user = Filament::auth()->user();

    return $user instanceof User
        && ($user->email === 'victorynonso9@gmail.com'
            || $user->hasAnyRole(['Super Admin', 'super_admin']));
}
public static function canViewAny(): bool { return static::canAccess(); }
public static function canCreate(): bool { return static::canAccess(); }
public static function canEdit(Model $r): bool { return static::canAccess(); }
public static function canDelete(Model $r): bool { return static::canAccess(); }
public static function canDeleteAny(): bool { return static::canAccess(); }
```

If Managers genuinely need to create staff accounts, keep `UserResource` for them but remove the `roles`
select from the form for non-Super-Admins (`->visible(fn () => auth()->user()->hasRole('Super Admin'))`
plus `->dehydrated(...)` on the same condition) and keep `RoleResource` Super-Admin-only. Either way
`/admin/roles` must not be reachable by `Manager`.

---

### F2 — High: `/invoices/{id}/view` is a public, walkable IDOR

**What it is.** A closure route with no `auth` and no `signed` middleware.

**File / lines.** `routes/web.php:10-14`

```php
// Public invoice view for print-to-PDF (no DOMPDF needed)
Route::get('/invoices/{id}/view', function ($id) {
    $invoice = \App\Models\Invoice::with('items')->findOrFail($id);
    return view('pdf.invoice', ['invoice' => $invoice]);
})->name('invoices.view');
```

**Concrete abuse path (executed).** With no session at all,
`GET /invoices/1/view` → **200**, and the response body contains the invoice's `customer_name`,
`customer_address`, `telephone`, line items and `total_amount`. The primary key is a plain
auto-increment integer (`database/migrations/2026_03_19_130000_create_invoices_table.php:12`), so a loop
over `1..N` dumps the resort's entire billing history, including corporate client names and amounts.
This is pre-existing, not newly introduced.

**Why the comment is not a defence.** The route exists so staff can print an invoice to PDF without
DOMPDF — but `InvoiceResource` already streams a PDF through an authenticated panel action
(`app/Filament/Resources/InvoiceResource.php:188-200,243-260`), so the public route is redundant for that
purpose.

**Recommended fix.** The sibling routes already do this correctly — copy them. Either
`->middleware('signed')` (matching `/booking/receipt/{booking}` at `routes/web.php:106-108`, which
correctly returns 403 unsigned) or, since this URL is only ever opened by staff,
`->middleware(['auth', 'can:view,invoice'])`. Signed is the better match if the link is ever embedded in
an e-mail to the customer.

---

### F3 — High: `InvoicePolicy` is bypassed for reading because `canAccess()` was overridden

**What it is.** Filament's `Resource::canAccess()` defaults to `canViewAny()`
(`vendor/filament/filament/src/Resources/Resource.php:136-138`), which consults the policy.
`InvoiceResource` replaces it with a bare authentication check, and `canAccess()` is what the resource
pages actually abort on (`vendor/filament/filament/src/Resources/Pages/Concerns/CanAuthorizeResourceAccess.php`).

**File / lines.** `app/Filament/Resources/InvoiceResource.php:31-34`

```php
public static function canAccess(): bool
{
    return auth()->guard('web')->check();
}
```

**Concrete abuse path (executed).** Acting as `Manager`: `$user->can('viewAny', Invoice::class)` is
**DENY** (`InvoicePolicy::viewAny` → `canManageInvoices` → Super Admin / `admin` / Front Desk only,
`app/Policies/InvoicePolicy.php:25-28,85-95`). Nonetheless `GET /admin/invoices` → **200**, and an
invoice created with `customer_name = "Secret Corp Ltd"` appears in the rendered HTML. Writes *are*
still blocked (`/admin/invoices/create` → 403), so the policy half-works: it guards writes and silently
fails on reads. The same bare check hands the invoice list to `Scanner`, `Space Host` and `Host`, none
of which the policy intends to admit.

**Why it matters in practice.** The invoice table shows customer name, telephone and total. A gate
scanner on a casual contract reads the resort's corporate billing relationships.

**Recommended fix.** Delete the `canAccess()` override so it falls back to `canViewAny()` and the policy
applies; or make it explicit and consistent with the policy:

```php
public static function canAccess(): bool
{
    return auth()->guard('web')->check() && static::canViewAny();
}
```

The identical pattern — `canAccess()` reduced to `auth()->guard('web')->check()` — is present in
`BookingResource.php:42-45`, `InventoryItemResource.php:23-26`, `ReportResource.php:20-23`,
`SaleResource.php:37-40` and `SpaceBookingResource.php:69-72`. Those resources have no policy, so
nothing is being bypassed there, but see F7.

---

### F4 — High: the `Host` alias is missing from the room-booking denylist

**What it is.** `Space Host` and `Host` are documented as interchangeable names for the same job
(`app/Models/User.php:89-96`, `RestrictsManagementAreas.php:34-41`,
`RestrictsSpaceBookingWrites.php:36-44` — all three list both). `RestrictsRoomBookingWrites` lists only
one of them.

**File / line.** `app/Filament/Concerns/RestrictsRoomBookingWrites.php:27`

```php
/** Roles that may see room bookings but not touch them. */
public const ROOM_READ_ONLY_ROLES = ['Space Host'];
```

**Concrete abuse path (executed).** Side-by-side, same request sequence:

| Role | `canWriteRoomBooking()` | `GET /admin/bookings/create` |
|---|---|---|
| `Space Host` | `false` | **403** |
| `Host` | `true` | **200** |

So the user from the `HostLoginTest` scenario — someone whose role was created as plainly `Host` — can
create, edit and delete *room* bookings, confirm and reject them, and release held rooms. That is exactly
the boundary `RestrictsRoomBookingWrites` was written to enforce, and its own docblock states the intent
("`Space Host` may VIEW room bookings but must never change them").

**Why it matters in practice.** The alias exists precisely because staff create roles by hand in
`/admin/roles`; whichever of the two names they typed decides whether they can overwrite the room
ledger. Two users doing the same job get different write rights, and nothing in the UI reveals it.

**Recommended fix.** One line:

```php
public const ROOM_READ_ONLY_ROLES = ['Space Host', 'Host'];
```

Better still, hoist the alias set to one place so it cannot diverge again — see the pattern-level
recommendation.

---

### F5 — Medium: `/ticket/verify/{uuid}` changes state with no authentication

**What it is.** The QR-scan endpoint is a public `GET` that mutates the ticket.

**Files / lines.** `routes/web.php:37-39` (no middleware) →
`app/Http/Controllers/PaymentController.php:306-370`, with the write at `:314` (`increment('scan_count')`)
and `:322-325` (`checked_in => true`).

**Concrete abuse path (executed).** With no session:
`GET /ticket/verify/{uuid}` → 200; the ticket's `checked_in` flips from `false` to **`true`** and
`scan_count` becomes 1. A second request returns JSON
`{"status":"already","scan_count":2,"ticket":{"name":"Gate Guest","email":"gate@example.test",...}}`.
The `ticket_scan_logs` row is written with **`user_id = NULL`**, so the audit trail cannot say who did it.
A ticket UUID is visible on the buyer's own success page and in the QR image they share on WhatsApp; a
single `<img src="https://…/ticket/verify/{uuid}">` in an e-mail will burn it from the victim's own
browser. The ticket holder then arrives at the gate and is told "already checked in".

Note that `app/Filament/Pages/ScanTicket.php:40-48` carefully reserves the scanning *page* for the
`Scanner` role — but the endpoint it posts to is open to the internet, so that gate buys nothing.

**Why it is Medium, not High.** The UUID is unguessable, so this is not mass-exploitable; it needs a
leaked or shared code. It is still a denial-of-entry primitive and a PII disclosure (name + e-mail) to
anyone holding a UUID.

**Recommended fix.** Split read from write. Keep an unauthenticated, **read-only** verify that reports
status without touching the row, and require a logged-in scanner for the check-in:

```php
Route::get('/ticket/verify/{uuid}', [PaymentController::class, 'verifyEntry'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('ticket.verify');
```

and re-point the QR payload at that URL (the scanner is a staff device that is already signed in — the
panel page at `/admin/scan-ticket` posts to this endpoint). If an unauthenticated fallback must stay,
make the check-in a `POST` guarded by `signed` and keep `name`/`email` out of the response. Also stamp
`user_id` as non-nullable in the scan log so an anonymous check-in is impossible to record.

---

### F6 — Medium: the seeded `Admin` role cannot use the panel at all

**What it is.** `RoleSeeder` creates `Admin` (`database/seeders/RoleSeeder.php:15`). `User::PANEL_ROLES`
contains `'admin'` (lowercase) but not `'Admin'` (`app/Models/User.php:89-96`).

**Concrete effect (measured).** `canAccessPanel()` returns **false** for a user whose only role is the
seeded `Admin`; every `/admin/*` URL including `/admin` itself returns **403**. The login form will have
accepted their password first — and because `Filament\Pages\Auth\Login::authenticate()` raises the same
validation error for both cases, the user is told their credentials are wrong. This is the exact failure
mode commit `73cea72` fixed for `Host`; `Admin` has the same defect and is still broken.

**Why it matters in practice.** Twofold. (a) Availability: assign `Admin` and the account is bricked with
a misleading error. (b) Security trap: the natural "fix" is to create a role literally named `admin`,
and `admin` is not in `FLOOR_ROLES`, so that role immediately gets `/admin/users` + `/admin/roles` — the
F1 escalation, handed out by accident. The empirical rows for `admin` and `super_admin` in the matrix show
exactly how much the lowercase variants receive.

**Recommended fix.** Decide on one canonical set and make the seeder and the constant agree. Minimal
change: add `'Admin'` to `User::PANEL_ROLES` and to the `Super Admin`-adjacent allowlists that currently
spell it `'admin'` (`InvoicePolicy.php:94`, `RestrictsSpaceBookingWrites.php:36-44`). Cleaner change:
drop the snake_case/lowercase aliases from the codebase entirely and normalise on comparison, i.e.
compare `Str::slug($role)` rather than carrying `['Super Admin', 'super_admin']` in six places.

---

### F7 — Low: six resources gate on "is logged in", so one middleware is the whole boundary

**What it is.** `BookingResource:42-45`, `InventoryItemResource:23-26`, `InvoiceResource:31-34`,
`ReportResource:20-23`, `SaleResource:37-40` and `SpaceBookingResource:69-72` all define
`canAccess(): return auth()->guard('web')->check();`, and `RestrictsManagementAreas` admits anyone not in
`FLOOR_ROLES`. Measured consequence: for a **`Waiter`**, a **`Referral`** role-holder and a **user with
no role at all**, the resource-level flags report
`bookings: access=Y create=Y edit=Y delete=Y`, `sales: access=Y create=Y`,
`users: access=Y create=Y edit=Y delete=Y`, `roles: access=Y create=Y edit=Y delete=Y`.

They are nonetheless refused in practice — every URL returns 403 — because
`User::canAccessPanel()` stops them at Filament's `Authenticate` middleware. **This is not currently
exploitable.** It is recorded because it means there is exactly *one* check between a non-staff account
and the role editor. Anything that admits a role to the panel (a new entry in `PANEL_ROLES`, a new
`::canAccessPanel()` branch, a second panel, an API token path that reuses these resources) instantly
grants it Administration and Events. That is precisely the mechanism of the `Space Host` bug that
commit `73cea72` fixed.

**Recommended fix.** Make the floor resources state who they are *for*, not merely that someone is
logged in — e.g. `return auth()->guard('web')->check() && $user->hasAnyRole([...operational roles...]);`
— so panel entry and area entry are two independent decisions rather than one.

---

### F8 — Low: `UserPolicy`, `RolePolicy` and `ActivityLogPolicy` are dead code with a wrong role name

**What it is.** All three are auto-discovered (verified: `Gate::getPolicyFor(User::class)` returns
`App\Policies\UserPolicy`) and all three test the snake_case name `super_admin`, which `RoleSeeder` never
creates.

**Files / lines.** `app/Policies/UserPolicy.php:9-37` (every method `hasRole('super_admin')`),
`app/Policies/RolePolicy.php:10-17,19-47`, `app/Policies/ActivityLogPolicy.php:10-18`.

**Measured result.** For a user holding the real seeded `Super Admin` role, the Gate answers:

```
Gate User::viewAny        = DENY
Gate User::create         = DENY
Gate Role::viewAny        = DENY
Gate Role::create         = DENY
Gate ActivityLog::viewAny = DENY
Gate Invoice::viewAny     = ALLOW      ← InvoicePolicy spells it correctly
```

while `UserResource::canViewAny()` and `RoleResource::canViewAny()` both return **true** for the same
user. **Verdict: reachable by the Gate, but dead for every path that matters.** `RestrictsManagementAreas`
overrides `canViewAny`, `canView`, `canCreate`, `canEdit`, `canDelete`, `canDeleteAny`, `canForceDelete`
and `canRestore`, so the policies are shadowed on all three resources; the abilities they are *not*
shadowed on (`replicate`, `reorder`, `restoreAny`, `forceDeleteAny`) have no registered page or table
action, and the policies return `false` for those anyway. A grep for `authorize(`, `Gate::` and `->can(`
across `app/` finds no other caller. `ActivityLogPolicy::view()` is likewise unreachable because
`ActivityLogResource` registers no view page.

So the mismatch is **fail-closed and currently harmless** — but it is a live landmine: delete the trait
from `UserResource` (or add a `view` page to `ActivityLogResource`) and the policy becomes authoritative,
at which point it denies the genuine Super Admin and the area becomes unusable for everyone.

**Recommended fix.** Either correct the strings to `['Super Admin', 'super_admin']` *and* let the
policies be the single source of truth for these three resources (removing the trait's overrides on
them), or delete the three policy files so there is one obvious place where Administration access is
decided. Keeping two contradictory answers is the worst of the three options. `InvoicePolicy` is the
one that is actually wired in and should stay.

---

### F9 — Informational: `visible()` *is* a server-side gate in this Filament version

`SpaceBookingResource` and `PendingSpaceBookingsWidget` defensively re-check authorisation inside the
action body (`SpaceBookingResource.php:293,313`; `PendingSpaceBookingsWidget.php:93,119`), with comments
warning that "`visible()` hides a button; it does not stop a crafted Livewire call".
`BookingResource`'s room confirm/reject actions (`BookingResource.php:196,212`) and
`PendingBookingsWidget`/`ConfirmedBookingsWidget` do **not**.

I tested whether that difference is exploitable. It is not, in Filament 3.3:
`Filament\Actions\Concerns\CanBeDisabled::isDisabled()` is
`return $this->evaluate($this->isDisabled) || $this->isHidden();`
(`vendor/filament/actions/src/Concerns/CanBeDisabled.php:18-21`), and both
`mountTableAction()` and `callMountedTableAction()` bail on `isDisabled()`
(`vendor/filament/tables/src/Concerns/HasActions.php:82,195`). Measured: a `Space Host` with a valid
Livewire snapshot for `ListBookings` called `mountTableAction('confirm', id)` followed by
`callMountedTableAction()` — the booking stayed `pending`; an identical call as `Manager` moved it to
`confirmed`. The `delete` action was likewise refused and the record survived.

Keep the `abort_unless` lines — they are correct and cheap — and consider adding them to
`BookingResource.php:192-228` for symmetry, since the protection currently depends on a vendor
implementation detail rather than on application code.

---

## Privilege-escalation paths

A role can escalate if it can reach `/admin/users` (assign itself a role) **or** `/admin/roles`
(create a role, attach users, or rename an existing one). Measured — these reach **both**:

| Role | `/admin/users` | `/admin/roles` | Can self-promote? |
|---|---|---|---|
| `Super Admin` | WRITE | WRITE | yes — by design, this is the owner role |
| **`Manager`** | **WRITE** | **WRITE** | **yes — F1, proven end to end. Not intended.** |
| `super_admin` (legacy alias) | WRITE | WRITE | yes — intended as a Super Admin alias |
| `admin` (legacy alias) | WRITE | WRITE | yes — intended as an admin alias, but note F6: this role is *not* seeded, so if someone creates it to work around the `Admin` lockout they create an owner-equivalent by accident |
| owner e-mail bypass with no role | WRITE | WRITE | yes — and it can mint itself the `Super Admin` role to also unlock Settings and the Activity log, which the bypass alone does not grant |

**Confirmed not to escalate:** `Front Desk`, `Space Host`, `Host`, `Scanner` (403 on both),
`Waiter`, `Referral`, seeded `Admin`, and any account with no role (403 on the whole panel).

The single actionable item here is **`Manager`**. Everything else on the list is either the owner or an
intended alias of the owner.

---

## What is correctly locked down

Stated plainly, because most of this panel is right:

* **The `referral` panel boundary holds.** A session authenticated on the `referral` guard was pointed at
  `/admin`, `/admin/users`, `/admin/roles`, `/admin/bookings`, `/admin/bookings/list`, `/admin/invoices`,
  `/admin/manage-settings`, `/admin/scan-ticket` and `/admin/tickets`: **every one redirected (302) to
  `/admin/login`**, while `/referral` returned 200. The separate guard
  (`config/auth.php:46-50`), the separate user provider (`App\Models\Referral`) and the namespace-scoped
  discovery in `ReferralPanelProvider.php:51-53` mean no staff resource is even registered in that panel.
  Additionally, a `User` holding the **`Referral`** role on the `web` guard is refused the whole admin
  panel (403 everywhere) because `Referral` is deliberately absent from `PANEL_ROLES`.
* **`Waiter` has no panel access at all** — 403 on every URL, correctly, and it is *also* listed in
  `FLOOR_ROLES` as belt-and-braces.
* **The signed routes are genuinely signed.** Unsigned requests measured:
  `/booking/success/{id}` → **403**, `/booking/receipt/{id}` → **403**, `/space-receipts/{id}` → **404**
  (the controller's own `abort_unless` fires first). The reasoning in the comments at
  `routes/web.php:81-108` is sound: sequential integer ids behind a signature.
* **Filament's resource list pages *are* route-gated.** The known trap is real —
  `ListRecords::authorizeAccess()` is empty
  (`vendor/filament/filament/src/Resources/Pages/ListRecords.php:72`) — but
  `Filament\Resources\Pages\Page` uses `CanAuthorizeResourceAccess`, whose `mountCanAuthorizeResourceAccess()`
  Livewire hook runs `abort_unless(static::getResource()::canAccess(), 403)` on every resource page.
  Verified for all 15 resources × 13 role variants: every denied combination returned 403, never a
  partially-rendered page. The explicit `use CanAuthorizeResourceAccess` in
  `ListSpaceBookings.php:14` is harmless redundancy.
* **The space-booking write boundary is the best-built gate in the codebase.** `RestrictsSpaceBookingWrites`
  is an allowlist, `Front Desk` and `Scanner` get the documented read-only cross-view (index 200,
  `/create` 403, `canWrite()` false), and the custom confirm/deny actions re-check server-side rather
  than relying on `visible()`.
* **Super-Admin-only areas hold exactly.** `/admin/rooms`, `/admin/spaces`, `/admin/activity-logs` and
  `/admin/manage-settings` refuse `Manager`, every floor role and the legacy aliases. `RevenueStatsWidget`
  is Super-Admin-only as specified.
* **Tickets, scan logs, referrals and vendor requests** are closed to every floor role (403 across the
  board), with `ScanTicket` correctly carved out for `Scanner` only
  (`ScanTicket.php:40-48` — and `Front Desk`, `Space Host`, `Host`, `Waiter` all measured at 403 there).
* **The unauthenticated route surface is otherwise appropriate.** Enumerating every route with no
  `auth`/`signed` middleware yields the public site (`/`, `/about`, `/rooms`, …), the ticket purchase
  flow, the payment callbacks and webhooks (CSRF-exempt by necessity, which is correct for
  gateway-to-server calls), and the rate-limited public API (`api/rooms`, `api/services`, `api/contact`,
  `api/bookings`, `api/service-bookings` — all `throttle`d, and the booking endpoints only *write*).
  The Hospi API in `routes/api.php:42-120` is uniformly behind `auth:sanctum` with per-route
  `hospi.role:` middleware. `filament/exports/{export}/download` carries no auth middleware but the
  controller enforces ownership (`vendor/filament/actions/src/Exports/Http/Controllers/DownloadExport.php:17-22`),
  and no export action is registered in this app.
  The two exceptions are F2 (`/invoices/{id}/view`) and F5 (`/ticket/verify/{uuid}`).
  `/vendor-payment/{vendorRequest}` is also public with a sequential id and renders the vendor's name,
  e-mail, spot and price (`resources/views/vendor/payment.blade.php:31-44`) — lower impact than F2
  because it only resolves for `status = approved`, but it should get the same `signed` treatment.

---

## Pattern-level recommendation

**The structural cause is that role membership is expressed as string literals duplicated across
independent gates, and the area gates default to *allow*.**

Three gates currently answer "who is this person": `User::PANEL_ROLES`, `RestrictsManagementAreas::FLOOR_ROLES`,
`RestrictsSpaceBookingWrites::SPACE_WRITERS` / `RestrictsRoomBookingWrites::ROOM_READ_ONLY_ROLES` — plus
ad-hoc inline arrays in `ScanTicket.php:47`, `ManageSettings.php:39`, `ActivityLogResource.php:29`,
`RoomResource.php:54`, `SpaceResource.php:53`, `InvoicePolicy.php:88,94`, and four widget `canView()`
methods. Adding a role means remembering all of them. Every finding above except F2 is a copy of the same
mistake:

* F1 — `Manager` was never considered against the Administration denylist.
* F4 — the `Host` alias reached three lists and missed the fourth.
* F6 — `Admin` reached the seeder and missed `PANEL_ROLES`.
* F8 — `super_admin` reached three policies and missed the seeder.
* The already-fixed `Space Host` bug — reached `PANEL_ROLES` and missed the trait.

Renaming the trait to `RestrictsManagementAreas` (commit `73cea72`) treated the symptom: it makes the file
easier to *find*, but it is still a separate list, and it is still default-allow, so forgetting it is still
silent and still grants rather than denies.

**Cheapest durable fix, in order of effort:**

1. **One role registry.** Create `app/Support/Roles.php` holding every canonical name and its accepted
   aliases, plus the derived sets (`PANEL`, `FLOOR`, `MANAGEMENT`, `SPACE_WRITERS`, `ROOM_WRITERS`), and
   have all the gates read from it. Aliases then propagate by construction, which alone would have
   prevented F4, F6 and the original `Space Host` bug. This is a few dozen lines and touches no logic.
2. **Flip the two dangerous resources to default-deny.** `UserResource` and `RoleResource` should name who
   *may* enter, not who may not (F1). A denylist is defensible for operational areas where the cost of
   forgetting is "a waiter sees the room calendar"; it is not defensible where the cost of forgetting is
   "a new role can mint Super Admin". Use the denylist for the floor, an allowlist for the keys.
3. **Make the matrix a test that fails loudly.** `tests/Feature/RoleAccessMatrixTest.php` already has the
   right shape — an exhaustive role × area table where anything not listed must 403. Extend it so that
   (a) every role in `RoleSeeder` is a row, including `Admin` and `Waiter`, which would have caught F6;
   (b) every registered resource and page is a column, enumerated from
   `Filament::getPanel('admin')->getResources()` rather than hand-listed, so a new resource breaks the
   test until its row is filled in; (c) WRITE is asserted as well as VIEW — `canCreate`/`canEdit`/
   `canDelete` per role per resource — which would have caught F4, where VIEW is identical for
   `Space Host` and `Host` and only WRITE differs; and (d) one case per escalation path asserts that a
   non-owner role *cannot* save a role change onto itself.
4. **Lint for the shape.** A one-line CI grep that fails on `canAccess(): return auth()->guard('web')->check();`
   in any resource under `app/Filament/Resources/` would have flagged F3 and F7 at review time.

None of this requires defining `spatie` permissions. Moving to real permissions with a
`users.manage` / `roles.manage` gate is the textbook answer and would make the whole class of bug
structurally impossible, but it is a larger migration and should be treated as a later option, not as
the fix for F1 — F1 needs the five-line allowlist above, today.

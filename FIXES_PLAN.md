# FIXES_PLAN.md

Plan for the two fixes in `host.md`, verified against vendor and app code. Documentation only — no code, no migrations, no tests run here.

---

## Reality check: the login bug is NOT double hashing

`host.md` leads with the double-hashing hypothesis. **It is wrong for this codebase.** Nobody should "fix" it.

- `app/Models/User.php` casts `'password' => 'hashed'` (line ~53).
- Laravel's cast is **idempotent**: `vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasAttributes.php::castAttributeAsHashedString()` (line ~1471) does `if (! Hash::isHashed($value)) { return Hash::make($value); }` — an already-hashed value passes straight through.
- So `Hash::make()` **plus** the cast hashes exactly once. The three call sites that combine them are all safe and need no change: `app/Http/Controllers/Api/StaffController.php:85`, `database/factories/UserFactory.php:31`, `database/seeders/HospiDemoSeeder.php:95`.
- `app/Filament/Resources/UserResource.php`'s password field is already correct: `->required(fn ($operation) => $operation === 'create')` + `->dehydrated(fn (?string $state) => filled($state))`, no `Hash::make`. Blank on edit keeps the existing password. `CreateUser` / `EditUser` have no `mutateFormDataBeforeCreate` and no hashing.

### The real cause

`vendor/filament/filament/src/Pages/Auth/Login.php` (lines ~67-76): after a **successful** `attempt()`, if `! $user->canAccessPanel(...)` Filament calls `Filament::auth()->logout()` and then `$this->throwFailureValidationException()` — **the same exception it throws for a wrong password.** A user whose role is not allowed into the panel sees a message indistinguishable from "wrong password".

`User::PANEL_ROLES` (committed state) allows `Super Admin`, `super_admin`, `admin`, `Manager`, `Front Desk`, `frontdesk`, `Space Host`, `Scanner`, plus an email bypass for `victorynonso9@gmail.com`. **`Host` is not in it.** The role shipped by the previous change is named `Space Host` (`database/seeders/RoleSeeder.php:19-23`), deliberately, to avoid clashing with the restaurant Host role in `HOSPI_SALES_BRIEF.md`. `RoleResource` lets staff create arbitrary roles, so a user created with a role literally named `Host` authenticates fine and is then refused the panel.

> Working-tree note: `app/Models/User.php` already carries three of the four Fix 1 changes (uncommitted) — `'Host'` in `PANEL_ROLES`, the refusal `Log::warning`, and `setEmailAttribute()`. Verify and keep those rather than re-applying them.

---

## FIX 1 — Host login

Four changes, no migration.

1. **Treat `Host` as an alias of `Space Host`.** Add `'Host'` to `User::PANEL_ROLES` (`app/Models/User.php:88-95`) **and** to `RestrictsSpaceBookingWrites::SPACE_WRITERS` (`app/Filament/Concerns/RestrictsSpaceBookingWrites.php`: `Super Admin`, `super_admin`, `Admin`, `admin`, `Manager`, `Space Host`). Whichever name was used then works. `Space Host` stays the canonical name — seeder and docs are not renamed.
2. **Do not change the login error message.** Filament uses one message for both failures on purpose, to prevent account enumeration. Instead `canAccessPanel()` logs a warning naming the user id, email, roles and panel when access is refused *after* a successful password check, so the next occurrence is diagnosable from `storage/logs/laravel.log`. Simplest sensible choice.
3. **Normalise email on save** — `setEmailAttribute()` on `User`, `strtolower(trim($value))`, null-safe. A trailing space or stray capital can no longer create an unloggable account. Additive, no migration.
4. **New `php artisan user:reset-password {email}`** (`app/Console/Commands/ResetUserPassword.php`):
   - `secret()` prompt (hidden input) plus a confirmation prompt; abort if they differ.
   - Assign the **plain** password to `$user->password` and `save()` **once**, letting the `hashed` cast hash it exactly once. It must **not** call `Hash::make` — correct but redundant, and it invites the double-hash confusion back.
   - Print the user's roles and whether `canAccessPanel()` passes, so it doubles as a diagnostic. Non-zero exit when the email matches no user.

### Test to add — `tests/Feature/HostLoginTest.php`

- Users with role `Host` and with `Space Host`, created the way staff create them, both log into the panel successfully.
- A role with no panel grant is refused, and the generic failure message is unchanged.
- `user:reset-password` leaves a password `Hash::check()` accepts — the guard proving hashing happens once.
- `' Mixed@Case.COM '` saves as `mixed@case.com`.

---

## FIX 2 — Receipt required for manual space bookings

### Current state (verified)

Space bookings shipped in the previous change: `space_bookings` (`database/migrations/2026_10_20_090000_create_space_bookings_table.php`), `app/Models/SpaceBooking.php`, `app/Services/SpaceBookingWorkflowService.php` (`confirmBooking` / `rejectBooking` / `markBookingPaid`, idempotent), `app/Filament/Resources/SpaceBookingResource.php` (+ `Pages/SpaceBookingCalendar`, `ListSpaceBookings`, `CreateSpaceBooking`, `EditSpaceBooking`), `app/Services/SpaceBookingReportService.php`, `app/Filament/Widgets/PendingSpaceBookingsWidget.php`, `app/Filament/Widgets/SpaceBookingCalendarWidget.php`. Guest bookings arrive via `app/Http/Controllers/Api/ServiceBookingController.php::store()`. **No receipt column exists yet.**

### 1. Migration (additive)

Adds to `space_bookings`, guarded with `Schema::hasColumn()` to match the existing migration's defensive style: `receipt_path` (nullable string), `receipt_original_name` (nullable string), `receipt_mime` (nullable string), `receipt_uploaded_at` (nullable timestamp), `source` (string, default `website`).

`source` distinguishes `manual` (created in the backend) from `website` (guest) — that is what makes the receipt requirement apply **only** to manual bookings. `CreateSpaceBooking` stamps `manual`; `ServiceBookingController::store()` keeps the `website` default.

### 2. Storage

The **private `local` disk**, under `storage/app/space-receipts/`, filename `Str::random(40)` plus the real extension. Never inside `public/`, never the original filename (kept only in `receipt_original_name` for display). **`php artisan storage:link` is not needed.**

### 3. Where the requirement is enforced — and why there

In `SpaceBookingWorkflowService::confirmBooking()`, which **throws** when `source === 'manual'`, `receipt_path` is empty and the booking is not already paid. Not in the form: the resource action, `PendingSpaceBookingsWidget`, the calendar widget and any future caller all funnel through this service, so the rule holds for all of them — a form-only rule would be bypassed by the widget's confirm action. Website and paid bookings are exempt. The Filament actions catch the exception and surface it as a notification rather than a 500.

### 4. Upload field

`FileUpload` on `SpaceBookingResource::form` (`->schema([...])`, line ~76): `->disk('local')`, `->directory('space-receipts')`, `acceptedFileTypes(['application/pdf','image/jpeg','image/png','image/heic','image/heif'])`, `maxSize(10240)` (10 MB), private visibility.

**HEIC** is accepted and stored as-is. Browsers cannot preview it and no converter (Imagick HEIF / libheif) is installed, so conversion to JPG is **deliberately out of scope** — a decision, not an omission. The file still downloads and opens on the Host's own phone.

### 5. Mobile camera-or-file

Filament's `FileUpload` renders a FilePond input. Simplest sensible approach: add `accept="image/*,application/pdf"` plus `capture` support to the underlying input via an `extraInputAttributes` / render-hook tweak, so mobile browsers offer **Camera / Files**. Verified at a 375px viewport rather than assumed.

### 6. Signed route

`GET /space-receipts/{spaceBooking}` in `routes/web.php`, named `space.receipt`, `signed` middleware, **permanent** (`URL::signedRoute`, no expiry) so links embedded in an exported report PDF keep working. `host.md` asked for ~1 year or permanent; permanent signed is simpler and has no expiry-renewal problem. It streams from the private disk with the stored mime and original filename. A tampered signature returns **403**; a booking with no receipt returns **404**.

Precedent: `booking.success` and `booking.receipt` (`routes/web.php:93-99`) use the same pattern, minted in `app/Mail/BookingConfirmedMail.php` via `URL::signedRoute(...)` — the recorded reason being that a sequential id would otherwise let anyone enumerate guest details.

### 7. Where the Receipt column appears

- **Space booking table** (`SpaceBookingResource::table`, after `payment_status`) — a "View receipt" link; `Online` when `source === 'website'`, `—` for a manual booking with none yet.
- **Booking edit page** — the upload field plus a link to the current file.
- **Report rows and PDF** — `SpaceBookingReportService::rows()` adds a `receipt` key beside the existing `room` / `occupant` / `check_in` / `amount`, and `resources/views/pdf/booking-report.blade.php` gains an optional Receipt cell, rendered only when the key is present so the room report sharing this template is unaffected — same approach as the existing optional `unitLabel`.

### 8. Front Desk

Can **view and open** receipts. Upload, replace and delete are gated by `RestrictsSpaceBookingWrites::canWrite()`, so Front Desk gets 403 on any write — consistent with its existing read-only cross-visibility over space bookings.

---

## Files to create

- `app/Console/Commands/ResetUserPassword.php`
- `database/migrations/XXXX_XX_XX_XXXXXX_add_receipt_to_space_bookings_table.php`
- `app/Http/Controllers/SpaceReceiptController.php`
- `tests/Feature/HostLoginTest.php`
- `tests/Feature/SpaceBookingReceiptTest.php`

## Files to change

- `app/Models/User.php` — `'Host'` in `PANEL_ROLES`, refusal log, `setEmailAttribute()`
- `app/Filament/Concerns/RestrictsSpaceBookingWrites.php` — `'Host'` in `SPACE_WRITERS`
- `app/Models/SpaceBooking.php` — fillable/casts for the new columns, `hasReceipt()`, `isManual()`, signed-URL accessor
- `app/Services/SpaceBookingWorkflowService.php` — receipt requirement in `confirmBooking()`
- `app/Filament/Resources/SpaceBookingResource.php` — `FileUpload`, Receipt column, action error handling
- `app/Filament/Resources/SpaceBookingResource/Pages/CreateSpaceBooking.php` — stamp `source = 'manual'`
- `app/Filament/Widgets/PendingSpaceBookingsWidget.php` — surface the confirm failure
- `app/Services/SpaceBookingReportService.php` — `receipt` in rows
- `resources/views/pdf/booking-report.blade.php` — optional Receipt cell
- `routes/web.php` — `space.receipt` signed route
- `app/Http/Controllers/Api/ServiceBookingController.php` — explicit `source = 'website'`

---

## QA checklist (mirrors host.md)

**Host**
- [ ] Created through the user form; logs in successfully (role `Host` and role `Space Host`).
- [ ] `php artisan user:reset-password {email}` hides input, confirms, and the new password logs in.
- [ ] Confirming a manual booking with no receipt is refused with a readable message — from the resource action **and** from `PendingSpaceBookingsWidget`.
- [ ] Uploads by file and by camera at 375px; PDF, JPG, PNG, HEIC accepted; >10 MB rejected.
- [ ] Receipt visible in the table, on the booking edit page, and in daily/weekly/monthly reports.

**Signed links**
- [ ] A receipt link opened from an exported report PDF works while logged out.
- [ ] A tampered signature is rejected with 403.

**Front Desk**
- [ ] Can view and open receipts.
- [ ] Gets 403 trying to upload, replace or delete one, and cannot confirm or deny a space booking.

**No regressions**
- [ ] Room bookings and Front Desk behaviour unchanged (`RestrictsRoomBookingWrites` still denies `Space Host`; the room report PDF renders without a Receipt column).
- [ ] Guest website bookings still confirm with no receipt and show `Online`.
- [ ] Full suite passes — especially `SpaceBookingAccessTest`, `SpaceBookingPublicFlowTest`, `BookingReportTest`, `BookingReceiptTest`.

**Deploy:** `git pull` → `php artisan migrate --force` → `php artisan optimize:clear`. `php artisan storage:link` is **not** required — receipts live on the private disk.

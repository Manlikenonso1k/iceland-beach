# Filament Admin UI — Customisation Guide

How the Iceland Beach admin login was restyled, written so another agent (or
a future you) can extend it without rediscovering the dead ends.

**Stack:** Filament v3.2 · Laravel 12 · Tailwind v4
**Scope:** admin login page (`/admin/login`). The rest of the panel is stock.

---

## 1. TL;DR — the mechanism

Filament's auth pages are Livewire components with their own layout. Rather
than replacing that layout (which breaks the form), the styling is **injected
from outside** via Filament's render-hook API, scoped so it only affects the
login page.

```
AdminPanelProvider
  ├── panel(): ->login(App\Filament\Pages\Auth\Login::class)
  └── boot(): 3 × FilamentView::registerRenderHook(..., scopes: Login::class)
                ├── HEAD_END          → filament.auth.theme  (all the CSS)
                ├── SIMPLE_PAGE_START → filament.auth.brand  (logo + heading)
                └── SIMPLE_PAGE_END   → filament.auth.foot   (footer line)
```

The Livewire form itself is never touched, so validation, rate-limiting and
session handling keep working exactly as Filament shipped them.

---

## 2. Files

| File | Role |
|---|---|
| `app/Providers/Filament/AdminPanelProvider.php` | Registers the login page + the three render hooks |
| `app/Filament/Pages/Auth/Login.php` | Extends Filament's Login; overrides only `getHeading()` |
| `resources/views/filament/auth/theme.blade.php` | Fonts + the entire stylesheet |
| `resources/views/filament/auth/brand.blade.php` | Logo, eyebrow, heading, sub-line |
| `resources/views/filament/auth/foot.blade.php` | Small print under the card |

Nothing in `vendor/` is modified, and no Filament blade is overridden.

---

## 3. The three pieces

### 3.1 Register the page

In `AdminPanelProvider::panel()`:

```php
->login(\App\Filament\Pages\Auth\Login::class)
```

`->login()` accepts the class. **This is the only correct way** to swap in a
custom login page (see §5 for what fails).

### 3.2 The page class

```php
namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return '';   // stock header hidden in CSS; brand.blade.php replaces it
    }
}
```

Deliberately **no `protected static $view`**. Overriding the view means owning
Filament's layout, Livewire wiring and form rendering. Not worth it — CSS can
reach everything from outside.

### 3.3 The render hooks

In `AdminPanelProvider::boot()`:

```php
public function boot(): void
{
    $login = \App\Filament\Pages\Auth\Login::class;

    FilamentView::registerRenderHook(
        PanelsRenderHook::HEAD_END,
        fn (): string => view('filament.auth.theme')->render(),
        scopes: $login,
    );

    FilamentView::registerRenderHook(
        PanelsRenderHook::SIMPLE_PAGE_START,
        fn (): string => view('filament.auth.brand')->render(),
        scopes: $login,
    );

    FilamentView::registerRenderHook(
        PanelsRenderHook::SIMPLE_PAGE_END,
        fn (): string => view('filament.auth.foot')->render(),
        scopes: $login,
    );
}
```

Required imports:

```php
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
```

> **`scopes:` is what keeps the dashboard clean.** Drop it and the CSS leaks
> into every panel page. Scope to a page class, or to a resource class.

---

## 4. Filament v3.2 DOM reference

The classes the stylesheet targets. Confirmed against
`vendor/filament/filament/resources/views/components/layout/simple.blade.php`
and `.../components/page/simple.blade.php`.

| Selector | What it is | Stock Tailwind worth knowing |
|---|---|---|
| `.fi-simple-layout` | Outer wrapper, full viewport | `flex min-h-screen flex-col items-center` |
| `.fi-simple-main-ctn` | Centering container | `flex w-full flex-grow items-center justify-center` |
| `.fi-simple-main` | **The card** | `my-16 w-full bg-white px-6 py-12 shadow-sm ring-1 sm:rounded-xl sm:px-12` |
| `.fi-simple-page` | Inner grid inside the card | — |
| `.fi-simple-header` | Stock logo + "Sign in" heading | hidden by the theme |
| `.fi-input-wrp` | Field shell — **carries the ring**, not the input | `ring-1` |
| `.fi-input` | The `<input>` itself | |
| `.fi-input-wrp-suffix` / `-prefix` | Affix slots (password reveal lives here) | |
| `.fi-btn.fi-color-primary` | Submit button | |
| `.fi-fo-field-wrp-label` | Field label | |
| `.fi-fo-field-wrp-error-message` | Validation text | |

### Gotchas found the hard way

- **The card border is a Tailwind `ring`, not a `border`.** Setting
  `border-color` does nothing. Use `--tw-ring-color`.
- **The checkbox is `border-none` + `ring-gray-950/10`** (a *dark* ring). On a
  dark card it disappears and the box reads as a flat grey chip. Fix by
  setting a light `--tw-ring-color`, not a background:
  ```css
  .fi-simple-main input[type='checkbox'] {
      --tw-ring-color: rgba(244, 247, 246, 0.32) !important;
  }
  ```
- **The password reveal button** renders inside `.fi-input-wrp-suffix` with its
  own background. Strip it or it looks bolted on.
- Most overrides need `!important` — Filament's utilities are equally specific.

### Available render hooks

From `vendor/filament/filament/src/View/PanelsRenderHook.php`:

```
panels::head.end                 HEAD_END
panels::body.start               BODY_START
panels::body.end                 BODY_END
panels::simple-page.start        SIMPLE_PAGE_START
panels::simple-page.end          SIMPLE_PAGE_END
panels::auth.login.form.before   AUTH_LOGIN_FORM_BEFORE
panels::auth.login.form.after    AUTH_LOGIN_FORM_AFTER
```

`grep -oE "const [A-Z_]+ = '[^']*'" vendor/filament/filament/src/View/PanelsRenderHook.php`
for the full list.

---

## 5. What does NOT work

Recorded because each one costs a debugging cycle.

| Attempt | Result |
|---|---|
| `->pages([..., Login::class])` | **Fatal.** `BadMethodCallException: Method App\Filament\Pages\Auth\Login::registerRoutes does not exist.` Auth pages are not regular pages — use `->login()`. |
| Custom blade with its own `<!DOCTYPE html>` | Silently ignored. Filament renders through its own layout; a standalone document never gets used. |
| Custom blade + bare `->login()` (no class arg) | Silently ignored. Filament serves its own Login class, so your `$view` is never read. **Looks like nothing happened.** |
| `->customCss([...])` on the panel | Method doesn't exist in v3.2. |
| Setting `border-color` on card/inputs | No effect — they use Tailwind `ring`. |

---

## 6. Design tokens

Defined at the top of `theme.blade.php`:

```css
--ib-basalt:  #0b0f0e;   /* near-black ground        */
--ib-glacier: #f4f7f6;   /* off-white text           */
--ib-teal:    #6fb8bc;   /* focus rings, active      */
--ib-ember:   #e2924a;   /* primary CTA, accents     */
--ib-ember-deep: #d9701f;
--ib-glass-border: rgba(244, 247, 246, 0.14);
```

Fonts: **Fraunces** (serif headings), **Hanken Grotesk** (UI). Both loaded from
Google Fonts inside `theme.blade.php`.

Motion: `cubic-bezier(0.16, 1, 0.3, 1)`, 0.45–1.1s. Background does a 46s
ken-burns drift. All of it disabled under `prefers-reduced-motion`.

Panel colours are set separately in `panel()->colors(['primary' => '#e2924a', ...])`
— that drives Filament components across the whole admin, not just login.

---

## 7. Common edits

**Change the background photo** — `theme.blade.php`, `.fi-simple-layout::before`:
```css
background: url('/images/hero.jpg') center 64% / cover no-repeat;
```
The `64%` biases the crop away from the flat sky. Adjust per image.

**Change wording / logo** — `brand.blade.php`. Plain HTML.

**Move the card** (currently left-weighted):
```css
.fi-simple-main-ctn { justify-content: flex-start; }  /* center | flex-end */
```

**Lighten or darken the photo** — the two `linear-gradient` stops in
`.fi-simple-layout::after`. There's a separate, lighter vertical grade under
`@media (max-width: 48rem)` so the photo stays visible on phones.

**Apply this to another panel** — repeat §3 in that panel's provider with its
own Login class. Hooks are registered per-provider and scoped per-class.

**Style a different admin page** — same pattern, new `scopes:` target. E.g.
`scopes: \App\Filament\Resources\BookingResource\Pages\ListBookings::class`.

---

## 8. Verifying a change

```bash
php artisan view:clear      # required — hook views are Blade-cached
php artisan config:clear    # only if you touched the provider
```

There is no asset build step; the CSS is inline in the hook view, so **`npm run
build` is not needed** for login styling changes.

Then look at it. Screenshot rather than assume — several bugs above were only
visible in a render:

```js
// node script.mjs   (playwright is already a devDependency)
import { chromium } from 'playwright';
const b = await chromium.launch();
const p = await (await b.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
await p.goto('http://127.0.0.1:8001/admin/login', { waitUntil: 'networkidle' });
await p.waitForTimeout(1400);
await p.screenshot({ path: 'login.png' });
await b.close();
```

**Always re-check auth after a styling change** — submit bad credentials and
confirm you get *"These credentials do not match our records."* and stay on
`/admin/login` with no console errors. Styling should never alter behaviour;
if it did, something overrode the view rather than the CSS.

Checked at 1440px and 390px: no horizontal bleed, form functional.

---

## 9. Reverting

Delete `resources/views/filament/auth/`, remove `boot()` from
`AdminPanelProvider`, and change `->login(Login::class)` back to `->login()`.
`app/Filament/Pages/Auth/Login.php` can stay or go. Stock Filament returns
immediately — nothing else depends on any of it.

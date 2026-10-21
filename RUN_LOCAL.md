REPOS (there are 2):
- MOBILE APP repo: the Iceland app (Expo / React Native)Tenstrings Music Ins@DESKTOP-EUP5SGC MINGW64 ~/Documents/iceland app (main)
$ . In this prompt it's called "Hospit-app".
- WEB repo: the Iceland web app (Laravel backend + website + API)Tenstrings Music Ins@DESKTOP-EUP5SGC MINGW64 ~/Documents/Iceland (main)
$ . In this prompt it's called "iceland-beach".
Before starting, find both folders on this PC, confirm which is which (the web repo has artisan and composer.json; the app repo has app.config.ts and eas.json), and write both full paths at the top of PROGRESS.md.
PROGRESS.md lives in the WEB repo root only.
Backend and API changes go in the WEB repo; app screens go in the MOBILE APP repo. Commit and push each repo separately.

Add booking views to the Hospi mobile app (Hospit-app, Expo) for the CEO, Manager and Host roles, backed by the iceland-beach Laravel API. The website already shows who booked which room and space in a calendar widget. The app has nothing for this, so bring the same visibility to the app.

PROGRESS FILE (do this first, keep it updated)
Create PROGRESS.md in the iceland-beach root. After EVERY step, update it with: the current phase, what's done, what's next, files changed, open issues and the last commit hash. If this session ends, the next agent must be able to read PROGRESS.md and continue from where you stopped. If PROGRESS.md already exists, read it first and resume from it. Don't start over.

USE 4 SUBAGENTS, IN ORDER

1. PLANNER (no code changes)
Read both repos and write the plan into PROGRESS.md:
- how the web calendar gets room and space bookings (models, queries, Filament widgets),
- which API endpoints already exist and which the app needs,
- role rules, mirroring the website's permissions:
  · CEO and Manager: VIEW all room and space bookings (who booked, dates/times, room or space, price, status, receipt), plus room and space details (images, prices, features). View-only unless the website already lets them do more.
  · Host: manage space bookings, view-only room bookings (same as the web).
  · Other app roles (chef, waiter and so on): no booking access.
- the app screens to add.
Keep it short. Make sensible choices and note them. Don't ask me questions.

2. CODER
Backend (iceland-beach):
- Add read-only API endpoints, authenticated with the app's existing auth (Sanctum or similar): room bookings and space bookings by date range (for the calendar), single booking details, and rooms and spaces with images (full public URLs), prices, pricing options and features.
- Enforce roles on the SERVER with policies or middleware. Return only the fields each role needs.
- Paginate and filter by date range. Never return everything at once.
App (Hospit-app):
- A "Bookings" section for CEO, Manager and Host: a calendar view (month/week) with Rooms and Spaces tabs, color-coded by status, where tapping a booking shows guest name, contact, room or space, dates/times, amount, status and a receipt link.
- A Rooms & Spaces screen: images, prices, features, quantities.
- Pull to refresh, loading and empty states, and a clear message when offline.
- Host keeps edit actions for spaces only if the website allows it. Otherwise view-only for now.
Commit and push each repo after each working part, and update PROGRESS.md.

3. QA TESTER
- Feature tests for every endpoint and role: CEO, Manager and Host get the right data; Host gets 403 when changing rooms; chef, waiter and guests get 403; no token gets 401.
- Data in the app matches the web calendar for the same dates.
- Images load with full URLs, and prices and features match the admin.
- Test the app on web (per-tab login) for each role.
- The full existing test suite still passes.
Record results in PROGRESS.md.

4. CYBERSECURITY AGENT
Audit ALL API routes (php artisan route:list --path=api), not just the new ones, and fix the issues:
- endpoints missing auth or role checks,
- IDOR: changing a booking ID in the URL to see someone else's booking,
- public endpoints (e.g. the /services page API) leaking guest names, phone numbers, emails or receipts,
- receipt links: signed URLs only, never guessable public paths,
- mass assignment (fillable/guarded) on booking models,
- rate limiting on login and the API,
- CORS limited to the real origins,
- APP_DEBUG=false, and no stack traces or SQL errors returned by the API,
- no secrets in the app bundle (API keys, DB credentials) and none committed to either repo,
- token expiry and logout revoking the token.
Fix each one, add a test for it, and list the findings in PROGRESS.md with severity and status.

RULES
- No direct database access from the app. Everything goes through the API.
- Additive migrations only. Never run migrate:fresh or seed over live data, and never touch the production database.
- Don't break the website, room booking, or existing app features (orders, kitchen, overdue alerts).
- Don't build a new APK. Commit and push, and I'll build.

FINAL REPORT (also saved at the bottom of PROGRESS.md)
- New endpoints, and which roles can use each one
- App screens added
- QA results: pass/fail
- Security findings and fixes, by severity
- Production deploy commands (git pull, php artisan migrate --force, php artisan optimize:clear)
- Anything left to do
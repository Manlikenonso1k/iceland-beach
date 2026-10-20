Add Space Booking and a new Host role to the iceland-beach project (Laravel 12 + Filament 3). The WEBSITE/backend is the priority, so finish it first. The mobile app (Hospit-app) comes second and is optional if time runs short.

Use 2 subagents:
1. PLANNER: reads the code and writes the plan. No code changes.
2. CODER + QA: builds from the plan, then tests.

WHAT I WANT
Room booking works perfectly. It's calendar-based: guests book from the website, the Front Desk role books, edits, confirms and denies reservations in the backend, and Front Desk can run daily, weekly and monthly reports with total figures.

We already have space management (spaces to rent out, not rooms), but it doesn't work like room booking. I want:
1. Space booking that works EXACTLY like room booking: same calendar, same reserve / edit / confirm / deny flow, same daily, weekly and monthly reports with totals, and bookable from the public website the same way rooms are. Reuse or copy the room booking code. Don't redesign it.
2. A new role: Host. Host manages space bookings, with the same powers Front Desk has for rooms.
3. Cross-viewing, read-only:
   - Front Desk can VIEW space bookings and space reports, but cannot book, edit, confirm, deny or delete them.
   - Host can VIEW room bookings and room reports, but cannot book, edit, confirm, deny or delete them.
4. Don't change how room booking or Front Desk works today.

NOTE: The Hospi mobile app may already have a role called "Host" (restaurant host). Check for a naming clash in the roles table and code. If one exists, tell me and use a clear name for the new role (e.g. "Space Host") instead of merging the two.

STEP 1: PLANNER SUBAGENT
Read the project and write SPACE_BOOKING_PLAN.md in the root, covering:
- how room booking works now (models, Filament resources, calendar, reports, permissions, website booking flow),
- what exists for spaces today and what needs adding,
- how roles and permissions work (Spatie / policies / Filament canAccess) and exactly how to add Host and the read-only rules,
- migrations needed (additive only),
- files to create or change,
- a test checklist per role.
Keep it short. Don't ask me questions. Make the simplest sensible choice and note it in the plan.

STEP 2: CODER + QA SUBAGENT (website first)
Build from SPACE_BOOKING_PLAN.md.
Rules:
- Reuse or copy the room booking code for spaces.
- Don't break room booking or Front Desk.
- Enforce read-only on the SERVER (policies/permissions), not just by hiding buttons.
- Additive migrations only. Never run migrate:fresh, never seed over data, never touch the live database.
- Create local test users: frontdesk@test.com, host@test.com, admin@test.com.

Test with feature tests AND in the Filament panel:
- Host: can book, edit, confirm and deny SPACE bookings and see space reports; can VIEW room bookings and room reports; gets BLOCKED (403) from creating, editing, confirming, denying or deleting room bookings, including direct URLs and API calls.
- Front Desk: rooms work exactly as before; can VIEW spaces and space reports; gets BLOCKED from changing space bookings.
- Admin: full access to both.
- Public website: a guest can book a space, and it shows up on the Host's calendar.
- Space report totals match the bookings in the database.
- The whole existing test suite passes.
Commit and push the website work once all tests pass, before starting the app.

STEP 3: MOBILE APP (Hospit-app), only after the website is done and pushed
- If the app has a front-desk or bookings section, add the matching Host views: manage space bookings, view-only room bookings. Use the same backend API and permissions.
- If the API needs new endpoints, add them to iceland-beach with the same server-side permission checks.
- Don't build a new APK. Just commit and push. I'll build later.
- If this gets complicated, stop and list what's left instead.

FINAL REPORT (short)
- Files changed and migrations added
- Test results: pass/fail per checklist item
- Decisions you made, plus any Host naming clash
- What was done in the app and what's left
- Deploy commands for the server (git pull, php artisan migrate --force, php artisan optimize:clear)
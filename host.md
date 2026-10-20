Two fixes in iceland-beach (Laravel 12 + Filament 3). Use the same 2 subagents: PLANNER first (reads the code, no changes, writes a short plan in FIXES_PLAN.md), then CODER + QA (fixes, tests, pushes). Don't ask me questions. Make the simplest sensible choice and note it.

FIX 1: Host can't log in ("wrong password")
I created a Host user with a password, and logging in says the password is wrong. Find the real cause. Check:
- Double hashing: the User model has a 'password' => 'hashed' cast AND the create form or seeder also calls Hash::make / bcrypt. Hash it only once.
- How the Filament user form saves passwords (dehydrateStateUsing, mutateFormDataBeforeCreate) and whether editing a user re-hashes or blanks the password.
- Email with a trailing space or different capitalization. Trim and lowercase on save and on login.
- canAccessPanel() in the User model: Host (and Space Host, if that's the role name) must be allowed into the panel.
- Whether the user was created in a different database (local vs live) from the one I'm logging into.
Fix the cause, not just this one user. Add a test: create a Host through the same form or flow I used, then log in successfully. Also add an artisan command to reset a user's password safely:
php artisan user:reset-password {email}
It should prompt for the password, hide the input and hash it once.

FIX 2: Receipt required for manual space bookings
- When a Host creates or confirms a MANUAL space booking (made in the backend, not by a guest on the website), a receipt upload is REQUIRED before the booking can be confirmed.
- Accept PDF, JPG, PNG and HEIC (convert HEIC to JPG if needed), max 10 MB.
- On a phone, the upload must let the Host take a photo with the camera OR choose a file. Use an input with accept="image/*,application/pdf" so mobile browsers offer Camera / Files. Test it on a phone-sized screen.
- Store receipts on the server (storage/app, not inside public/) with an unguessable filename, and link them to the booking.
- Add a "Receipt" column to the space booking table AND to the space reports (daily/weekly/monthly), including any PDF or Excel export, with a clickable "View receipt" link that opens or downloads the file.
- The links must work from inside an exported PDF, so use a long-lived Laravel signed URL (temporarySignedRoute, valid ~1 year, or a permanent signed route) rather than a login-only route. Don't use plain public URLs.
- Show the receipt on the booking view page too. Front Desk (view-only) can see and open receipts but can't upload, replace or delete them.
- Website bookings by guests don't need a receipt. Show "Online" or "—" in that column.

QA
- Host: created via the user form, logs in successfully, can't confirm a manual booking without a receipt, can upload by file and by camera (phone-sized test), and the receipt shows in the table, the booking view and the reports.
- A receipt link opened from an exported report PDF works without logging in; a tampered or expired link is rejected.
- Front Desk can view receipts but gets 403 trying to change them.
- Room booking and Front Desk behavior are unchanged.
- The full test suite passes.

FINAL REPORT (short)
- Root cause of the login bug and the fix
- Files changed and migrations added (additive only)
- Test results per item
- Deploy commands, including php artisan storage:link if it's needed, plus:
  git pull, php artisan migrate --force, php artisan optimize:clear
Commit and push when everything passes.
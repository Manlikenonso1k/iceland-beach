# Deployment Fix Prompts and Changes

## Prompts Used (condensed)
- "Use the same method with my other project; it works well in the flow."
- "How do I get my SSH private key?"
- "Continue from here" (SSH session context).
- "Why is it deploying at morithos.com?"
- "All done, what’s next?"
- "Key already in use; actions still don’t work."
- "Here’s what I see in Actions" (logs provided multiple times).
- "Clean the secret so I don’t copy spaces."
- "Invalid workflow file: script already defined."
- "Public keys are different; how do I update?"
- "Which private key again?"
- "It says repository not found."
- "Key already in use but no key is there."
- "What’s next?"
- "It worked—update tools folder with prompts and what we did different; create prompts.md."
- "Hero overlay hidden / gradient alignment / text width issues."
- "Fix responsive layout issue: content shrinks on mobile; horizontal scroll." 
- "Apply fix only to index.php; other pages OK." 
- "Update prompts file with latest fixes."
- "Card text overlaps; keep .card-text within cards across page."
- "Add WhatsApp notification for contact form; use CallMeBot; store API key in config."
- "How do I get the API key; is it free?"
- "Confirm CallMeBot activation number."
- "Build a structured admin system with dashboard, inventory, waiters, tables, POS, reports, settings."
- "Proceed with Phase 2: CRUD + POS + receipts + reports."

## What We Did Different (Fix Summary)
1. **Workflow update**: Switched deploy workflow to the Hostinger SSH method that initializes an SSH agent on the server and performs a clean `git fetch` + `git reset --hard` instead of `git pull`.
2. **Secrets configuration**: Added Hostinger secrets for host/user/port/deploy path and a valid SSH private key.
3. **SSH key handling**:
   - Verified the server’s SSH keys and permissions.
   - Added Hostinger’s public key to GitHub as a **Deploy Key** with write access.
   - Generated a new key pair (`id_ed25519_iceland`) when the old key was already in use.
4. **Known hosts**: Added `ssh-keyscan` for `github.com` to avoid interactive host verification.
5. **Workflow cleanup**: Removed duplicate `script` block that caused the YAML error.
6. **Repository access fix**: Resolved `Repository not found` by using a repo-specific deploy key instead of a reused key already bound elsewhere.

## UI/Layout Fixes Added (Index Page Only)
1. **Hero overlay alignment**: Made gradient stretch full width, left-aligned overlay text, removed container width constraints, and constrained paragraph width for clean line breaks.
2. **Index-only responsive fix**: Added `body.home-page` class and scoped full-width/overflow rules to index.php only.
3. **Wrapper consistency**: Added `main.site-wrapper` to stabilize layout and prevent horizontal overflow on mobile.

## UI/Text Overflow Fix (Global)
- Added `.card-text` wrapping rules to prevent overflow and keep text inside cards across pages.

## Contact Form WhatsApp Notification
1. **Added WhatsApp notifier**: Implemented `send_whatsapp_notification()` using CallMeBot API.
2. **Safe config**: Added `core/config/whatsapp.example.php` and ignored `core/config/whatsapp.php` in git.
3. **Hooked to contact form**: Triggered WhatsApp notification only after email send success in `contact.php`.
4. **Activation instructions**: Use CallMeBot number from their page, send "I allow callmebot to send me messages" to receive API key.

## Files Updated (Layout)
- `index.php` (set `bodyClass = 'home-page'`)
- `includes/header.php` (body class support, main wrapper start)
- `includes/footer.php` (main wrapper close)
- `static/styles/style.css` (home page overflow/width fix and hero overlay styles)
 - `static/styles/style.css` (card text wrap fix)

## Files Updated (WhatsApp)
- `core/controller/whatsapp.php`
- `core/config/whatsapp.example.php`
- `.gitignore` (ignore `core/config/whatsapp.php`)
- `contact.php`

## Admin System Phase 1–2 (New)
1. **Admin dashboard tabs**: Added top-level tabs for Dashboard, Room Bookings, Inventory, Waiters, Tables, POS, Reports, Settings.
2. **Preserved booking logic**: Existing Rooms/Services/Membership logic moved under Room Bookings tab.
3. **Inventory CRUD**: Add/edit/delete products with low stock alerts.
4. **Waiter management**: Create waiters, enable/disable access, role stored.
5. **Table management**: Assign tables to waiters and set status.
6. **POS flow**: Waiter login → select table → add items → confirm order; stock auto-reduced; anti-duplicate safeguard.
7. **Receipts & reports**: 80mm receipt print; daily report print with filters.
8. **DB helpers**: Added `insertGetId()` and `delete()` helpers for DB operations.

## Files Updated (Admin System)
- `admin/index.php` (new tabs, inventory/waiter/table/report logic)
- `admin/pos.php` (POS workflow)
- `admin/receipt.php` (print receipt)
- `admin/report_print.php` (print report)
- `core/config/dbquery.php` (insertGetId, delete)
- `TOOLS/admin_schema.sql` (new tables and seed)

## Commands/Steps Actually Used
- `ls -la ~/.ssh/`
- `cat ~/.ssh/id_ed25519`
- `cat ~/.ssh/id_ed25519.pub`
- `cat ~/.ssh/authorized_keys`
- `chmod 700 ~/.ssh`
- `chmod 600 ~/.ssh/authorized_keys`
- `ssh-keygen -t ed25519 -C "icelandbeach-deploy" -f ~/.ssh/id_ed25519_iceland -N ""`
- Added deploy key in GitHub repo settings (Allow write access)
- Updated workflow to use `id_ed25519_iceland`

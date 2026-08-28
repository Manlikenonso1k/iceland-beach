You are a Laravel-React migration expert.

TASK: Clean up legacy PHP and flatten laravel-app/ structure to root

CURRENT STRUCTURE:
iceland-beach/
├── laravel-app/          ← All new code is here
│   ├── app/
│   ├── config/
│   ├── resources/
│   ├── routes/
│   ├── public/
│   ├── prompt/           ← Already moved here
│   ├── composer.json
│   ├── package.json
│   └── ...
├── (legacy PHP files)    ← OLD - to be deleted
├── composer.json         ← Root level
├── package.json          ← Root level
└── ...

DESIRED FINAL STRUCTURE:
iceland-beach/
├── app/                  ← Moved from laravel-app/
├── config/               ← Moved from laravel-app/
├── resources/            ← Moved from laravel-app/
├── routes/               ← Moved from laravel-app/
├── public/               ← Moved from laravel-app/
├── prompt/               ← Will be at root level
├── composer.json         ← Root level
├── package.json          ← Root level
├── .env
├── artisan
├── .git/
├── .gitignore
└── (all other Laravel files at root)

STEP 1: MOVE LARAVEL-APP FILES
- Move ALL contents from laravel-app/ to iceland-beach/ root (including prompt/)
- Flatten the structure completely
- Delete the empty laravel-app/ folder
- Update any internal imports/paths that reference laravel-app/

STEP 2: DELETE LEGACY PHP FILES
Delete these root-level files:
- PHP files: index.php, about.php, gallery.php, login.php, logout.php, membership.php, profile.php, rooms.php, ticket.php.bak, contact.php, festival.php, book.php, event.php, messagesent.php, renew.php, soon.php, services.php, menu.php
- Folders: admin/, includes/, static/, scripts/, bootstrap-5.3.2-dist/
- Old templates: star-admin2-free-admin-template-1.0.0/
- Zips: bootstrap-5.3.2-dist.zip
- Backup files: *.bak

STEP 3: VERIFY ROUTES
- Check routes/ for any hardcoded paths
- Ensure public/ folder is set correctly for serving React build
- Verify all imports still work after flattening

STEP 4: VERIFY DEPENDENCIES
- Ensure composer.json and package.json are at root level
- Verify node_modules/ and vendor/ are at root level
- Check .env file is present and correct

STEP 5: GIT CLEANUP
- Show git status
- Suggest .gitignore updates if needed
- Do NOT commit yet - just prepare for commit

OUTPUT:
1. Show commands to safely move laravel-app/ contents to root
2. Show commands to delete legacy PHP files
3. Verify no broken imports or paths
4. Confirm final structure is clean
5. Show git status before commit

Do NOT commit until all steps are verified.
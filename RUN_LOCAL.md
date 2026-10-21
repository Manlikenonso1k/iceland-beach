Bug: space images uploaded in the admin (https://icelandbeach.com/admin/spaces) don't show on the public page (https://icelandbeach.com/services). Every space shows the same fallback image, /legacy-images/Coconut%20Cabana.jpeg, instead of its uploaded image.

The upload saves (the services row gets main_image like "spaces/main/01M5....jpeg"), and the earlier missing updated_at column is fixed.

Find the real cause. Check:
1. Which field the /services page reads. It may still read an old or legacy image column, or a hardcoded legacy image map, instead of main_image and gallery_images. Make it use main_image (and gallery_images) and fall back to the legacy image ONLY when main_image is empty.
2. The disk. Which disk the Filament FileUpload for spaces uses ('public' vs 'local'). Public images must be on the 'public' disk (storage/app/public/spaces/...). If they're on 'local' or private, switch to 'public' and handle the files already uploaded.
3. The URL. The frontend, API or resource must return a full public URL via Storage::disk('public')->url($path), not the raw path. If the React frontend builds URLs itself, fix it there.
4. Whether an <img onError> fallback is hiding a broken URL (e.g. a 404 because public/storage isn't linked). Log or check the actual URL the page tries to load.
5. Caching: any cached API response or view for services that needs clearing after an update.

Don't change how other pages show images. Fix it for all spaces, not just one.
Add a test: upload a main image to a space, then confirm the /services output contains that image's public URL and not the legacy fallback.

Report the root cause, files changed, and the exact commands to run on production. Include php artisan storage:link if it's needed, and npm run build plus committing public/build if any frontend code changed. Commit and push.
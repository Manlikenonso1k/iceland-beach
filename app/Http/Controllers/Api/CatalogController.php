<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    /**
     * Space catalogue for the public /services page.
     *
     * ⚠️ `main_image` and `gallery_images` MUST stay in the select list.
     *
     * They were previously absent, so an admin could upload a photo, see it
     * saved, and the public page would still show the same legacy fallback for
     * every single space — the column holding the upload was never fetched, and
     * the front end fell back to the one hardcoded image. Narrowing this select
     * again reintroduces that bug silently, because nothing throws: the
     * attribute is simply missing and the fallback looks deliberate.
     */
    public function services(): JsonResponse
    {
        $spaces = ServiceBooking::query()
            ->select([
                'id',
                'service_name',
                'service_category',
                'service_price',
                // Legacy path, still the fallback for spaces predating uploads.
                'service_image',
                // The Filament uploads. Without these two, displayImage()
                // cannot see the upload and silently returns the legacy value.
                'main_image',
                'gallery_images',
            ])
            ->orderBy('service_category')
            ->orderBy('service_name')
            ->get();

        $spaces->each(function (ServiceBooking $space): void {
            // Resolved server-side so the front end never has to know whether
            // an image came from a Filament upload on the `public` disk or the
            // legacy public path. Exactly what Api\BookingController::rooms()
            // already does for rooms — spaces had simply never been given the
            // same treatment.
            $space->setAttribute('image_url', $space->displayImage());
            $space->setAttribute('gallery_urls', $space->galleryUrls());

            // price_label and is_complimentary are computed here too, so the
            // React pages do not re-implement what "free" means.
            $space->setAttribute('is_complimentary', $space->isComplimentary());
            $space->setAttribute('is_free', $space->isFree());
            $space->setAttribute('price_label', $space->priceLabel());
        });

        return response()->json($spaces);
    }
}

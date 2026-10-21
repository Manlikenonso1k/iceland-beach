<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function services(): JsonResponse
    {
        $spaces = ServiceBooking::query()
            ->select(['id', 'service_name', 'service_category', 'service_price', 'service_image'])
            ->orderBy('service_category')
            ->orderBy('service_name')
            ->get();

        // price_label and is_complimentary are computed SERVER-side so the
        // React pages do not re-implement the rule. "₦0.00" was showing on
        // spaces that are never charged for; the model decides what free looks
        // like, and the admin table renders the same string.
        $spaces->each(function (ServiceBooking $space): void {
            $space->setAttribute('is_complimentary', $space->isComplimentary());
            $space->setAttribute('is_free', $space->isFree());
            $space->setAttribute('price_label', $space->priceLabel());
        });

        return response()->json($spaces);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function services(): JsonResponse
    {
        return response()->json(ServiceBooking::query()
            ->select(['id', 'service_name', 'service_category', 'service_price', 'service_image'])
            ->orderBy('service_category')
            ->orderBy('service_name')
            ->get());
    }
}
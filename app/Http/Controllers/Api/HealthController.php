<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Unauthenticated reachability probe for the mobile app's config screen — it
 * proves the phone can reach this Laravel instance over the LAN before any login.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'version' => 'hospi-sales-v1',
            'env' => app()->environment(),
            'time' => now()->toIso8601String(),
        ]);
    }
}

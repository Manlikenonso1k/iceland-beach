<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /api/me — the app's single source of business identity. Everything the app
 * would otherwise be tempted to hardcode (business name, logo, colour, enabled
 * departments, prices) comes from here, scoped to the token's tenant.
 */
class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $prices = $tenant
            ? TenantSetting::map()
            : [];

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'department' => $user->department,
                'roles' => $user->getRoleNames()->values(),
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo_url' => $tenant->logoUrl(),
                'primary_colour' => $tenant->primary_colour,
                'enabled_departments' => $tenant->enabled_departments ?? [],
                'timezone' => $tenant->timezone,
            ] : null,
            'prices' => [
                'gate_ticket' => (int) ($prices['gate_ticket'] ?? 0),
                'per_head' => (int) ($prices['per_head'] ?? 0),
                'swimming_per_head' => (int) ($prices['swimming_per_head'] ?? 0),
            ],
        ]);
    }
}

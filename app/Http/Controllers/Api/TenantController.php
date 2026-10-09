<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Business (tenant) configuration — the onboarding/setup surface. A manager
 * chooses which departments the business runs; everything downstream (the
 * waitress kitchen picker, the manager board stations) reads enabled_departments
 * from /api/me, so this is the single switch that reshapes the app per business.
 *
 * The tenant is the caller's own (from the token), so a manager can only ever
 * configure their own business.
 */
class TenantController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled_departments' => ['sometimes', 'array', 'min:1'],
            'enabled_departments.*' => ['string', Rule::in(Tenant::ALL_DEPARTMENTS)],
            'name' => ['sometimes', 'string', 'max:255'],
            'primary_colour' => ['sometimes', 'string', 'max:9'],
            'timezone' => ['sometimes', 'string', 'max:64'],
        ]);

        $tenant = $request->user()->tenant;
        abort_if(! $tenant, 404, 'No tenant for this account.');

        if (array_key_exists('enabled_departments', $data)) {
            // Keep the catalogue's order, de-duplicated.
            $data['enabled_departments'] = array_values(array_filter(
                Tenant::ALL_DEPARTMENTS,
                fn (string $d) => in_array($d, $data['enabled_departments'], true),
            ));
        }

        $tenant->update($data);

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo_url' => $tenant->logoUrl(),
                'primary_colour' => $tenant->primary_colour,
                'enabled_departments' => $tenant->enabled_departments ?? [],
                'timezone' => $tenant->timezone,
            ],
        ]);
    }
}

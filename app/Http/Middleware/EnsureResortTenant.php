<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confines a route to staff of the resort that owns this website.
 *
 * `bookings` and `space_bookings` are the WEBSITE's ledger. They predate
 * Hospi Sales multi-tenancy and carry no `tenant_id`, so BelongsToTenant cannot
 * scope them and every tenant-scoped control in this codebase is silently
 * absent on them. The practical consequence, confirmed by test, was that a
 * Manager/CEO/Host of ANY other business on Hospi Sales could read
 * /api/bookings/rooms/{id} and get a resort guest's name, email, phone and a
 * valid signed receipt URL by walking a sequential integer.
 *
 * The role gate (`hospi.role`) answers "which role?"; it cannot answer "whose
 * business?". This middleware answers the second question and is the only thing
 * standing between the two tenants on these routes.
 *
 * Who is allowed:
 *   - a user with no tenant at all — the legacy website admins, who are the
 *     resort's own staff and existed long before tenants did;
 *   - a user whose tenant matches config('resort.tenant_slug').
 *
 * Deliberately FAILS OPEN when no tenant row matches that slug. A deployment
 * whose resort tenant is named something else would otherwise have this
 * middleware lock the resort's own managers out of their own calendar — a
 * misconfiguration must not become an outage. Set RESORT_TENANT_SLUG in .env
 * to the real slug and the gate engages; the fail-open path is a safety net,
 * not the intended configuration.
 */
class EnsureResortTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // auth:sanctum runs first, so this is belt-and-braces only.
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Legacy website admins have no tenant. They are the resort.
        if ($user->tenant_id === null) {
            return $next($request);
        }

        $resortTenantId = Tenant::query()
            ->where('slug', (string) config('resort.tenant_slug'))
            ->value('id');

        // Unresolvable slug — see the class docblock. Do not lock anyone out,
        // but do NOT fail open silently: in this state the gate is doing
        // nothing at all, which is indistinguishable from the vulnerability it
        // exists to close. Logged at warning so a slug mismatch is visible in
        // storage/logs/laravel.log rather than being discovered by an incident.
        if ($resortTenantId === null) {
            Log::warning('EnsureResortTenant is inactive: no tenant matches the configured slug.', [
                'configured_slug' => (string) config('resort.tenant_slug'),
                'route' => $request->path(),
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'consequence' => 'Cross-tenant booking reads are NOT blocked. Set RESORT_TENANT_SLUG.',
            ]);

            return $next($request);
        }

        if ((int) $user->tenant_id !== (int) $resortTenantId) {
            return response()->json([
                'message' => 'These bookings belong to another business.',
            ], 403);
        }

        return $next($request);
    }
}

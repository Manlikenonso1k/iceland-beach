<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side role gate for the Hospi Sales API. Role isolation is enforced
 * here, not by the app hiding a tab — a waitress token refused on a chef route
 * is the control that matters. Uses the seeded role NAMES (e.g. 'Manager',
 * 'Waiter'), since this codebase defines no permissions, only roles.
 *
 *   Route::post(...)->middleware('hospi.role:Manager,Super Admin');
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'This action is unauthorized for your role.',
            ], 403);
        }

        return $next($request);
    }
}

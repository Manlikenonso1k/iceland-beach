<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Confines every query and every insert on a model to a single tenant.
 *
 * The tenant is resolved from the authenticated user (i.e. the Sanctum token),
 * NEVER from a request parameter — a client-supplied tenant_id is the single
 * most likely way Beach A ends up reading Beach B's data.
 *
 * Server-side jobs and the console have no authenticated user, so they set an
 * explicit context via Tenant::use()/currentTenantId binding; without one, the
 * scope does not filter (the caller must scope by hand, e.g. per-tenant loops).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = static::currentTenantId();

            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('tenant_id'))) {
                $tenantId = static::currentTenantId();

                if ($tenantId !== null) {
                    $model->setAttribute('tenant_id', $tenantId);
                }
            }
        });
    }

    /**
     * The tenant the current execution context is confined to, or null when
     * there is no context (unauthenticated console/job code must scope by hand).
     * An explicit binding wins so scheduled jobs can iterate tenant by tenant.
     */
    public static function currentTenantId(): ?int
    {
        if (app()->bound('currentTenantId')) {
            $bound = app('currentTenantId');

            return $bound === null ? null : (int) $bound;
        }

        $user = Auth::user();

        // Under token auth the sanctum guard may not be the default guard, so
        // fall back to it explicitly before giving up (console/jobs stay null).
        if (! $user && app()->bound('request') && Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
        }

        return $user && isset($user->tenant_id) ? (int) $user->tenant_id : null;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Query without the tenant guard — use only in deliberate cross-tenant code. */
    public function scopeAcrossTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}

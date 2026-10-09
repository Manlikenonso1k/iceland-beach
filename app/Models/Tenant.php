<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** The full catalogue a business can enable. Kitchens take orders; services are other stations. */
    public const KITCHEN_DEPARTMENTS = ['main_kitchen', 'grill_kitchen', 'barbecue', 'ice_cream'];

    public const SERVICE_DEPARTMENTS = ['gate_sales', 'hosts', 'front_desk'];

    public const ALL_DEPARTMENTS = [
        'main_kitchen', 'grill_kitchen', 'barbecue', 'ice_cream',
        'gate_sales', 'hosts', 'front_desk',
    ];

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'primary_colour',
        'enabled_departments',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'enabled_departments' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Run a callback with this tenant as the active context, for console/job
     * code that has no authenticated user. Restores the previous binding after.
     */
    public static function runAs(int $tenantId, callable $callback): mixed
    {
        $previous = app()->bound('currentTenantId') ? app('currentTenantId') : false;
        app()->instance('currentTenantId', $tenantId);

        try {
            return $callback();
        } finally {
            if ($previous === false) {
                app()->forgetInstance('currentTenantId');
            } else {
                app()->instance('currentTenantId', $previous);
            }
        }
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(TenantSetting::class);
    }

    public function logoUrl(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        return asset('storage/'.ltrim($this->logo_path, '/'));
    }
}

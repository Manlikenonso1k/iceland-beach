<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use BelongsToTenant;

    public const DEPARTMENTS = ['main_kitchen', 'grill_kitchen', 'barbecue', 'ice_cream'];

    public const DEFAULT_PREP_MINUTES = 30;

    protected $fillable = [
        'tenant_id',
        'department',
        'name',
        'category',
        'price',
        'prep_time_minutes',
        'is_available',
        'sku',
        'image_path',
    ];

    /** Request-host URL for the meal image, or null. Pass the current request. */
    public function imageUrl(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        return url('storage/'.ltrim($this->image_path, '/'));
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'prep_time_minutes' => 'integer',
            'is_available' => 'boolean',
        ];
    }
}

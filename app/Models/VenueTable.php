<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class VenueTable extends Model
{
    use BelongsToTenant;

    protected $table = 'venue_tables';

    protected $fillable = [
        'tenant_id',
        'name',
        'zone',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

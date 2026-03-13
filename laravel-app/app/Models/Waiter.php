<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Waiter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'username',
        'password_hash',
        'pin_hash',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function voidLogs(): HasMany
    {
        return $this->hasMany(VoidLog::class, 'voided_by');
    }
}

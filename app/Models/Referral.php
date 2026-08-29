<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Referral extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ReferralTicketSale::class);
    }

    /**
     * Resolve an active referral from a code typed at checkout.
     *
     * Codes are stored uppercase and matched case-insensitively so guests can
     * type them however they like. Returns null for unknown or deactivated
     * codes — an unrecognised code must never block a purchase.
     */
    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        return static::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Generate a unique code. Ambiguous characters (0/O, 1/I) are excluded so
     * codes survive being read aloud or written down.
     */
    public static function generateCode(string $prefix = 'IB'): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = strtoupper($prefix) . '-' . $suffix;
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    /** Total kobo credited to this referral. */
    public function totalAmount(): int
    {
        return (int) $this->sales()->where('status', 'successful')->sum('amount');
    }

    public function successfulSalesCount(): int
    {
        return $this->sales()->where('status', 'successful')->count();
    }
}

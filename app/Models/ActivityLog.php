<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Device class parsed from the stored user agent.
     *
     * Derived on read rather than stored, so historical rows gain it too — the
     * raw user_agent has been captured all along.
     */
    public function deviceType(): string
    {
        $ua = (string) $this->user_agent;

        if ($ua === '') {
            return 'Unknown';
        }

        return match (true) {
            str_contains($ua, 'iPad') || str_contains($ua, 'Tablet') => 'Tablet',
            str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone') => 'Mobile',
            default => 'Desktop',
        };
    }

    /** Browser parsed from the stored user agent. Order matters — see below. */
    public function browser(): string
    {
        $ua = (string) $this->user_agent;

        if ($ua === '') {
            return 'Unknown';
        }

        // Edge and Chrome both contain "Chrome"; Chrome and Safari both contain
        // "Safari". Most specific first.
        return match (true) {
            str_contains($ua, 'Edg/') || str_contains($ua, 'Edge') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Unknown',
        };
    }
}

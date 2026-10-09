<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A tracked meal order. The traffic-light colour and seconds_remaining are
 * COMPUTED here from due_at/completed_at/status against server time — never
 * stored, never taken from the phone clock — so the board and the app cannot
 * drift and a staff member changing their phone clock cannot change the SLA.
 */
class Order extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['pending', 'accepted', 'declined', 'ready', 'completed', 'cancelled'];

    // Statuses a kitchen is still working (candidates for the overdue sweep).
    public const OPEN_STATUSES = ['pending', 'accepted'];

    public const COLOUR_GREEN = 'green';   // completed in time
    public const COLOUR_AMBER = 'amber';   // in progress, within SLA
    public const COLOUR_RED = 'red';       // overdue, or declined
    public const COLOUR_GREY = 'grey';     // completed late

    protected $fillable = [
        'tenant_id',
        'department',
        'placed_by_user_id',
        'chef_user_id',
        'table_id',
        'guest_ref',
        'status',
        'placed_at',
        'target_minutes',
        'due_at',
        'accepted_at',
        'ready_at',
        'completed_at',
        'declined_at',
        'decline_reason',
        'declined_by_user_id',
        'total_amount',
        'notified_overdue_at',
        'last_nudged_at',
        'expedited_at',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
            'due_at' => 'datetime',
            'accepted_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'declined_at' => 'datetime',
            'notified_overdue_at' => 'datetime',
            'last_nudged_at' => 'datetime',
            'expedited_at' => 'datetime',
            'target_minutes' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by_user_id');
    }

    public function chef(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_user_id');
    }

    public function declinedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declined_by_user_id');
    }

    /**
     * The single source of truth for the traffic light, evaluated at $now
     * (defaults to server now). Mirrors HOSPI_SALES_BRIEF §1 exactly.
     */
    public function statusColour(?Carbon $now = null): string
    {
        $now ??= now();

        if ($this->completed_at !== null) {
            return $this->completed_at->lessThanOrEqualTo($this->due_at)
                ? self::COLOUR_GREEN
                : self::COLOUR_GREY;
        }

        if ($this->status === 'declined') {
            return self::COLOUR_RED;
        }

        return $now->greaterThan($this->due_at) ? self::COLOUR_RED : self::COLOUR_AMBER;
    }

    /** Seconds until due_at (negative once overdue), from server time. */
    public function secondsRemaining(?Carbon $now = null): int
    {
        $now ??= now();

        return (int) round($now->diffInSeconds($this->due_at, false));
    }

    /**
     * Board ordering: RED first (most urgent), then AMBER, then the rest.
     * The colour is computed, so we order by the raw conditions it derives from.
     */
    public function scopeBoardOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE
                WHEN completed_at IS NULL AND (status = 'declined' OR due_at < ?) THEN 0
                WHEN completed_at IS NULL THEN 1
                ELSE 2 END", [now()])
            ->orderBy('due_at');
    }
}

<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public static function log(
        string $event,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
    ): void {
        $actor = $user ?? Auth::user();

        ActivityLog::create([
            'user_id' => $actor?->id,
            'event' => $event,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties !== [] ? $properties : null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}

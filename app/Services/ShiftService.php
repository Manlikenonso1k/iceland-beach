<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Chef shift lifecycle. A chef holds at most one open shift at a time; starting
 * a new one closes any previous open shift. Department defaults to the chef's
 * own department. Tenant scoping is inherited from the Shift model.
 */
class ShiftService
{
    public function start(User $chef, ?string $department = null): Shift
    {
        $department = $department ?: $chef->department;

        if (! $department || ! in_array($department, Meal::DEPARTMENTS, true)) {
            throw ValidationException::withMessages([
                'department' => 'A valid kitchen department is required to start a shift.',
            ]);
        }

        // One open shift per chef — close any existing before opening a new one.
        Shift::query()->open()->where('user_id', $chef->id)->update(['ended_at' => now()]);

        return Shift::query()->create([
            'user_id' => $chef->id,
            'department' => $department,
            'started_at' => now(),
        ]);
    }

    public function end(User $chef): void
    {
        Shift::query()->open()->where('user_id', $chef->id)->update(['ended_at' => now()]);
    }

    public function current(User $chef): ?Shift
    {
        return Shift::query()->open()->where('user_id', $chef->id)->latest('started_at')->first();
    }
}

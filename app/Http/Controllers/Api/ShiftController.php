<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function current(Request $request): JsonResponse
    {
        $shift = $this->shifts->current($request->user());

        return response()->json(['data' => $this->present($shift)]);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'department' => ['nullable', 'string'],
        ]);

        $shift = $this->shifts->start($request->user(), $data['department'] ?? null);

        return response()->json(['data' => $this->present($shift)], 201);
    }

    public function end(Request $request): JsonResponse
    {
        $this->shifts->end($request->user());

        return response()->json(['data' => null]);
    }

    private function present(?\App\Models\Shift $shift): ?array
    {
        if (! $shift) {
            return null;
        }

        return [
            'id' => $shift->id,
            'department' => $shift->department,
            'started_at' => optional($shift->started_at)->toIso8601String(),
        ];
    }
}

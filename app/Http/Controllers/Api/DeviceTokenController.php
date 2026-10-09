<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /** Register (or refresh) this device's Expo push token for the current user. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'expo_token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:ios,android'],
        ]);

        $user = $request->user();

        // Re-registering the same physical token re-points it at this user rather
        // than duplicating, so a shared device follows whoever logged in.
        DeviceToken::query()->updateOrCreate(
            ['expo_token' => $data['expo_token']],
            [
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'platform' => $data['platform'] ?? null,
            ],
        );

        return response()->json(['message' => 'Device registered.'], 201);
    }
}

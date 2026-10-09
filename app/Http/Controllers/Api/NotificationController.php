<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-side of the app's notification bell. Backed by Laravel's database
 * notifications (the same records the order events write), scoped to the
 * authenticated user by the framework's relations.
 */
class NotificationController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['count' => 0]);
    }
}

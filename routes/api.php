<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookingViewController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ServiceBookingController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/rooms', [BookingController::class, 'rooms'])->middleware('throttle:60,1');
Route::get('/services', [CatalogController::class, 'services'])->middleware('throttle:60,1');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1');
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:10,1');
Route::post('/service-bookings', [ServiceBookingController::class, 'store'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Hospi Sales API (additive) — Phase 0: health, auth, identity
|--------------------------------------------------------------------------
*/
Route::get('/health', HealthController::class)->middleware('throttle:60,1');

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    // Throttled like every other write route. It was the only unthrottled one.
    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->middleware('throttle:30,1');
    Route::get('/me', MeController::class);
    Route::patch('/tenant', [TenantController::class, 'update'])
        ->middleware(['throttle:30,1', 'hospi.role:Manager,Super Admin,Admin']);

    // Phase 2 — the order / delay engine.
    Route::get('/meals', [MealController::class, 'index']);
    Route::post('/meals', [MealController::class, 'store'])
        ->middleware(['throttle:60,1', 'hospi.role:Manager,Super Admin,Admin']);
    Route::patch('/meals/{meal}', [MealController::class, 'update'])
        ->middleware(['throttle:60,1', 'hospi.role:Manager,Super Admin,Admin']);
    Route::post('/meals/{meal}/image', [MealController::class, 'uploadImage'])
        ->middleware(['throttle:30,1', 'hospi.role:Manager,Super Admin,Admin']);

    Route::get('/orders/board', [OrderController::class, 'board'])
        ->middleware('hospi.role:Manager,Super Admin,Admin');
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware(['throttle:30,1', 'hospi.role:Waiter,Manager']);

    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])
        ->middleware(['throttle:60,1', 'hospi.role:Chef,Manager']);
    Route::post('/orders/{order}/decline', [OrderController::class, 'decline'])
        ->middleware(['throttle:60,1', 'hospi.role:Chef,Manager']);
    Route::post('/orders/{order}/ready', [OrderController::class, 'ready'])
        ->middleware(['throttle:60,1', 'hospi.role:Chef,Manager']);
    Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])
        ->middleware(['throttle:60,1', 'hospi.role:Waiter,Manager']);
    Route::post('/orders/{order}/nudge', [OrderController::class, 'nudge'])
        ->middleware(['throttle:60,1', 'hospi.role:Manager,Super Admin,Admin']);
    Route::post('/orders/{order}/expedite', [OrderController::class, 'expedite'])
        ->middleware(['throttle:60,1', 'hospi.role:Manager,Super Admin,Admin']);
    Route::post('/orders/{order}/reassign', [OrderController::class, 'reassign'])
        ->middleware(['throttle:60,1', 'hospi.role:Manager,Super Admin,Admin']);

    // CEO / Manager operations dashboard: department revenue + delay accountability.
    Route::get('/dashboard/revenue', [DashboardController::class, 'revenue'])
        ->middleware('hospi.role:Manager,CEO,Super Admin,Admin');

    // Staff account creation (role matrix enforced in the controller).
    Route::get('/staff', [StaffController::class, 'index'])
        ->middleware('hospi.role:Manager,CEO,Super Admin,Admin');
    Route::post('/staff', [StaffController::class, 'store'])
        ->middleware(['throttle:30,1', 'hospi.role:Manager,CEO,Super Admin,Admin']);

    // Tables (manager-managed; any staff can list).
    Route::get('/tables', [TableController::class, 'index']);
    Route::post('/tables', [TableController::class, 'store'])
        ->middleware(['throttle:60,1', 'hospi.role:Manager,Super Admin,Admin']);
    Route::patch('/tables/{table}', [TableController::class, 'update'])
        ->middleware(['throttle:60,1', 'hospi.role:Manager,Super Admin,Admin']);

    // Order transfers between staff (waitress↔waitress, chef↔chef).
    Route::get('/orders/{order}/transfer-targets', [TransferController::class, 'targets'])
        ->middleware('hospi.role:Waiter,Chef,Manager,CEO,Super Admin,Admin');
    Route::post('/orders/{order}/transfer', [TransferController::class, 'store'])
        ->middleware(['throttle:30,1', 'hospi.role:Waiter,Chef,Manager,CEO,Super Admin,Admin']);
    Route::get('/transfers', [TransferController::class, 'incoming'])
        ->middleware('hospi.role:Waiter,Chef');
    Route::post('/transfers/{transfer}/accept', [TransferController::class, 'accept'])
        ->middleware(['throttle:60,1', 'hospi.role:Waiter,Chef']);
    Route::post('/transfers/{transfer}/decline', [TransferController::class, 'decline'])
        ->middleware(['throttle:60,1', 'hospi.role:Waiter,Chef']);

    Route::get('/shifts/current', [ShiftController::class, 'current'])
        ->middleware('hospi.role:Chef,Manager,Super Admin,Admin');
    Route::post('/shifts/start', [ShiftController::class, 'start'])
        ->middleware(['throttle:30,1', 'hospi.role:Chef,Manager,Super Admin,Admin']);
    Route::post('/shifts/end', [ShiftController::class, 'end'])
        ->middleware(['throttle:30,1', 'hospi.role:Chef,Manager,Super Admin,Admin']);

    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->middleware('throttle:30,1');

    Route::post('/device-tokens', [DeviceTokenController::class, 'store'])
        ->middleware('throttle:30,1');

    /*
    |----------------------------------------------------------------------
    | Booking visibility — READ ONLY
    |----------------------------------------------------------------------
    | Mirrors what the website's calendar widgets show. CEO and Manager see
    | everything; Space Host/Host sees both but, as on the web, may not change
    | room bookings — and no write route exists here at all, so that holds by
    | construction. Every other role (Chef, Waiter, Scanner, Front Desk) is
    | refused by the role gate, and no token is refused by auth:sanctum.
    |
    | 'Host' is listed alongside 'Space Host' because the two are aliases — see
    | User::PANEL_ROLES. Omitting one is a bug this project has already had
    | twice.
    */
    Route::middleware('hospi.role:CEO,Manager,Super Admin,Admin,Space Host,Host')->group(function (): void {
        // from/to are REQUIRED and the window is capped in the controller, so
        // there is no way to ask for the whole ledger.
        Route::get('/bookings/calendar', [BookingViewController::class, 'calendar'])
            ->middleware(['throttle:60,1', 'hospi.resort']);

        // `hospi.resort` on the three routes below, and not on the inventory
        // ones, is deliberate. These carry a named guest's email and phone and
        // a signed receipt URL, and `bookings`/`space_bookings` have no
        // tenant_id for BelongsToTenant to scope — so without it a Manager of
        // ANOTHER business on Hospi Sales reads resort guests' contact details
        // by walking a sequential id. The inventory routes return only the room
        // and space catalogue, which /api/rooms and /api/services already serve
        // to the public, so gating them would buy nothing.
        Route::get('/bookings/rooms/{booking}', [BookingViewController::class, 'roomBooking'])
            ->middleware(['throttle:60,1', 'hospi.resort']);

        Route::get('/bookings/spaces/{spaceBooking}', [BookingViewController::class, 'spaceBooking'])
            ->middleware(['throttle:60,1', 'hospi.resort']);

        Route::get('/inventory/rooms', [InventoryController::class, 'rooms'])
            ->middleware('throttle:60,1');

        Route::get('/inventory/spaces', [InventoryController::class, 'spaces'])
            ->middleware('throttle:60,1');
    });
});

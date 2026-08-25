<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ServiceBookingController;
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

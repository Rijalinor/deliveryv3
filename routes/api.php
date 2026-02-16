<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DriverApiController;
use App\Http\Controllers\Api\TripApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API routes (if needed)
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Protected API routes - require authentication
Route::middleware(['web', 'auth'])->group(function () {
    
    // Driver Location Tracking
    Route::prefix('driver')->group(function () {
        Route::post('/location', [DriverApiController::class, 'updateLocation'])
            ->name('api.driver.location');
        
        Route::get('/active-trip', [DriverApiController::class, 'getActiveTrip'])
            ->name('api.driver.active-trip');
        
        Route::get('/trip/{trip}', [DriverApiController::class, 'getTripDetails'])
            ->name('api.driver.trip-details');
    });
    
    // Trip Status Management
    Route::prefix('trip')->group(function () {
        Route::post('/{trip}/stop/{stop}/arrived', [TripApiController::class, 'markArrived'])
            ->name('api.trip.mark-arrived');
        
        Route::post('/{trip}/stop/{stop}/done', [TripApiController::class, 'markDone'])
            ->name('api.trip.mark-done');
        
        Route::post('/{trip}/stop/{stop}/rejected', [TripApiController::class, 'markRejected'])
            ->name('api.trip.mark-rejected');
        
        Route::post('/{trip}/finish', [TripApiController::class, 'finishTrip'])
            ->name('api.trip.finish');
    });
});

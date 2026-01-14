<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\Admin\GeocodingController;

Route::middleware(['web', 'auth'])
    ->prefix('admin/api')
    ->group(function () {
        Route::get('/geocode', [GeocodingController::class, 'search'])->name('admin.api.geocode');
        Route::get('/reverse', [GeocodingController::class, 'reverse'])->name('admin.api.reverse');
    });

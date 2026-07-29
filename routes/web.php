<?php

use App\Http\Controllers\QuoteController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleRentalConditionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('quotes', QuoteController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('vehicles', VehicleController::class)->except(['show']);

    Route::get('vehicles/{vehicle}/conditions', [VehicleRentalConditionController::class, 'edit'])
        ->name('vehicles.conditions.edit');
    Route::put('vehicles/{vehicle}/conditions', [VehicleRentalConditionController::class, 'update'])
        ->name('vehicles.conditions.update');
});

require __DIR__.'/settings.php';

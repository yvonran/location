<?php

use App\Http\Controllers\Configuration\BrandController;
use App\Http\Controllers\Configuration\SimulationSettingController;
use App\Http\Controllers\Configuration\VehicleModelController;
use App\Http\Controllers\Configuration\VehicleTypeController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleRentalConditionController;
use App\Support\Roles;
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

// Référentiel marques / types / modèles : réservé au superadmin.
Route::middleware(['auth', 'verified', 'role:'.Roles::SuperAdmin])
    ->prefix('configuration')
    ->name('configuration.')
    ->group(function () {
        Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('vehicle-types', VehicleTypeController::class)
            ->parameters(['vehicle-types' => 'vehicle_type'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('vehicle-models', VehicleModelController::class)
            ->parameters(['vehicle-models' => 'vehicle_model'])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('simulation-settings', [SimulationSettingController::class, 'edit'])
            ->name('simulation-settings.edit');
        Route::put('simulation-settings', [SimulationSettingController::class, 'update'])
            ->name('simulation-settings.update');
    });

require __DIR__.'/settings.php';

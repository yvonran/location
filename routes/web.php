<?php

use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('quotes', QuoteController::class)->only(['index', 'create', 'store', 'show']);
});

require __DIR__.'/settings.php';
